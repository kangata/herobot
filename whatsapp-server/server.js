const { default: makeWASocket, DisconnectReason, makeCacheableSignalKeyStore } = require('@whiskeysockets/baileys')
const { useMySQLAuthState } = require('mysql-baileys')
const express = require('express')
const { Boom } = require('@hapi/boom')
const qrcode = require('qrcode')
const pino = require('pino')
const dotenv = require('dotenv');

dotenv.config();

const app = express()
const port = process.env.WA_PORT || 3000

const logger = pino({
    level: 'error'
});

app.use(express.json())

const connections = new Map()
const qrCodes = new Map()
const credsSavers = new Map() // Store saveCreds and removeCreds functions

const LARAVEL_API_URL = process.env.WHATSAPP_LARAVEL_URL || 'http://localhost';
const WHATSAPP_SERVER_TOKEN = process.env.WHATSAPP_SERVER_TOKEN;

// MySQL configuration from environment variables
const MYSQL_CONFIG = {
    host: process.env.WA_DB_HOST || 'localhost',
    port: parseInt(process.env.WA_DB_PORT) || 3306,
    user: process.env.WA_DB_USER || 'root',
    password: process.env.WA_DB_PASSWORD,
    database: process.env.WA_DB_DATABASE || 'whatsapp',
    tableName: process.env.WA_DB_TABLE_NAME || 'auth',
    retryRequestDelayMs: parseInt(process.env.WA_DB_RETRY_DELAY) || 200,
    maxtRetries: parseInt(process.env.WA_DB_MAX_RETRIES) || 10
};

async function startAllConnections() {
    console.log('MySQL-based authentication initialized. Checking for existing sessions...');

    try {
        // Get all existing sessions from MySQL
        const mysql = require('mysql2/promise');
        const connection = await mysql.createConnection({
            host: MYSQL_CONFIG.host,
            port: MYSQL_CONFIG.port,
            user: MYSQL_CONFIG.user,
            password: MYSQL_CONFIG.password,
            database: MYSQL_CONFIG.database
        });

        // Create auth table if it doesn't exist
        const tableName = MYSQL_CONFIG.tableName;

        await connection.execute(
            'CREATE TABLE IF NOT EXISTS `' + tableName + '` (`session` varchar(50) NOT NULL, `id` varchar(80) NOT NULL, `value` json DEFAULT NULL, UNIQUE KEY `idxunique` (`session`,`id`), KEY `idxsession` (`session`), KEY `idxid` (`id`)) ENGINE=MyISAM;'
        );

        const [rows] = await connection.execute(
            `SELECT DISTINCT session FROM ${tableName} WHERE session IS NOT NULL AND session != ''`
        );

        await connection.end();

        if (rows.length > 0) {
            console.log(`Found ${rows.length} existing sessions. Starting connections...`);

            for (const row of rows) {
                const sessionId = row.session;
                try {
                    // Start connection for each existing session
                    await connectionPool.getConnection(sessionId);
                    console.log(`Started connection for existing session: ${sessionId}`);

                    // Add a small delay between connections to avoid overwhelming the system
                    await new Promise(resolve => setTimeout(resolve, 1000));
                } catch (error) {
                    console.error(`Failed to start connection for session ${sessionId}:`, error);
                }
            }
        } else {
            console.log('No existing sessions found in database.');
        }
    } catch (error) {
        console.error('Failed to check for existing sessions:', error);
        console.log('Continuing with on-demand connection startup...');
    }
}

async function connectToWhatsApp(integrationId) {
    const { state, saveCreds, removeCreds } = await useMySQLAuthState({
        session: integrationId,
        ...MYSQL_CONFIG
    })

    // Store the creds functions for later use
    credsSavers.set(integrationId, { saveCreds, removeCreds })

    const sock = makeWASocket({
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        logger: logger
    })

    console.log('Starting connection for integration:', integrationId)

    let connectionTimeout = setTimeout(async () => {
        console.log('Connection timeout for integration:', integrationId, sock.user)
        if (sock.user == null) {
            sock.ev.removeAllListeners('connection.update')
            sock.ev.removeAllListeners('creds.update')
            sock.ev.removeAllListeners('messages.upsert')
            connections.delete(integrationId)
            connectionPool.connections.delete(integrationId)
            qrCodes.delete(integrationId)

            // Remove MySQL auth data
            const credsHandler = credsSavers.get(integrationId)
            if (credsHandler && credsHandler.removeCreds) {
                try {
                    await credsHandler.removeCreds()
                    console.log('Removed MySQL auth data for integration:', integrationId)
                } catch (error) {
                    console.error('Failed to remove MySQL auth data:', error)
                }
            }
            credsSavers.delete(integrationId)

            sendWebSocketUpdate(integrationId, { status: 'qr_expired' })
        }
    }, 2 * 60 * 1000) // 2 minutes timeout

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update

        if (connection === 'close') {
            if (lastDisconnect.error instanceof Boom) {
                const isLoggedOut = lastDisconnect.error.output.statusCode === DisconnectReason.loggedOut

                if (isLoggedOut) {
                    // Remove MySQL auth data
                    const credsHandler = credsSavers.get(integrationId)
                    if (credsHandler && credsHandler.removeCreds) {
                        try {
                            await credsHandler.removeCreds()
                            console.log('Removed MySQL auth data for logged out integration:', integrationId)
                        } catch (error) {
                            console.error('Failed to remove MySQL auth data:', error)
                        }
                    }
                    credsSavers.delete(integrationId)
                    sendWebSocketUpdate(integrationId, { status: 'disconnected' })
                }

                clearTimeout(connectionTimeout)
                connectToWhatsApp(integrationId);
            }
        } else if (connection === 'open') {
            qrCodes.delete(integrationId) // Clear QR code once connected

            clearTimeout(connectionTimeout) // Clear the timeout when connected

            const phone = sock.user.id.split(':')[0]
            sendWebSocketUpdate(integrationId, { status: 'connected', phone })
        }

        if (qr) {
            qrcode.toDataURL(qr)
                .then(qrImage => {
                    qrCodes.set(integrationId, qrImage)
                    sendWebSocketUpdate(integrationId, { status: 'waiting_for_qr_scan', qr: qrImage })
                })
                .catch(err => console.error('Failed to generate QR code:', err))
        }
    })

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('messages.upsert', async (m) => {
        await handleIncomingMessage(sock, integrationId, m)
    })

    connections.set(integrationId, sock)
}

async function handleIncomingMessage(sock, integrationId, m) {
    const message = m.messages[0]
    if (message.key.fromMe) return // Ignore outgoing messages

    const sender = message.key.remoteJid
    const messageContent = message.message.conversation || message.message.extendedTextMessage?.text || ''

    try {
        // Send read receipt
        await sock.readMessages([message.key])

        // Send typing indicator
        await sock.sendPresenceUpdate('composing', sender)

        const response = await fetch(`${LARAVEL_API_URL}/api/whatsapp/incoming-message`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WhatsApp-Server-Token': WHATSAPP_SERVER_TOKEN
            },
            body: JSON.stringify({
                integrationId,
                sender,
                message: messageContent
            })
        });

        const responseData = await response.json();

        // Logging
        console.log('Incoming Message:', {
            integrationId,
            sender,
            message: messageContent
        });
        console.log('Response:', responseData);

        // Stop typing indicator
        await sock.sendPresenceUpdate('paused', sender)

        if (response.status === 404) return;

        // Send the response back to the sender
        if (responseData.response) {
            await sock.sendMessage(sender, { text: responseData.response })
        } else {
            await sock.sendMessage(sender, { text: 'Terjadi kesalahan! Silakan coba lagi nanti.' })
        }
    } catch (error) {
        console.error('Failed to handle incoming message:', error)
    }
}

function sendWebSocketUpdate(integrationId, data) {
    console.log('sendWebSocketUpdate', integrationId, data)
    return fetch(`${LARAVEL_API_URL}/api/whatsapp/webhook`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WhatsApp-Server-Token': WHATSAPP_SERVER_TOKEN
        },
        body: JSON.stringify({
            integrationId,
            ...data
        })
    })
}

// Use a connection pool for better resource management
const connectionPool = {
    maxConnections: 50,
    connections: new Map(),
    async getConnection(integrationId) {
        if (!this.connections.has(integrationId) && this.connections.size < this.maxConnections) {
            await connectToWhatsApp(integrationId)
            this.connections.set(integrationId, connections.get(integrationId))
        }
        return this.connections.get(integrationId)
    }
}

// API endpoints
app.post('/connect', async (req, res) => {
    const { integrationId } = req.body
    if (!integrationId) {
        return res.status(400).json({ error: 'Integration ID is required' })
    }

    try {
        await connectionPool.getConnection(integrationId)
        res.json({ success: true, message: 'Connection initiated or already exists' })
    } catch (error) {
        res.status(500).json({ error: 'Failed to establish connection' })
    }
})

app.get('/status/:integrationId', async (req, res) => {
    const { integrationId } = req.params
    await connectionPool.getConnection(integrationId)

    setTimeout(async () => {
        const qrCode = qrCodes.get(integrationId)
        const connection = connections.get(integrationId)
        let status = 'disconnected'

        if (connection && connection.user) {
            status = 'connected'
        } else if (qrCode) {
            status = 'waiting_for_qr_scan'
        }

        res.json({
            status,
            qr: qrCode || null
        })
    }, 1000)
})

app.post('/send-message', async (req, res) => {
    const { integrationId, recipient, message } = req.body
    try {
        const sock = await connectionPool.getConnection(integrationId)
        if (!sock) {
            return res.status(404).json({ error: 'Connection not found' })
        }

        await sock.sendMessage(`${recipient}@s.whatsapp.net`, { text: message })
        res.json({ success: true })
    } catch (error) {
        res.status(500).json({ error: 'Failed to send message' })
    }
})

app.post('/disconnect', async (req, res) => {
    const { integrationId } = req.body
    if (!integrationId) {
        return res.status(400).json({ error: 'Integration ID is required' })
    }

    try {
        // Remove MySQL auth data
        const credsHandler = credsSavers.get(integrationId)
        if (credsHandler && credsHandler.removeCreds) {
            try {
                await credsHandler.removeCreds()
                console.log('Removed MySQL auth data for integration:', integrationId)
            } catch (error) {
                console.error('Failed to remove MySQL auth data:', error)
            }
        }
        credsSavers.delete(integrationId)

        const connection = connections.get(integrationId)
        if (connection) {
            connection.logout()
            connection.end(true)
            connections.delete(integrationId)
            connectionPool.connections.delete(integrationId)
        }

        qrCodes.delete(integrationId)

        sendWebSocketUpdate(integrationId, { status: 'disconnected' })

        res.json({ success: true, message: 'Disconnected successfully' })
    } catch (error) {
        res.status(500).json({ error: 'Failed to disconnect' })
    }
})

app.listen(port, async () => {
    console.log(`WhatsApp server listening at http://localhost:${port}`);
    await startAllConnections();
});

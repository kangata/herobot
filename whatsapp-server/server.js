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

async function connectToWhatsApp(channelId) {
    const { state, saveCreds, removeCreds } = await useMySQLAuthState({
        session: channelId,
        ...MYSQL_CONFIG
    })

    // Store the creds functions for later use
    credsSavers.set(channelId, { saveCreds, removeCreds })

    const sock = makeWASocket({
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        logger: logger
    })

    console.log('Starting connection for channel:', channelId)

    let connectionTimeout = setTimeout(async () => {
        console.log('Connection timeout for channel:', channelId, sock.user)
        if (sock.user == null) {
            sock.ev.removeAllListeners('connection.update')
            sock.ev.removeAllListeners('creds.update')
            sock.ev.removeAllListeners('messages.upsert')
            connections.delete(channelId)
            connectionPool.connections.delete(channelId)
            qrCodes.delete(channelId)

            // Remove MySQL auth data
            const credsHandler = credsSavers.get(channelId)
            if (credsHandler && credsHandler.removeCreds) {
                try {
                    await credsHandler.removeCreds()
                    console.log('Removed MySQL auth data for channel:', channelId)
                } catch (error) {
                    console.error('Failed to remove MySQL auth data:', error)
                }
            }
            credsSavers.delete(channelId)

            sendWebSocketUpdate(channelId, { status: 'qr_expired' })
        }
    }, 2 * 60 * 1000) // 2 minutes timeout

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update

        if (connection === 'close') {
            if (lastDisconnect.error instanceof Boom) {
                const isLoggedOut = lastDisconnect.error.output.statusCode === DisconnectReason.loggedOut

                if (isLoggedOut) {
                    // Remove MySQL auth data
                    const credsHandler = credsSavers.get(channelId)
                    if (credsHandler && credsHandler.removeCreds) {
                        try {
                            await credsHandler.removeCreds()
                            console.log('Removed MySQL auth data for logged out channel:', channelId)
                        } catch (error) {
                            console.error('Failed to remove MySQL auth data:', error)
                        }
                    }
                    credsSavers.delete(channelId)
                    sendWebSocketUpdate(channelId, { status: 'disconnected' })
                }

                clearTimeout(connectionTimeout)
                connectToWhatsApp(channelId);
            }
        } else if (connection === 'open') {
            qrCodes.delete(channelId) // Clear QR code once connected

            clearTimeout(connectionTimeout) // Clear the timeout when connected

            const phone = sock.user.id.split(':')[0]
            sendWebSocketUpdate(channelId, { status: 'connected', phone })
        }

        if (qr) {
            qrcode.toDataURL(qr)
                .then(qrImage => {
                    qrCodes.set(channelId, qrImage)
                    sendWebSocketUpdate(channelId, { status: 'waiting_for_qr_scan', qr: qrImage })
                })
                .catch(err => console.error('Failed to generate QR code:', err))
        }
    })

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('messages.upsert', async (m) => {
        await handleIncomingMessage(sock, channelId, m)
    })

    connections.set(channelId, sock)
}

async function handleIncomingMessage(sock, channelId, m) {
    const message = m.messages[0]
    if (message.key.fromMe) return // Ignore outgoing messages

    const sender = message.key.remoteJid

    // If it's a group chat, we need to check if the bot was mentioned
    if (sender?.endsWith('@g.us')) {
        const botJid = sock.user.id.replace(/:\d+/, "");

        // 1. Check if the bot was mentioned
        const mentionedJids = message.message?.extendedTextMessage?.contextInfo?.mentionedJid
            || msg?.message?.extendedTextMessage?.contextInfo?.participant
            || [];
        const isBotMentioned = mentionedJids.includes(botJid);

        // 2. Check if the message is a reply to the bot's own message
        const quotedMessage = message.message?.extendedTextMessage?.contextInfo?.quotedMessage;
        const quotedParticipant = message.message?.extendedTextMessage?.contextInfo?.participant; // JID of the sender of the quoted message

        // A message is a reply to the bot if it quotes a message and the quoted message's sender is the bot
        const isReplyingToBotMessage = quotedMessage && quotedParticipant === botJid;

        if (!isBotMentioned && !isReplyingToBotMessage) {
            console.log(`[Group Message] Bot not mentioned and not replied to in group chat (${sender}). Ignoring message.`);
            return;
        } else if (isBotMentioned) {
            console.log(`[Group Message] Bot was mentioned in group chat (${sender}). Processing message.`);
        } else if (isReplyingToBotMessage) {
            console.log(`[Group Message] Bot's message was replied to in group chat (${sender}). Processing message.`);
        }
    }

    // get message from several source
    const messageContent =
        message.message?.conversation ||
        message.message?.extendedTextMessage?.text ||
        message.message?.imageMessage?.caption ||
        message.message?.videoMessage?.caption ||
        message.message?.documentMessage?.caption ||
        ''

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
                channelId,
                sender,
                message: messageContent
            })
        });

        const responseData = await response.json();

        // Logging
        console.log('Incoming Message:', {
            channelId,
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

function sendWebSocketUpdate(channelId, data) {
    console.log('sendWebSocketUpdate', channelId, data)
    return fetch(`${LARAVEL_API_URL}/api/whatsapp/webhook`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WhatsApp-Server-Token': WHATSAPP_SERVER_TOKEN
        },
        body: JSON.stringify({
            channelId,
            ...data
        })
    })
}

// Use a connection pool for better resource management
const connectionPool = {
    maxConnections: 50,
    connections: new Map(),
    async getConnection(channelId) {
        if (!this.connections.has(channelId) && this.connections.size < this.maxConnections) {
            await connectToWhatsApp(channelId)
            this.connections.set(channelId, connections.get(channelId))
        }
        return this.connections.get(channelId)
    }
}

// API endpoints
app.post('/connect', async (req, res) => {
    const { channelId } = req.body
    if (!channelId) {
        return res.status(400).json({ error: 'channel ID is required' })
    }

    try {
        await connectionPool.getConnection(channelId)
        res.json({ success: true, message: 'Connection initiated or already exists' })
    } catch (error) {
        res.status(500).json({ error: 'Failed to establish connection' })
    }
})

app.get('/status/:channelId', async (req, res) => {
    const { channelId } = req.params
    await connectionPool.getConnection(channelId)

    setTimeout(async () => {
        const qrCode = qrCodes.get(channelId)
        const connection = connections.get(channelId)
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
    const { channelId, recipient, message } = req.body
    try {
        const sock = await connectionPool.getConnection(channelId)
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
    const { channelId } = req.body
    if (!channelId) {
        return res.status(400).json({ error: 'channel ID is required' })
    }

    try {
        // Remove MySQL auth data
        const credsHandler = credsSavers.get(channelId)
        if (credsHandler && credsHandler.removeCreds) {
            try {
                await credsHandler.removeCreds()
                console.log('Removed MySQL auth data for channel:', channelId)
            } catch (error) {
                console.error('Failed to remove MySQL auth data:', error)
            }
        }
        credsSavers.delete(channelId)

        const connection = connections.get(channelId)
        if (connection) {
            connection.logout()
            connection.end(true)
            connections.delete(channelId)
            connectionPool.connections.delete(channelId)
        }

        qrCodes.delete(channelId)

        sendWebSocketUpdate(channelId, { status: 'disconnected' })

        res.json({ success: true, message: 'Disconnected successfully' })
    } catch (error) {
        res.status(500).json({ error: 'Failed to disconnect' })
    }
})

app.listen(port, async () => {
    console.log(`WhatsApp server listening at http://localhost:${port}`);
    await startAllConnections();
});

const { default: makeWASocket, DisconnectReason, useMultiFileAuthState } = require('@whiskeysockets/baileys')
const express = require('express')
const { Boom } = require('@hapi/boom')
const qrcode = require('qrcode')
const fs = require('fs')
const path = require('path')
const pino = require('pino')

const app = express()
const port = 3000

const logger = pino({
    level: 'error'
});

app.use(express.json())

const connections = new Map()
const qrCodes = new Map()

const storagePath = process.argv[2] || path.join(__dirname, 'auth_info_baileys');

async function connectToWhatsApp(integrationId) {
    const authFolder = path.join(storagePath, integrationId);
    if (!fs.existsSync(authFolder)) {
        fs.mkdirSync(authFolder, { recursive: true });
    }

    const { state, saveCreds } = await useMultiFileAuthState(authFolder)
    
    const sock = makeWASocket({
        auth: state,
        logger: logger
    })

    let connectionTimeout = setTimeout(() => {
        if (sock.user == null) {
            sock.ev.removeAllListeners('connection.update')
            sock.ev.removeAllListeners('creds.update')
            sock.ev.removeAllListeners('messages.upsert')
            connections.delete(integrationId)
            connectionPool.connections.delete(integrationId)
            qrCodes.delete(integrationId)
        }
    }, 120000) // 2 minutes timeout

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update

        if (connection === 'close') {
            if (lastDisconnect.error instanceof Boom) {
                const isLoggedOut = lastDisconnect.error.output.statusCode === DisconnectReason.loggedOut

                if (isLoggedOut) {
                    const authFolder = path.join(storagePath, integrationId);
                    if (fs.existsSync(authFolder)) {
                        fs.rmSync(authFolder, { recursive: true, force: true });
                    }
                    clearTimeout(connectionTimeout)
                    sendWebSocketUpdate(integrationId, { status: 'disconnected' })
                }

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
                    sendWebSocketUpdate(integrationId, { qr: qrImage })
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

        const response = await fetch('http://localhost:80/api/whatsapp/incoming-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
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
        console.log('Response:', responseData.response);

        // Stop typing indicator
        await sock.sendPresenceUpdate('paused', sender)

        // Send the response back to the sender
        await sock.sendMessage(sender, { text: responseData.response })
    } catch (error) {
        console.error('Failed to handle incoming message:', error)
    }
}

function sendWebSocketUpdate(integrationId, data) {
    console.log('sendWebSocketUpdate', integrationId, data)
    return fetch('http://localhost:80/api/whatsapp-webhook', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
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
        const authFolder = path.join(storagePath, integrationId);
        if (fs.existsSync(authFolder)) {
            fs.rmSync(authFolder, { recursive: true, force: true });
        }

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

app.listen(port, () => {
    console.log(`WhatsApp server listening at http://localhost:${port}`)
})
const { default: makeWASocket, DisconnectReason, useMultiFileAuthState } = require('@whiskeysockets/baileys')
const express = require('express')
const { Boom } = require('@hapi/boom')
const qrcode = require('qrcode')
const fs = require('fs')
const path = require('path')

const app = express()
const port = 3000

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
        printQRInTerminal: true
    })

    let connectionTimeout = setTimeout(() => {
        if (sock.user == null) {
            console.log(`Connection timeout for ${integrationId}`)
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
            const shouldReconnect = (lastDisconnect.error instanceof Boom &&
                lastDisconnect.error.output.statusCode !== DisconnectReason.loggedOut)
            if (shouldReconnect) {
                connectToWhatsApp(integrationId)
            }
        } else if (connection === 'open') {
            console.log(`Connected to WhatsApp for ${integrationId}`)
            qrCodes.delete(integrationId) // Clear QR code once connected
            clearTimeout(connectionTimeout) // Clear the timeout when connected
            sendWebSocketUpdate(integrationId, { status: 'connected' })
        }

        if (qr) {
            // Generate QR code image asynchronously
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
        console.log(`New message for ${integrationId}:`, JSON.stringify(m, undefined, 2))
        // Handle incoming messages here
    })

    connections.set(integrationId, sock)
}

function sendWebSocketUpdate(integrationId, data) {
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
    .then(response => response.text().then(text => console.log('WebSocket Update sent to Laravel:', text)))
    .catch(error => console.error('Failed to send update to Laravel:', error));
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

app.get('/qr/:integrationId', async (req, res) => {
    const { integrationId } = req.params
    await connectionPool.getConnection(integrationId)

    setTimeout(async () => {
        const qrCode = qrCodes.get(integrationId)
        if (qrCode) {
            res.json({ data: qrCode })
        } else {
            res.status(404).json({ error: 'QR code not available' })
        }
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

app.listen(port, () => {
    console.log(`WhatsApp server listening at http://localhost:${port}`)
})
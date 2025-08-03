const express = require('express');
const { connectionPool, connections, qrCodes, credsSavers, sendWebSocketUpdate } = require('../services/whatsapp.service');

const router = express.Router();

// API endpoints
router.post('/connect', async (req, res) => {
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

router.get('/status/:channelId', async (req, res) => {
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

router.post('/send-message', async (req, res) => {
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

router.post('/disconnect', async (req, res) => {
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

module.exports = router;
const { default: makeWASocket, DisconnectReason, makeCacheableSignalKeyStore, downloadMediaMessage } = require('@whiskeysockets/baileys');
const { useMySQLAuthState } = require('mysql-baileys');
const { Boom } = require('@hapi/boom');
const qrcode = require('qrcode');
const pino = require('pino');
const FormData = require('form-data');
const fetch = require('node-fetch');
const config = require('../config');

const logger = pino({ level: config.logger.level });

const connections = new Map();
const qrCodes = new Map();
const credsSavers = new Map();

// Helper function to get file extension from MIME type
function getFileExtension(mimeType) {
    const mimeToExt = {
        'image/jpeg': 'jpg',
        'image/jpg': 'jpg',
        'image/png': 'png',
        'image/gif': 'gif',
        'image/webp': 'webp',
        'audio/mp3': 'mp3',
        'audio/mpeg': 'mp3',
        'audio/wav': 'wav',
        'audio/ogg': 'ogg',
        'audio/m4a': 'm4a',
        'audio/webm': 'webm',
        'audio/flac': 'flac',
        'video/mp4': 'mp4',
        'video/avi': 'avi',
        'video/mov': 'mov',
        'video/webm': 'webm',
        'application/pdf': 'pdf',
        'application/msword': 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'docx',
        'text/plain': 'txt'
    };

    return mimeToExt[mimeType] || 'bin';
}

async function connectToWhatsApp(channelId) {
    const { state, saveCreds, removeCreds } = await useMySQLAuthState({
        session: channelId,
        ...config.db
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
            || message?.message?.extendedTextMessage?.contextInfo?.participant
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

    // Utility to extract media info
    const getMediaInfo = (msg) => {
        const types = [
            { key: 'imageMessage', label: 'image' },
            { key: 'audioMessage', label: 'audio' },
            { key: 'videoMessage', label: 'video' },
            { key: 'documentMessage', label: 'document' }
        ];
        for (const type of types) {
            if (msg[type.key]) {
                return {
                    type: type.label,
                    content: msg[type.key],
                    key: type.key
                };
            }
        }
        return null;
    };

    let mediaBuffer = null;
    let mediaMimeType = null;

    // Prefer text content in order of: conversation, extendedText, caption (image/video/document)
    const messageContent =
        message.message?.conversation ??
        message.message?.extendedTextMessage?.text ??
        message.message?.imageMessage?.caption ??
        message.message?.videoMessage?.caption ??
        message.message?.documentMessage?.caption ??
        '';

    try {
        // Handle media messages in a DRY way
        const mediaInfo = getMediaInfo(message.message || {});
        if (mediaInfo) {
            try {
                mediaBuffer = await downloadMediaMessage(
                    message,
                    'buffer',
                    {},
                    {
                        logger,
                        reuploadRequest: sock.updateMediaMessage
                    }
                );
                mediaMimeType = mediaInfo.content.mimetype;
            } catch (error) {
                console.error(`Error downloading ${mediaInfo.type}:`, error);
            }
        }

        // Send read receipt and typing indicator in parallel for speed
        await Promise.all([
            sock.readMessages([message.key]),
            sock.sendPresenceUpdate('composing', sender)
        ]);

        // Prepare form data for multipart request
        const formData = new FormData();
        formData.append('channelId', channelId);
        formData.append('sender', sender);
        formData.append('message', messageContent);

        // Add media file if present
        if (mediaBuffer && mediaMimeType) {
            const extension = getFileExtension(mediaMimeType);
            const fileName = `media_${Date.now()}.${extension}`;
            formData.append('media_file', mediaBuffer, {
                filename: fileName,
                contentType: mediaMimeType
            });
        }

        const response = await fetch(`${config.laravelApiUrl}/api/whatsapp/incoming-message`, {
            method: 'POST',
            headers: {
                'X-WhatsApp-Server-Token': config.serverToken,
                ...formData.getHeaders()
            },
            body: formData
        });

        const responseData = await response.json();

        // Stop typing indicator
        await sock.sendPresenceUpdate('paused', sender);

        if (response.status === 404) return;

        // Send the response back to the sender
        if (responseData?.response) {
            await sock.sendMessage(sender, { text: responseData.response });
        }
    } catch (error) {
        console.error('Failed to handle incoming message:', error)
    }
}

function sendWebSocketUpdate(channelId, data) {
    console.log('sendWebSocketUpdate', channelId, data)
    return fetch(`${config.laravelApiUrl}/api/whatsapp/webhook`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WhatsApp-Server-Token': config.serverToken
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

async function startAllConnections() {
    console.log('MySQL-based authentication initialized. Checking for existing sessions...');

    try {
        // Get all existing sessions from MySQL
        const mysql = require('mysql2/promise');
        const connection = await mysql.createConnection({
            host: config.db.host,
            port: config.db.port,
            user: config.db.user,
            password: config.db.password,
            database: config.db.database
        });

        // Create auth table if it doesn't exist
        const tableName = config.db.tableName;

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

module.exports = {
    connectToWhatsApp,
    handleIncomingMessage,
    sendWebSocketUpdate,
    startAllConnections,
    connectionPool,
    connections,
    qrCodes,
    credsSavers,
    getFileExtension
};
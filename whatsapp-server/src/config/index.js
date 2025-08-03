const dotenv = require('dotenv');
const path = require('path');

// Load .env file from the root of the project, not from whatsapp-server
dotenv.config({ path: path.resolve(__dirname, '../../../.env') });

module.exports = {
    port: process.env.WHATSAPP_PORT || 3000,
    laravelApiUrl: process.env.WHATSAPP_LARAVEL_URL || 'http://localhost',
    serverToken: process.env.WHATSAPP_SERVER_TOKEN,
    db: {
        host: process.env.WHATSAPP_DB_HOST || 'localhost',
        port: parseInt(process.env.WHATSAPP_DB_PORT, 10) || 3306,
        user: process.env.WHATSAPP_DB_USER || 'root',
        password: process.env.WHATSAPP_DB_PASSWORD,
        database: process.env.WHATSAPP_DB_DATABASE || 'whatsapp',
        tableName: process.env.WHATSAPP_DB_TABLE_NAME || 'auth',
        retryRequestDelayMs: parseInt(process.env.WHATSAPP_DB_RETRY_DELAY, 10) || 200,
        maxRetries: parseInt(process.env.WHATSAPP_DB_MAX_RETRIES, 10) || 10,
    },
    logger: {
        level: 'error',
    },
};
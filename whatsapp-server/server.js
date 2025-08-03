const express = require('express');
const config = require('./src/config');
const apiRoutes = require('./src/routes/api.routes');
const { startAllConnections } = require('./src/services/whatsapp.service');

const app = express();

app.use(express.json());

app.use('/', apiRoutes);

app.listen(config.port, async () => {
    console.log(`WhatsApp server listening at http://localhost:${config.port}`);
    await startAllConnections();
});

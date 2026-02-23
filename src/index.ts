import express from 'express';
import bodyParser from 'body-parser';
import dotenv from 'dotenv';
import { handleMessage } from './bot';
import { getAdminDashboard, broadcastMessage } from './admin';
import { runCron } from './cron';

dotenv.config();

const app = express();
app.use(bodyParser.urlencoded({ extended: true }));
const port = process.env.PORT || 3000;

app.use(bodyParser.json());

app.get('/webhook', (req, res) => {
    const mode = req.query['hub.mode'];
    const token = req.query['hub.verify_token'];
    const challenge = req.query['hub.challenge'];

    if (mode && token) {
        if (mode === 'subscribe' && token === process.env.VERIFY_TOKEN) {
            console.log('WEBHOOK_VERIFIED');
            res.status(200).send(challenge);
        } else {
            res.sendStatus(403);
        }
    }
});

app.post('/webhook', (req, res) => {
    const body = req.body;
    if (body.object === 'page') {
        body.entry.forEach((entry: any) => {
            entry.messaging.forEach((event: any) => {
                if (event.message || event.postback) {
                    handleMessage(event.sender.id, event).catch(err => {
                        console.error('Error handling message:', err);
                    });
                }
            });
        });
        res.status(200).send('EVENT_RECEIVED');
    } else {
        res.sendStatus(404);
    }
});

app.get('/', (req, res) => {
    res.send('Islamic AI Assistant is running.');
});

app.get('/admin', async (req, res) => {
    if (req.query.password !== process.env.ADMIN_PASSWORD) {
        return res.status(403).send('Unauthorized');
    }
    const html = await getAdminDashboard();
    res.send(html);
});

app.post('/admin/broadcast', async (req, res) => {
    const { message } = req.body;
    const count = await broadcastMessage(message);
    res.send(`Broadcast sent to \${count} users. <a href="/admin?password=\${process.env.ADMIN_PASSWORD}">Back</a>`);
});

app.get('/cron', async (req, res) => {
    // Basic protection
    if (req.query.token !== process.env.ADMIN_PASSWORD) {
        return res.status(403).send('Unauthorized');
    }
    await runCron();
    res.send('Cron executed');
});

app.listen(port, () => {
    console.log(`Server is listening on port \${port}`);
});

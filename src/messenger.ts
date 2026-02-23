import axios from 'axios';
import dotenv from 'dotenv';

dotenv.config();

const PAGE_ACCESS_TOKEN = process.env.PAGE_ACCESS_TOKEN;
const FACEBOOK_API_URL = 'https://graph.facebook.com/v21.0/me/messages';

export async function sendTextMessage(psid: string, text: string, quickReplies: any[] = []) {
    const payload: any = {
        recipient: { id: psid },
        message: { text }
    };
    if (quickReplies.length > 0) {
        payload.message.quick_replies = quickReplies.map(qr => ({
            content_type: 'text',
            title: qr.title,
            payload: qr.payload
        }));
    }
    return await callFacebookAPI(payload);
}

export async function sendImage(psid: string, imageUrl: string) {
    const payload = {
        recipient: { id: psid },
        message: { attachment: { type: 'image', payload: { url: imageUrl, is_reusable: true } } }
    };
    return await callFacebookAPI(payload);
}

export async function sendButtonTemplate(psid: string, text: string, buttons: any[]) {
    const payload = {
        recipient: { id: psid },
        message: { attachment: { type: 'template', payload: { template_type: 'button', text, buttons } } }
    };
    return await callFacebookAPI(payload);
}

export async function sendTypingIndicator(psid: string, isOn: boolean) {
    const payload = { recipient: { id: psid }, sender_action: isOn ? 'typing_on' : 'typing_off' };
    return await callFacebookAPI(payload);
}

async function callFacebookAPI(payload: any) {
    try {
        const response = await axios.post(\`\${FACEBOOK_API_URL}?access_token=\${PAGE_ACCESS_TOKEN}\`, payload);
        return response.data;
    } catch (error: any) {
        console.error('Facebook API Error:', error.response?.data || error.message);
        throw error;
    }
}

export function getDefaultQuickReplies() {
    return [
        { title: '◇مواقيت الصلاة', payload: 'PRAYER_TIMES' },
        { title: '◇الأذكار', payload: 'ADHIKAR' },
        { title: '◇إبلاغ عن خطأ', payload: 'REPORT_ISSUE' },
        { title: '◇المطور', payload: 'DEVELOPER_INFO' }
    ];
}

export function getBackToAIQuickReply() {
    return [{ title: 'رجوع ❌', payload: 'BACK_TO_AI' }];
}

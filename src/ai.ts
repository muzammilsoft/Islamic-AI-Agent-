import axios from 'axios';
import { tools, executeTool } from './tools';
import dotenv from 'dotenv';

dotenv.config();

const POLLINATIONS_API_URL = 'https://gen.pollinations.ai/v1/chat/completions';
const API_KEY = process.env.POLLINATIONS_API_KEY;

const SYSTEM_PROMPT = `
You are an Islamic AI Assistant (iAi). Your goal is to help Muslims in their daily lives by answering questions based on reliable Islamic sources (Quran, Sunnah).
You have access to several tools to fetch accurate information.
Instructions:
1. If a user's question requires information from a tool, you MUST respond with a JSON array of tool calls.
2. Example: [{"tool": "getPrayerTimes", "city": "Khartoum", "country": "Sudan"}]
3. If no tool is needed, respond in Arabic.
4. Prioritize Quran and Sunnah.
5. If tool results are provided, synthesize them into a final answer in Arabic.
`;

export async function callAI(history: any[], userMessage: string, toolResults: any[] = []) {
    const messages: any[] = [{ role: 'system', content: SYSTEM_PROMPT }, ...history, { role: 'user', content: userMessage }];
    if (toolResults.length > 0) {
        messages.push({ role: 'assistant', content: \`Tool Results: \${JSON.stringify(toolResults)}\` });
        messages.push({ role: 'user', content: 'Please provide the final answer based on these results in Arabic.' });
    }
    try {
        const response = await axios.post(POLLINATIONS_API_URL, { model: 'openai', messages, temperature: 0.7 }, { headers: { 'Authorization': API_KEY ? \`Bearer \${API_KEY}\` : undefined, 'Content-Type': 'application/json' } });
        return response.data.choices[0].message.content;
    } catch (error) { return null; }
}

export async function processAiFlow(psid: string, userMessage: string, history: any[], onStatusUpdate: (msg: string) => Promise<void>) {
    let response = await callAI(history, userMessage);
    if (!response) return "عذراً، حدث خطأ في التواصل مع الذكاء الاصطناعي.";
    const toolCallRegex = /\\[\\s*\\{.*"tool":\\s*".*"\\s*\\}\\s*\\]/s;
    const match = response.match(toolCallRegex);
    if (match) {
        try {
            const toolCalls = JSON.parse(match[0]);
            const results = [];
            for (const call of toolCalls) {
                await onStatusUpdate(\`جاري طلب أداة \${call.tool} 🛠...\`);
                const result = await executeTool(call.tool, call);
                results.push({ tool: call.tool, result });
            }
            await onStatusUpdate(\`إرسال نتائج الأدوات إلى النموذج...\`);
            return await callAI(history, userMessage, results) || "عذراً، فشلت في تجميع الرد النهائي.";
        } catch (e) { return response; }
    }
    return response;
}

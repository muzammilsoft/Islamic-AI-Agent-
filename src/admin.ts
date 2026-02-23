import { getAllUsers, getAllComplaints } from './database';
import { sendTextMessage } from './messenger';

export async function getAdminDashboard() {
    const users = await getAllUsers();
    const complaints = await getAllComplaints();
    return \`
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head><meta charset="UTF-8"><title>لوحة تحكم iAi</title><style>body { font-family: sans-serif; background: #f4f4f4; margin: 20px; } .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; } table { width: 100%; border-collapse: collapse; } th, td { text-align: right; padding: 10px; border-bottom: 1px solid #ddd; }</style></head>
    <body>
        <h1>لوحة تحكم iAi 🤖</h1>
        <div class="card"><h2>إحصائيات</h2><p>المستخدمين: \${users.length}</p><p>الشكاوى: \${complaints.length}</p></div>
        <div class="card"><h2>Broadcast</h2><form action="/admin/broadcast" method="POST"><textarea name="message" required></textarea><br><button type="submit">إرسال</button></form></div>
        <div class="card"><h2>المستخدمين</h2><table><thead><tr><th>PSID</th><th>المدينة</th><th>آخر ظهور</th></tr></thead><tbody>\${users.map((u: any) => \`<tr><td>\${u.psid}</td><td>\${u.city}</td><td>\${u.last_seen}</td></tr>\`).join('')}</tbody></table></div>
    </body></html>\`;
}

export async function broadcastMessage(message: string) {
    const users = await getAllUsers();
    let count = 0;
    for (const user of users) {
        try { await sendTextMessage(user.psid, message); count++; } catch (e) {}
    }
    return count;
}

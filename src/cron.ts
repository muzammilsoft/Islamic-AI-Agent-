import { getAllUsers } from './database';
import { sendTextMessage } from './messenger';
import axios from 'axios';

export async function runCron() {
    const users = await getAllUsers();
    const now = new Date();
    const khartoumTime = new Date(now.getTime() + (2 * 60 * 60 * 1000));
    const currentHHmm = khartoumTime.toISOString().split('T')[1].substring(0, 5);
    const date = khartoumTime.toISOString().split('T')[0].split('-').reverse().join('-');

    for (const user of users) {
        if (!user.city && !user.lat) continue;
        try {
            let url = user.lat ? \`https://api.aladhan.com/v1/timings/\${date}?latitude=\${user.lat}&longitude=\${user.lng}&method=8\` : \`https://api.aladhan.com/v1/timingsByCity/\${date}?city=\${user.city}&country=\${user.country}&method=8\`;
            const response = await axios.get(url);
            const timings = response.data.data.timings;
            const prayers: any = { 'Fajr': 'الفجر', 'Dhuhr': 'الظهر', 'Asr': 'العصر', 'Maghrib': 'المغرب', 'Isha': 'العشاء' };
            for (const p in prayers) {
                if (timings[p] === currentHHmm) {
                    await sendTextMessage(user.psid, \`حي على الصلاة! حان الآن موعد أذان \${prayers[p]}.\`);
                }
            }
        } catch (e) {}
    }
}

import { getUser, createUser, updateUser, addHistory, getHistory, saveComplaint, resetSystem } from './database';
import { sendTextMessage, sendImage, sendButtonTemplate, sendTypingIndicator, getDefaultQuickReplies, getBackToAIQuickReply } from './messenger';
import { processAiFlow } from './ai';
import { validateCityCountry } from './tools';

export async function handleMessage(psid: string, event: any) {
    let user = await getUser(psid);
    if (!user) {
        user = await createUser(psid);
        await sendWelcomeMessage(psid);
    }

    if (event.message && event.message.quick_reply) {
        await handlePayload(psid, event.message.quick_reply.payload);
        return;
    }

    if (event.postback) {
        await handlePayload(psid, event.postback.payload);
        return;
    }

    if (event.message && event.message.attachments) {
        for (const attachment of event.message.attachments) {
            if (attachment.type === 'location') {
                const { lat, long } = attachment.payload.coordinates;
                await handleLocationUpdate(psid, lat, long);
                return;
            }
        }
    }

    if (event.message && event.message.text) {
        const text = event.message.text;

        if (psid === process.env.ADMIN_PSID && text.startsWith('/')) {
            await handleAdminCommand(psid, text);
            return;
        }

        if (user.state === 'awaiting_complaint') {
            await handleComplaintSubmission(psid, text);
            return;
        }

        if (user.state === 'awaiting_city') {
            await handleCityInput(psid, text);
            return;
        }

        await handleAiInteraction(psid, text);
    }
}

async function sendWelcomeMessage(psid: string) {
    const welcomeText = "مرحباً بك في المساعد الإسلامي (iAi)!\n\nأنا هنا لمساعدتك في الإجابة على أسئلتك الدينية، وتوفير مواقيت الصلاة، والمزيد. \n\nتذكر دائماً أن هذا البوت هو أداة مساعدة، ويجب التحقق من الإجابات الفقهية الهامة من مصادر موثوقة. \n\nشارك البوت مع أصدقائك لتعم الفائدة وتكون صدقة جارية في ميزان حسناتك.";
    await sendTextMessage(psid, welcomeText);
    await sendImage(psid, 'https://via.placeholder.com/800x400.png?text=Islamic+AI+Assistant');
    await sendTextMessage(psid, "يمكنك استخدام الأزرار في الأسفل للوصول إلى الميزات الرئيسية أو ابدأ بالكتابة مباشرة للتحدث معي.", getDefaultQuickReplies());
}

async function handlePayload(psid: string, payload: string) {
    switch (payload) {
        case 'PRAYER_TIMES':
            await handlePrayerTimesFlow(psid);
            break;
        case 'ADHIKAR':
            await sendTextMessage(psid, "هذا القسم قيد التطوير حالياً.", getBackToAIQuickReply());
            break;
        case 'REPORT_ISSUE':
            await updateUser(psid, { state: 'awaiting_complaint' });
            await sendTextMessage(psid, "يسرنا سماع اقتراحاتك أو يؤسفنا وجود مشكلة. يرجى كتابة رسالة مفصلة حول المشكلة أو الخطأ الفقهي الذي تريد تصحيحه. سيتم إرسالها مباشرة إلى المطور.");
            break;
        case 'DEVELOPER_INFO':
            await handleDeveloperInfo(psid);
            break;
        case 'BACK_TO_AI':
            await updateUser(psid, { state: 'default' });
            await sendTextMessage(psid, "لقد عدنا إلى وضع المساعد الذكي. كيف يمكنني مساعدتك؟", getDefaultQuickReplies());
            break;
        case 'CHANGE_CITY':
            await updateUser(psid, { state: 'awaiting_city' });
            await sendTextMessage(psid, "الرجاء إدخال اسم المدينة والدولة باللغة الإنجليزية، مفصولة بفاصلة. مثال: Khartoum, Sudan", getBackToAIQuickReply());
            break;
        case 'GPS_LOCATION':
            await sendTextMessage(psid, "لتحديد موقعك بدقة، يرجى الضغط على علامة (+) في الأسفل ثم اختيار 'الموقع' (Location) وإرساله.\n\nبعد إرسال الموقع، سأقوم بتحديث مواقيت الصلاة بناءً عليه.", getBackToAIQuickReply());
            break;
        case 'CONFIRM_LOCATION':
            await sendTextMessage(psid, "تم تأكيد حفظ الموقع بنجاح. ستتلقى تنبيهات الصلاة بناءً على هذا الموقع.", getDefaultQuickReplies());
            break;
        default:
            await sendTextMessage(psid, "أعتذر، لم أتعرف على هذا الإجراء.", getDefaultQuickReplies());
    }
}

async function handleAiInteraction(psid: string, text: string) {
    await sendTypingIndicator(psid, true);
    const history = await getHistory(psid);
    const finalAnswer = await processAiFlow(psid, text, history, async (status) => {
        await sendTextMessage(psid, status, []);
        await sendTypingIndicator(psid, true);
    });
    await sendTextMessage(psid, finalAnswer, getDefaultQuickReplies());
    await sendTypingIndicator(psid, false);
    await addHistory(psid, 'user', text);
    await addHistory(psid, 'assistant', finalAnswer);
}

async function handlePrayerTimesFlow(psid: string) {
    const user = await getUser(psid);
    const city = user.city || 'Nyala';
    const country = user.country || 'Sudan';
    const result = await validateCityCountry(city, country);
    if (result.valid) {
        const timings = result.data.timings;
        const date = result.data.date.readable;
        const responseText = `🕋 مواقيت الصلاة لمدينة: \${city}, \${country}\n🗓️ \${date}\n\n`
            + `الفجر: \${timings.Fajr}\n`
            + `الشروق: \${timings.Sunrise}\n`
            + `الظهر: \${timings.Dhuhr}\n`
            + `العصر: \${timings.Asr}\n`
            + `المغرب: \${timings.Maghrib}\n`
            + `العشاء: \${timings.Isha}`;
        const quickReplies = [
            { title: 'تغيير المدينة', payload: 'CHANGE_CITY' },
            { title: 'تحديد بالـGPS', payload: 'GPS_LOCATION' },
            { title: 'رجوع ❌', payload: 'BACK_TO_AI' }
        ];
        await sendTextMessage(psid, responseText, quickReplies);
    } else {
        await sendTextMessage(psid, "عذراً، لم أتمكن من جلب مواقيت الصلاة حالياً.", getBackToAIQuickReply());
    }
}

async function handleLocationUpdate(psid: string, lat: number, lng: number) {
    await sendTypingIndicator(psid, true);
    await updateUser(psid, { lat, lng, state: 'default' });
    await sendTextMessage(psid, "تم تحديث موقعك بنجاح. هل تريد حفظ هذا الموقع لتلقي تنبيهات مواعيد الصلاة؟", [
        { title: 'تأكيد ✅', payload: 'CONFIRM_LOCATION' },
        { title: 'رجوع ❌', payload: 'BACK_TO_AI' }
    ]);
}

async function handleCityInput(psid: string, text: string) {
    const parts = text.split(',');
    if (parts.length < 2) {
        await sendTextMessage(psid, "يرجى إدخال اسم المدينة والدولة مفصولين بفاصلة. مثال: Khartoum, Sudan");
        return;
    }
    const city = parts[0].trim();
    const country = parts[1].trim();
    await sendTypingIndicator(psid, true);
    const result = await validateCityCountry(city, country);
    if (result.valid) {
        await updateUser(psid, { city, country, state: 'default' });
        await sendTextMessage(psid, \`تم تعيين \${city}, \${country} كمدينتك الافتراضية.\`, getDefaultQuickReplies());
        await handlePrayerTimesFlow(psid);
    } else {
        await sendTextMessage(psid, \`عذراً، لم أتمكن من العثور على المدينة "\${city}". يرجى التأكد من التهجئة الصحيحة بالإنجليزية.\`);
    }
    await sendTypingIndicator(psid, false);
}

async function handleComplaintSubmission(psid: string, text: string) {
    await saveComplaint(psid, text);
    await updateUser(psid, { state: 'default' });
    if (process.env.ADMIN_PSID) {
        await sendTextMessage(process.env.ADMIN_PSID, \`شكوى جديدة من المستخدم (\${psid}):\n\n"\${text}"\`);
    }
    await sendTextMessage(psid, "شكراً لك. تم إرسال رسالتك إلى المطور بنجاح. سنعود الآن إلى الوضع الرئيسي.", getDefaultQuickReplies());
}

async function handleDeveloperInfo(psid: string) {
    const devInfoText = "المطور: مزمل يحيى (KG) Khartoum Ghoul\n"
                 + "وصف المطور: مطور تطبيقات و تطبيقات ويب.\n"
                 + "المشروع: المساعد الإسلامي - Islamic AI Assistant iAi\n"
                 + "الإصدار: 1.0v 25/10/2025\n"
                 + "وصف المشروع: بوت فيسبوك ماسنجر يساعد المسلم في تعلم أمور دينه و يحثه على المحافظة عليها بالإجابة على أسئلته بالذكاء الاصطناعي، بناء على مصادر موثوقة منها الكتاب و السنة و منها مواقيت الصلاة.";
    const buttons = [{ type: 'web_url', url: 'https://www.facebook.com/khartoum.ghoul', title: 'تواصل مع المطور' }];
    await sendButtonTemplate(psid, devInfoText, buttons);
    await sendTextMessage(psid, "اضغط على 'رجوع' للعودة إلى القائمة الرئيسية.", getBackToAIQuickReply());
}

async function handleAdminCommand(psid: string, text: string) {
    const parts = text.split(' ');
    const command = parts[0].toLowerCase();
    const args = parts.slice(1).join(' ');
    switch (command) {
        case '/help':
            const helpMsg = "🤖 أوامر المدير:\n" + "/help - عرض هذه الرسالة\n" + "/broadcast <message> - إرسال رسالة لجميع المستخدمين\n" + "/reset - تفريغ الشكاوى وسجل المحادثات";
            await sendTextMessage(psid, helpMsg);
            break;
        case '/broadcast':
            if (!args) { await sendTextMessage(psid, "⚠️ استخدام: /broadcast <الرسالة>"); return; }
            const { broadcastMessage } = require('./admin');
            const count = await broadcastMessage(args);
            await sendTextMessage(psid, \`✅ تم الإرسال إلى \${count} مستخدم.\`);
            break;
        case '/reset':
            await resetSystem();
            await sendTextMessage(psid, "✅ تم تصفير النظام (الشكاوى والسجلات).");
            break;
        default:
            await sendTextMessage(psid, "❓ أمر غير معروف. اكتب /help للمساعدة.");
    }
}

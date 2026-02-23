import axios from 'axios';

export const tools = [
    {
        name: 'getPrayerTimes',
        description: 'Get prayer times for a specific city and country or by coordinates.',
        parameters: { type: 'object', properties: { city: { type: 'string' }, country: { type: 'string' }, latitude: { type: 'number' }, longitude: { type: 'number' } } }
    },
    {
        name: 'getQuranVerse',
        description: 'Get a specific surah from the Quran.',
        parameters: { type: 'object', properties: { surahNumber: { type: 'number' } }, required: ['surahNumber'] }
    },
    {
        name: 'getQuranTafsir',
        description: 'Get Tafsir for a specific surah.',
        parameters: { type: 'object', properties: { surahNumber: { type: 'number' } }, required: ['surahNumber'] }
    },
    {
        name: 'searchIslamHouse',
        description: 'Search for Islamic content on IslamHouse.',
        parameters: { type: 'object', properties: { query: { type: 'string' } } }
    },
    {
        name: 'getCurrentDateTime',
        description: 'Get the current date and time in Khartoum (+2 GMT).',
        parameters: { type: 'object', properties: {} }
    }
];

export async function executeTool(name: string, args: any) {
    switch (name) {
        case 'getPrayerTimes': return await getPrayerTimes(args);
        case 'getQuranVerse': return await getQuranVerse(args.surahNumber);
        case 'getQuranTafsir': return await getQuranTafsir(args.surahNumber);
        case 'searchIslamHouse': return await searchIslamHouse(args.query);
        case 'getCurrentDateTime': return getCurrentDateTime();
        default: throw new Error(\`Tool \${name} not found\`);
    }
}

function getCurrentDateTime() {
    const now = new Date();
    const khartoumTime = new Date(now.getTime() + (2 * 60 * 60 * 1000));
    return { iso: khartoumTime.toISOString(), readable: khartoumTime.toLocaleString('ar-SD', { timeZone: 'Africa/Khartoum' }), date: khartoumTime.toISOString().split('T')[0] };
}

export async function validateCityCountry(city: string, country: string) {
    try {
        const date = new Date().toISOString().split('T')[0].split('-').reverse().join('-');
        const url = \`https://api.aladhan.com/v1/timingsByCity/\${date}?city=\${city}&country=\${country}&method=8\`;
        const response = await axios.get(url);
        return { valid: true, data: response.data.data };
    } catch (error: any) {
        return { valid: false, error: error.response?.data?.data || 'Invalid location' };
    }
}

async function getPrayerTimes(args: any) {
    try {
        const date = new Date().toISOString().split('T')[0].split('-').reverse().join('-');
        let url = args.latitude ? \`https://api.aladhan.com/v1/timings/\${date}?latitude=\${args.latitude}&longitude=\${args.longitude}&method=8\` : \`https://api.aladhan.com/v1/timingsByCity/\${date}?city=\${args.city || 'Nyala'}&country=\${args.country || 'Sudan'}&method=8\`;
        const response = await axios.get(url);
        return response.data.data;
    } catch (error: any) {
        return { error: 'Could not fetch prayer times' };
    }
}

async function getQuranVerse(surahNumber: number) {
    try {
        const response = await axios.get(\`https://api.alquran.cloud/v1/surah/\${surahNumber}\`);
        return response.data.data;
    } catch (error) { return { error: 'Could not fetch Quran surah' }; }
}

async function getQuranTafsir(surahNumber: number) {
    try {
        const response = await axios.get(\`https://quranenc.com/api/v1/translation/sura/arabic_moyassar/\${surahNumber}\`);
        return response.data;
    } catch (error) { return { error: 'Could not fetch Quran tafsir' }; }
}

async function searchIslamHouse(query: string) {
    try {
        const response = await axios.get(\`https://api3.islamhouse.com/v3/paV29H2gm56kvLPy/main/sitecontent/ar/ar/json\`);
        return response.data;
    } catch (error) { return { error: 'Could not fetch IslamHouse content' }; }
}

import sqlite3 from 'sqlite3';
import { open, Database } from 'sqlite';
import path from 'path';
import fs from 'fs';

const dbPath = process.env.DATABASE_PATH || './data/database.sqlite';

const dataDir = path.dirname(dbPath);
if (!fs.existsSync(dataDir)) {
    fs.mkdirSync(dataDir, { recursive: true });
}

let db: Database | null = null;

export async function getDb(): Promise<Database> {
    if (db) return db;
    db = await open({
        filename: dbPath,
        driver: sqlite3.Database
    });
    await initDb(db);
    return db;
}

async function initDb(database: Database) {
    await database.exec(\`
        CREATE TABLE IF NOT EXISTS users (
            psid TEXT PRIMARY KEY,
            state TEXT DEFAULT 'default',
            city TEXT DEFAULT 'Nyala',
            country TEXT DEFAULT 'Sudan',
            lat REAL,
            lng REAL,
            timezone TEXT DEFAULT 'Africa/Khartoum',
            last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS complaints (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            psid TEXT,
            message TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved INTEGER DEFAULT 0,
            FOREIGN KEY(psid) REFERENCES users(psid)
        );
        CREATE TABLE IF NOT EXISTS history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            psid TEXT,
            role TEXT,
            content TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(psid) REFERENCES users(psid)
        );
    \`);
}

export async function getUser(psid: string) {
    const database = await getDb();
    return await database.get('SELECT * FROM users WHERE psid = ?', [psid]);
}

export async function createUser(psid: string) {
    const database = await getDb();
    await database.run('INSERT OR IGNORE INTO users (psid) VALUES (?)', [psid]);
    return await getUser(psid);
}

export async function updateUser(psid: string, data: any) {
    const database = await getDb();
    const fields = Object.keys(data).map(key => \`\${key} = ?\`).join(', ');
    const values = Object.values(data);
    await database.run(\`UPDATE users SET \${fields}, last_seen = CURRENT_TIMESTAMP WHERE psid = ?\`, [...values, psid]);
}

export async function addHistory(psid: string, role: string, content: string) {
    const database = await getDb();
    await database.run('INSERT INTO history (psid, role, content) VALUES (?, ?, ?)', [psid, role, content]);
    const count: any = await database.get('SELECT COUNT(*) as count FROM history WHERE psid = ?', [psid]);
    if (count.count > 10) {
        await database.run(\`DELETE FROM history WHERE psid = ? AND id IN (SELECT id FROM history WHERE psid = ? ORDER BY timestamp ASC LIMIT ?)\`, [psid, psid, count.count - 10]);
    }
}

export async function getHistory(psid: string) {
    const database = await getDb();
    return await database.all('SELECT role, content FROM history WHERE psid = ? ORDER BY timestamp ASC', [psid]);
}

export async function saveComplaint(psid: string, message: string) {
    const database = await getDb();
    await database.run('INSERT INTO complaints (psid, message) VALUES (?, ?)', [psid, message]);
}

export async function getAllComplaints() {
    const database = await getDb();
    return await database.all('SELECT * FROM complaints ORDER BY timestamp DESC');
}

export async function getAllUsers() {
    const database = await getDb();
    return await database.all('SELECT * FROM users ORDER BY last_seen DESC');
}

export async function resetSystem() {
    const database = await getDb();
    await database.run('DELETE FROM complaints');
    await database.run('DELETE FROM history');
}

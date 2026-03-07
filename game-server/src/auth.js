const db = require('./db');

async function validateUser(sessionIdOrUserId) {
  const numeric = parseInt(sessionIdOrUserId, 10);
  if (!Number.isFinite(numeric) || numeric <= 0) return null;
  const rows = await db.query(
    'SELECT id, username FROM users WHERE id = ? LIMIT 1',
    [numeric]
  );
  return rows[0] || null;
}

async function validateSessionCookie(cookieHeader) {
  if (!cookieHeader || typeof cookieHeader !== 'string') return null;
  const PHPSESSID = cookieHeader.split(';').map(s => s.trim()).find(s => s.startsWith('PHPSESSID='));
  if (!PHPSESSID) return null;
  const sessionId = PHPSESSID.replace(/^PHPSESSID=/, '').trim();
  if (!sessionId) return null;
  const path = require('path');
  require('dotenv').config({ path: path.resolve(__dirname, '../../.env') });
  const sessionPath = process.env.SESSION_PATH || require('os').tmpdir();
  const fs = require('fs').promises;
  const sessionFile = path.join(sessionPath, `sess_${sessionId}`);
  try {
    const data = await fs.readFile(sessionFile, 'utf8');
    const match = data.match(/user_id\|i:(\d+)/);
    if (match) return await validateUser(match[1]);
  } catch (_) {}
  const rows = await db.query(
    'SELECT id, username FROM users WHERE id = (SELECT user_id FROM php_sessions WHERE session_id = ? LIMIT 1) LIMIT 1',
    [sessionId]
  ).catch(() => []);
  return (Array.isArray(rows) ? rows[0] : null) || null;
}

module.exports = { validateUser, validateSessionCookie };

# MyOPCards Game Server

Node.js + Socket.io game server for One Piece TCG online play. Uses the same MySQL database as the PHP app (loads `.env` from project root).

## Setup

- `npm install`
- Ensure `DB_*` and optionally `GAME_SERVER_PORT` (default 3001) are set in project root `.env`.
- Start: `npm start` or `pm2 start ecosystem.config.js`
- Apache vhost should proxy `/socket.io/` to `http://127.0.0.1:3001/socket.io/` (see project root `myopcards.com.conf`). Enable `a2enmod proxy proxy_http proxy_wstunnel` if needed.

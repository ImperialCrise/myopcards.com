# MyOPCards

**Track, manage, and share your One Piece TCG collection.**

MyOPCards is a full‑stack web application for One Piece Card Game collectors: browse cards, manage your collection, follow market prices (USD & EUR), and connect with other players.

---

## Features

- **Card catalog** — Browse all One Piece TCG cards with dynamic search, filters (set, rarity, color), and sort by price
- **Collection** — Add/remove cards, quantities, wishlist; sort by price or date; share your collection via a public link
- **Prices** — USD (TCGPlayer) and EUR by edition (EN/FR/JP via Cardmarket when available); switch currency in the header
- **Market** — Top gainers/losers, set value summaries, price history
- **User accounts** — Email/password and Discord OAuth; profile, friends, friend requests with notifications
- **Analytics** — Collection value over time, completion by set, top valuable cards
- **Admin panel** — User/card/price management, manual sync triggers, sync logs, CSV import for EUR prices
- **SEO** — Meta tags, Open Graph, sitemap, structured data
- **Theme** — Light/dark mode, responsive layout, card carousel background

---

## Tech stack

| Layer      | Technology |
|-----------|------------|
| Backend   | PHP 8.4, PDO/MySQL 8 |
| Frontend  | HTML5, Tailwind CSS (CDN), Alpine.js, Chart.js, Lucide icons |
| Auth      | Session + optional Discord OAuth |
| Data      | [OPTCG API](https://optcgapi.com) (cards + TCGPlayer USD), Cardmarket (EUR via FlareSolverr or CSV) |
| Infra     | Apache 2.4, HTTPS (e.g. Let’s Encrypt), Docker (FlareSolverr) |

---

## Requirements

- PHP 8.4+ with extensions: `pdo_mysql`, `curl`, `json`, `mbstring`
- MySQL 8.0+
- Apache with `mod_rewrite` (or equivalent URL rewriting)
- Optional: Docker (for FlareSolverr, used for Cardmarket scraping)

---

## Installation

### 1. Clone and dependencies

```bash
git clone git@github.com:ImperialCrise/myopcards.com.git
cd myopcards.com
composer install
```

### 2. Environment

Copy environment variables and edit as needed:

```bash
cp .env.example .env
```

Required variables:

| Variable | Description |
|----------|-------------|
| `APP_NAME` | Application name (e.g. MyOPCards) |
| `APP_URL` | Full base URL (e.g. https://myopcards.com) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL connection |
| `OPTCG_API_BASE` | `https://optcgapi.com/api` |
| `DISCORD_CLIENT_ID`, `DISCORD_CLIENT_SECRET`, `DISCORD_REDIRECT_URI` | For Discord login (optional) |
| `FLARESOLVERR_URL` | `http://127.0.0.1:8192/v1` if using Docker FlareSolverr (optional) |

### 3. Database

Create the database and user, then run migrations:

```bash
php bin/migrate.php
```

### 4. Web server

- Document root: `public/`
- Enable `mod_rewrite` and point requests to `public/index.php` (see `public/.htaccess`).
- Use HTTPS in production (e.g. Certbot).

### 5. Game server (optional, for online play)

The play lobby and VS Bot need the Node.js game server. Apache must proxy `/socket.io/` to it (vhost 80 and 443).

**With Docker Compose (recommended):**

```bash
docker compose up -d game-server
```

The service uses the project `.env` and connects to MySQL on the host via `host.docker.internal`. Port 3001 is published on the host for the Apache proxy.

**MySQL:** The game server runs inside Docker, so MySQL sees the connection from the container IP (e.g. `172.20.0.x`), not `localhost`. Allow the same user from any host (e.g. run once as MySQL admin):

```sql
CREATE USER IF NOT EXISTS 'myopcards_user'@'%' IDENTIFIED BY 'YOUR_DB_PASSWORD';
GRANT ALL PRIVILEGES ON myopcards.* TO 'myopcards_user'@'%';
FLUSH PRIVILEGES;
```

Use the same password as `DB_PASSWORD` in `.env`. If the user already exists for `%`, just ensure the password matches.

**Or with Node directly (e.g. PM2):**

```bash
cd game-server
npm install
pm2 start ecosystem.config.js
```

Default port: 3001 (override with `GAME_SERVER_PORT` in `.env`). Health check: `curl http://127.0.0.1:3001/health`.

### 6. FlareSolverr (optional, for Cardmarket EUR)

To scrape Cardmarket (Cloudflare‑protected), run a dedicated FlareSolverr instance:

```bash
docker compose up -d
```

This starts FlareSolverr on `127.0.0.1:8192`. Set `FLARESOLVERR_URL=http://127.0.0.1:8192/v1` in `.env`.

---

## Cron jobs

Recommended schedule for price and collection data:

| Schedule | Command | Purpose |
|----------|---------|--------|
| Daily 4:00 | `php bin/sync-cards.php` | Refresh card catalog from OPTCG API |
| Daily 3:00 | `php bin/sync-prices.php tcgplayer` | Update USD prices (TCGPlayer) |
| Weekly (e.g. Mon 1:00) | `php bin/sync-prices.php cardmarket en 100` | Cardmarket EN (batch of 100) |
| Weekly (e.g. Tue 1:00) | `php bin/sync-prices.php cardmarket fr 100` | Cardmarket FR |
| Weekly (e.g. Wed 1:00) | `php bin/sync-prices.php cardmarket jp 100` | Cardmarket JP |
| Daily 4:30 | `php bin/snapshot-collections.php` | Collection value snapshots for charts |
| Daily 5:00 | `php bin/sync-translations.php` | Card name translations |

Log output to files (e.g. `/var/log/myopcards-*.log`) and ensure the web user can run these scripts.

---

## Project structure

```
myopcards.com/
├── bin/                    # CLI scripts (sync, migrate, snapshot)
├── migrations/             # SQL migrations (run via bin/migrate.php)
├── public/                 # Web root
│   ├── assets/             # CSS, JS, images
│   ├── index.php           # Front controller
│   └── .htaccess
├── src/
│   ├── Controllers/
│   ├── Core/               # Auth, Database, Currency, View, Router, SyncLogger
│   ├── Models/
│   ├── Services/           # Card sync, price update, Cardmarket scraper, OAuth
│   └── Views/
├── .env                    # Not in git; copy from .env.example
├── composer.json
├── docker-compose.yml      # FlareSolverr for Cardmarket
└── README.md
```

---

## Price updating (summary)

- **USD** — Fetched from OPTCG API (TCGPlayer). Run `php bin/sync-prices.php tcgplayer` (e.g. daily).
- **EUR (EN/FR/JP)** — From Cardmarket:
  - With FlareSolverr: same script with `cardmarket en|fr|jp [limit]` (e.g. 100 per run to avoid rate limits).
  - Without: use Admin → Prices → CSV import (`card_set_id`, `price_en`, `price_fr`, `price_jp`).

Sync runs are logged in the database; view them in **Admin → Logs**.

---

## Admin panel

- URL: `/admin` (requires admin user).
- First admin is set in the database (`users.is_admin = 1` for the chosen user).
- Sections: Dashboard, Users, Cards (with edit), Prices (sync buttons, CSV import), Sync logs.

---

## License and credits

- Not affiliated with Bandai or One Piece. Card data from [OPTCG API](https://optcgapi.com). Prices from TCGPlayer and Cardmarket.
- [Discord community](https://discord.gg/m5k52GFQPQ) — Privileged Lab.

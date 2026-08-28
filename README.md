# SKA The Boutique — Hotels Website

Luxury boutique hotel website for **SKA Naguru** and **SKA Munyonyo**, Kampala.

This is a **PHP + static HTML** site, not Laravel. There is no `database.php`, no Artisan, and no `php artisan migrate`. Data lives in **two possible databases** depending on how you host it.

- **Public site**: GitHub Pages (static) + Supabase
- **Legacy PHP**: still supported for local/cPanel hosting with MySQL
- **Built by**: [Cirqco](https://cirqco.com/)

---

## Where is the database?

| Host | Database | How to create it |
|---|---|---|
| **GitHub Pages** (live: `cirqcox-pixel.github.io/skahotels`) | **Supabase PostgreSQL** project [`aoofgjyhwbxasdvhdwoe`](https://supabase.com/dashboard/project/aoofgjyhwbxasdvhdwoe) | Paste `supabase/RUN_THIS_IN_SUPABASE.sql` into the [SQL Editor](https://supabase.com/dashboard/project/aoofgjyhwbxasdvhdwoe/sql) once |
| **cPanel / PHP server** | **MySQL / MariaDB** named `skabcwvw_ska001` (cPanel prefix may vary) | Create the DB in cPanel, put credentials in `.env`, import `database/schema_mysql.sql` **or** load any page (tables self-create). Then visit `/admin/setup.php` |

There is no committed `.env` — copy `.env.example`. The MySQL password is **not** in this repo. The Supabase **anon** key in `assets/js/ska-config.js` is public by design; the **service role** key must never be committed.

Step-by-step for Namecheap/cPanel: [`DEPLOY_CPANEL.md`](DEPLOY_CPANEL.md).

---

## Live URLs

| Environment | URL |
|---|---|
| GitHub Pages | `https://cirqcox-pixel.github.io/skahotels/` |
| Supabase project | `https://supabase.com/dashboard/project/aoofgjyhwbxasdvhdwoe` |
| GitHub repo | `https://github.com/cirqcox-pixel/skahotels` |

---

## Architecture

```
GitHub Pages (static HTML)
    ↓  @supabase/supabase-js (anon key)
Supabase PostgreSQL + Auth + Storage
```

**GitHub Pages cannot run PHP.** The repo includes:

- **`.php` files** — full CMS for PHP/MySQL hosting (backward compatible)
- **`docs/` folder** — static HTML built by CI for GitHub Pages
- **`assets/js/ska-*.js`** — Supabase client, forms, live data hydration

---

## 1. Supabase setup (required for GitHub Pages)

Easiest: paste the whole file `supabase/RUN_THIS_IN_SUPABASE.sql` into the [SQL Editor](https://supabase.com/dashboard/project/aoofgjyhwbxasdvhdwoe/sql) and run it once.

Or run migrations **in order**:

1. `supabase/migrations/001_schema.sql`
2. `supabase/migrations/002_seed.sql`
3. `supabase/migrations/003_rls.sql`
4. `supabase/migrations/004_rooms_seed.sql`
5. `supabase/migrations/005_fix_admin_rls.sql`
6. `supabase/migrations/006_seed_offers.sql`
7. `supabase/migrations/007_harden_rls.sql` (admin allowlist — required for a secure admin)

Then:

1. **Authentication → Sign In / Providers → disable public email sign-ups**
2. **Authentication → Users → Add user** (e.g. `admin@skaboutiquebnb.com`)
3. Add that user to `admin_users` (see comments at the top of `007_harden_rls.sql`)
4. Sign in at `/admin/login.html` on GitHub Pages

### Migrate existing MySQL data (optional)

Export rooms, bookings, promotions from MySQL and import via Supabase Table Editor or SQL.

---

## 2. GitHub Pages deployment

### Automatic (recommended)

1. Push to `main` branch
2. GitHub Actions runs `tools/build-static.mjs` → builds `docs/`
3. Enable **Pages** in repo Settings → Build and deployment → **GitHub Actions**

### Manual build

```bash
node tools/build-static.mjs
# Commit docs/ folder or let CI handle it
```

---

## 3. Local / cPanel PHP (MySQL)

```bash
cp .env.example .env
# Edit DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Optional explicit import (phpMyAdmin or mysql CLI):
# mysql -u USER -p DBNAME < database/schema_mysql.sql

php -S localhost:8080
```

First admin: `http://localhost:8080/admin/setup.php` (self-disables after one account). Then `http://localhost:8080/admin/login.php`.

`config/cms.php` creates every table and seeds rooms / offers / CMS copy on first page load if they are missing.

---

## Configuration

| File | Purpose |
|---|---|
| `assets/js/ska-config.js` | Supabase URL + publishable anon key + Formspree/Resend notify settings |
| `.env` | MySQL credentials + Supabase service role (never commit) |
| `config/db.php` | Auto-selects MySQL or Supabase based on `DB_CONNECTION` |
| `database/schema_mysql.sql` | Full MySQL schema + sample rooms for phpMyAdmin |
| `supabase/RUN_THIS_IN_SUPABASE.sql` | One-paste Postgres schema + seed + hardened RLS |
| `DEPLOY_CPANEL.md` | Namecheap / cPanel MySQL deploy steps |
| `EMAIL_SETUP.md` | How to enable Formspree or Resend email alerts on GitHub Pages |

---

## Admin portals

| Host | Admin login |
|---|---|
| GitHub Pages | `/skahotels/admin/login.html` (Supabase Auth) |
| PHP server | `/admin/login.php` (MySQL admins table) |

### CMS features (PHP admin)

- Rooms, Bookings, Promotions
- Inquiries, Pages & Content, Gallery, Site Settings, Admin Users

---

## Project structure

```
assets/           CSS, images, JS (Supabase client)
admin/            PHP admin portal (`setup.php` creates the first MySQL admin)
config/           Site config, DB, CMS helpers
database/         MySQL schema (`schema_mysql.sql`)
forms/            PHP form handlers (MySQL path)
includes/         Layout partials
supabase/         Postgres migrations + one-paste `RUN_THIS_IN_SUPABASE.sql`
tools/            build-static.mjs for GitHub Pages
docs/             Generated static site (CI output)
```

---

## Security notes

- **Publishable/anon key** in `ska-config.js` is designed to be public — protected by RLS policies
- **Service role key** must only live in `.env` on PHP servers — never in GitHub
- `.env` is gitignored

---

## Support

Crafted by **Cirqco** — [cirqco.com](https://cirqco.com/)

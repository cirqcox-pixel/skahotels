# SKA The Boutique — Hotels Website

Luxury boutique hotel website for **SKA Naguru** and **SKA Munyonyo**, Kampala.

- **Public site**: GitHub Pages (static) + Supabase
- **Legacy PHP**: still supported for local/cPanel hosting with MySQL
- **Built by**: [Cirqco](https://cirqco.com/)

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

## 1. Supabase setup (required)

In [Supabase SQL Editor](https://supabase.com/dashboard/project/aoofgjyhwbxasdvhdwoe/sql), run migrations **in order**:

1. `supabase/migrations/001_schema.sql`
2. `supabase/migrations/002_seed.sql`
3. `supabase/migrations/003_rls.sql`

### Create admin user (Supabase Auth)

1. Go to **Authentication → Users → Add user**
2. Use email + password (e.g. `admin@skaboutiquebnb.com`)
3. Sign in at `/admin/login.html` on GitHub Pages

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

## 3. Local PHP development (optional, backward compatible)

```bash
# Copy environment file
cp .env.example .env

# Use MySQL (default)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=skabcwvw_ska001
...

# Serve with PHP built-in server
php -S localhost:8080
```

PHP admin: `http://localhost:8080/admin/login.php`

---

## Configuration

| File | Purpose |
|---|---|
| `assets/js/ska-config.js` | Supabase URL + publishable anon key (safe for public repo) |
| `.env` | MySQL credentials + Supabase service role (never commit) |
| `config/db.php` | Auto-selects MySQL or Supabase based on `DB_CONNECTION` |

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
admin/            PHP admin portal
config/           Site config, DB, CMS helpers
forms/            PHP form handlers (MySQL path)
includes/         Layout partials
supabase/         SQL migrations + RLS
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

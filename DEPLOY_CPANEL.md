# Deploying SKA Hotels to cPanel (Namecheap)

The **same codebase** runs in two modes, chosen by one env var:

| Mode | Where | Database |
|------|-------|----------|
| `supabase` | GitHub Pages (static `docs/`) | Supabase (PostgreSQL) |
| `mysql` (default) | cPanel / Namecheap (PHP) | cPanel MySQL / MariaDB |

This guide covers the **cPanel / MySQL** path. Nothing needs code changes — you
only set a `.env` and (optionally) import the schema.

---

## 1. Create the database (cPanel)

1. cPanel → **MySQL® Databases**.
2. Create a database, e.g. `skabcwvw_ska001`.
3. Create a MySQL user with a strong password.
4. **Add the user to the database** with **ALL PRIVILEGES**.

Note the final names — cPanel prefixes them with your account (e.g.
`skabcwvw_ska001`, `skabcwvw_skauser`).

## 2. Upload the files

Upload the project into your web root (`public_html/` or a subfolder) via
**File Manager** or FTP. Include everything **except**:

- `.env` (create it fresh on the server — see step 3)
- `node_modules/`, `docs/`, `tools/` (these are only for the GitHub Pages build)

## 3. Create `.env` on the server

In the site root, create a `.env` file (copy from `env.example`):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=skabcwvw_ska001
DB_USERNAME=skabcwvw_skauser
DB_PASSWORD=your_real_password
```

- `DB_HOST` is almost always `127.0.0.1` (or `localhost`) on cPanel.
- Leave `SUPABASE_*` unset — they're ignored in mysql mode.
- Make sure `.env` is **not** publicly downloadable. The repo `.gitignore`
  already excludes it; on the server keep it outside the docroot if you can, or
  rely on the default deny (it has no direct route).

## 4. Create the tables

**You have two options — either works:**

**A. Do nothing (self-healing).** The first time any page loads,
`config/cms.php` auto-creates every table and seeds the CMS content. Just visit
your homepage.

**B. Import explicitly (recommended for a clean, reviewable schema).**
cPanel → **phpMyAdmin** → select your DB → **Import** →
`database/schema_mysql.sql`. This creates all tables and adds a little sample
room/promotion content. It's safe to run even after option A (everything is
`IF NOT EXISTS`).

## 5. Create the first admin

Visit **`https://yourdomain/admin/setup.php`** once. It creates your first
administrator (bcrypt-hashed). It self-disables as soon as an admin exists.

**Then delete `admin/setup.php` from the server.**

Sign in at `https://yourdomain/admin/login.php`.

## 6. File uploads

The admin writes room/promotion images to `uploads/rooms/` and
`uploads/promotions/`. Ensure `uploads/` exists and is writable
(cPanel default `0755` is fine; the code creates subfolders as needed).

> ⚠️ On GitHub Pages there is no `uploads/` write path — image uploads are a
> cPanel-only feature. On the static site, images come from committed assets and
> Supabase Storage.

---

## Switching a server to Supabase instead (optional)

If you ever want the PHP server to read/write Supabase instead of MySQL, set in
`.env`:

```
DB_CONNECTION=supabase
SUPABASE_URL=https://nllqkepymtwwbvbjnbyz.supabase.co
SUPABASE_SERVICE_ROLE_KEY=...   # server-side only, never commit
```

(Most deployments won't need this — GitHub Pages already talks to Supabase from
the browser.)

---

## Troubleshooting

| Symptom | Cause / fix |
|--------|-------------|
| `Connection failed: Access denied` | Wrong `.env` DB creds, or user not added to DB with privileges. |
| Blank page / 500 on first load | PHP version too old — require **PHP 8.0+** (cPanel → Select PHP Version). |
| "Table doesn't exist" | You disabled the self-heal or restricted DDL rights — import `database/schema_mysql.sql` manually. |
| Can't reach `/admin/setup.php` | It self-disables once an admin exists; use `login.php`. To reset, clear the `admins` table in phpMyAdmin. |
| Images upload but don't show | `uploads/` not writable, or wrong file permissions. Set `uploads/` to `0755`. |

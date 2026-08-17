#!/usr/bin/env node
/**
 * Build static HTML site for GitHub Pages (docs/ folder)
 * Strips PHP blocks, rewrites .php → .html, copies assets.
 *
 * Usage: node tools/build-static.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'docs');

const PUBLIC_PAGES = [
  'index', 'offers', 'about-us', 'meetings-events', 'contact', 'help',
  'careers', 'loyalty', 'privacy-policy', 'terms-of-use', 'cookie-policy',
  'naguru', 'munyonyo'
];

const COPY_DIRS = ['assets', 'uploads'];
const COPY_FILES = ['robots.txt', 'sitemap.xml', 'google0f5359f7f26f1d03.html', '.nojekyll'];

function rmrf(dir) {
  if (!fs.existsSync(dir)) return;
  fs.rmSync(dir, { recursive: true, force: true });
}

function copyDir(src, dest) {
  if (!fs.existsSync(src)) return;
  fs.mkdirSync(dest, { recursive: true });
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const s = path.join(src, entry.name);
    const d = path.join(dest, entry.name);
    if (entry.isDirectory()) copyDir(s, d);
    else fs.copyFileSync(s, d);
  }
}

function stripPhp(content) {
  content = content.replace(/<\?php[\s\S]*?\?>/g, '');
  content = content.replace(/<\?=[\s\S]*?\?>/g, '');
  return content;
}

function rewriteLinks(content) {
  content = content.replace(/href="([^"]*)\.php([^"]*)"/g, 'href="$1.html$2"');
  content = content.replace(/action="([^"]*)\.php([^"]*)"/g, 'action="$1.html$2"');
  content = content.replace(/href='([^']*)\.php([^']*)'/g, "href='$1.html$2'");
  return content;
}

function injectStaticFlag(html) {
  return html.replace('<html', '<html data-ska-static="true"');
}

function appendScripts(html) {
  const scripts = `
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="/skahotels/assets/js/ska-config.js"></script>
<script src="/skahotels/assets/js/ska-api.js"></script>
<script src="/skahotels/assets/js/ska-forms.js"></script>
<script src="/skahotels/assets/js/ska-live.js"></script>`;
  if (html.includes('ska-config.js')) return html;
  return html.replace('</body>', scripts + '\n</body>');
}

function buildPage(name) {
  const src = path.join(ROOT, `${name}.php`);
  if (!fs.existsSync(src)) {
    console.warn(`Skip missing: ${name}.php`);
    return;
  }
  let html = fs.readFileSync(src, 'utf8');
  html = stripPhp(html);
  html = rewriteLinks(html);
  html = injectStaticFlag(html);
  html = appendScripts(html);
  fs.writeFileSync(path.join(OUT, `${name}.html`), html);
  console.log(`Built ${name}.html`);
}

function buildAdminLogin() {
  const adminOut = path.join(OUT, 'admin');
  const adminAssets = path.join(adminOut, 'assets');
  fs.mkdirSync(adminAssets, { recursive: true });
  const html = `<!DOCTYPE html>
<html lang="en" data-ska-static="true">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Sign In | SKA</title>
  <link rel="stylesheet" href="/skahotels/admin/assets/login.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="ska-login">
<div class="ska-login-wrap"><div class="login-card">
  <div class="logo"><h4>SKA <span>Admin Portal</span></h4><p class="logo-sub">Supabase Auth</p></div>
  <div id="loginError" class="error" style="display:none"></div>
  <form id="adminLoginForm" class="ska-login-form">
    <div class="mb-3"><label>Email</label><input type="email" id="adminEmail" class="form-control" required></div>
    <div class="mb-3"><label>Password</label><input type="password" id="adminPass" class="form-control" required></div>
    <button type="submit" class="btn-login">Sign In</button>
  </form>
  <p style="font-size:12px;color:#888;margin-top:16px">Create admin users in Supabase Auth dashboard.</p>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="/skahotels/assets/js/ska-config.js"></script>
<script src="/skahotels/assets/js/ska-api.js"></script>
<script>
document.getElementById('adminLoginForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  var err = document.getElementById('loginError');
  try {
    await SkaApi.adminSignIn(document.getElementById('adminEmail').value, document.getElementById('adminPass').value);
    location.href = 'dashboard.html';
  } catch (ex) {
    err.style.display = 'block';
    err.textContent = ex.message || 'Login failed';
  }
});
</script>
</body></html>`;
  fs.writeFileSync(path.join(adminOut, 'login.html'), html);

  const dash = `<!DOCTYPE html>
<html lang="en" data-ska-static="true">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Dashboard | SKA Admin</title>
  <link rel="stylesheet" href="/skahotels/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="background:#f4f2ed;padding:24px;font-family:Jost,sans-serif">
<div style="max-width:1100px;margin:0 auto">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <h1 style="margin:0;font-size:24px">SKA Admin</h1>
    <div>
      <a href="/skahotels/" target="_blank" style="margin-right:12px">View Site</a>
      <button id="logoutBtn" style="padding:8px 16px">Logout</button>
    </div>
  </div>
  <div id="authWarn" style="display:none;padding:16px;background:#fff3cd;border-radius:8px;margin-bottom:16px">Please <a href="login.html">sign in</a> first.</div>
  <h2>Recent Bookings</h2>
  <div id="bookings" style="background:#fff;border-radius:8px;padding:16px;margin-bottom:24px;overflow-x:auto">Loading…</div>
  <h2>Recent Inquiries</h2>
  <div id="inquiries" style="background:#fff;border-radius:8px;padding:16px;overflow-x:auto">Loading…</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="/skahotels/assets/js/ska-config.js"></script>
<script src="/skahotels/assets/js/ska-api.js"></script>
<script>
(async function(){
  var session = await SkaApi.adminSession();
  if (!session) { document.getElementById('authWarn').style.display='block'; return; }
  var sb = SkaApi.client();
  var b = await sb.from('bookings').select('*').order('created_at',{ascending:false}).limit(20);
  var i = await sb.from('inquiries').select('*').order('created_at',{ascending:false}).limit(20);
  function table(rows, cols) {
    if (!rows.length) return '<p>No records yet.</p>';
    var h = '<table style="width:100%;border-collapse:collapse;font-size:13px"><tr>' + cols.map(function(c){return '<th style="text-align:left;padding:8px;border-bottom:1px solid #eee">'+c+'</th>';}).join('') + '</tr>';
    rows.forEach(function(r){ h += '<tr>' + cols.map(function(c){return '<td style="padding:8px;border-bottom:1px solid #f3f3f3">'+(r[c]||'—')+'</td>';}).join('') + '</tr>'; });
    return h + '</table>';
  }
  document.getElementById('bookings').innerHTML = b.error ? b.error.message : table(b.data||[], ['created_at','name','email','branch','room_type','status','total']);
  document.getElementById('inquiries').innerHTML = i.error ? i.error.message : table(i.data||[], ['created_at','name','email','subject','message']);
  document.getElementById('logoutBtn').onclick = async function(){ await SkaApi.adminSignOut(); location.href='login.html'; };
})();
</script>
</body></html>`;
  fs.writeFileSync(path.join(adminOut, 'dashboard.html'), dash);
  console.log('Built admin/login.html + dashboard.html');
}

function copyAdminAssets() {
  copyDir(path.join(ROOT, 'admin', 'assets'), path.join(OUT, 'admin', 'assets'));
}

console.log('Building GitHub Pages static site → docs/');
rmrf(OUT);
fs.mkdirSync(OUT, { recursive: true });

for (const page of PUBLIC_PAGES) buildPage(page);
buildAdminLogin();

for (const dir of COPY_DIRS) copyDir(path.join(ROOT, dir), path.join(OUT, dir));
copyDir(path.join(ROOT, 'admin', 'assets'), path.join(OUT, 'admin', 'assets'));

for (const file of COPY_FILES) {
  const src = path.join(ROOT, file);
  if (fs.existsSync(src)) fs.copyFileSync(src, path.join(OUT, file));
}

fs.writeFileSync(path.join(OUT, '.nojekyll'), '');
console.log('Done. Deploy docs/ folder via GitHub Pages.');

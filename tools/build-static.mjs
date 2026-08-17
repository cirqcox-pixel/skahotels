#!/usr/bin/env node
/**
 * Build valid static HTML for GitHub Pages → docs/
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'docs');
const PARTIALS = path.join(__dirname, 'partials');
const BASE = '/skahotels';

const PAGES = {
  index: {
    title: 'SKA The Boutique | Luxury Boutique Hotel in Kampala, Uganda',
    description: 'Book direct at SKA The Boutique — Naguru and Munyonyo, Kampala.',
    css: ['assets/css/home.css'],
    nav: 'landing',
    bodyClass: 'has-landing-nav',
  },
  offers: { title: 'Special Offers | SKA The Boutique', description: 'Exclusive offers at SKA.', css: ['assets/css/pages.css'], nav: 'landing' },
  'about-us': { title: 'About Us | SKA The Boutique', description: 'Our story.', css: ['assets/css/style.css', 'assets/css/pages.css'], nav: 'landing' },
  'meetings-events': { title: 'Meetings & Events | SKA', description: 'Events at SKA.', css: ['assets/css/pages.css'], nav: 'landing' },
  contact: { title: 'Contact Us | SKA', description: 'Get in touch.', css: ['assets/css/pages.css'], nav: 'landing' },
  help: { title: 'Help Centre | SKA', description: 'Help and FAQs.', css: ['assets/css/pages.css'], nav: 'landing' },
  careers: { title: 'Careers | SKA', description: 'Join our team.', css: ['assets/css/pages.css'], nav: 'landing' },
  loyalty: { title: 'SKA Rewards', description: 'Loyalty programme.', css: ['assets/css/pages.css'], nav: 'landing' },
  'privacy-policy': { title: 'Privacy Policy | SKA', description: 'Privacy policy.', css: ['assets/css/pages.css'], nav: 'landing' },
  'terms-of-use': { title: 'Terms of Use | SKA', description: 'Terms of use.', css: ['assets/css/pages.css'], nav: 'landing' },
  'cookie-policy': { title: 'Cookie Policy | SKA', description: 'Cookie policy.', css: ['assets/css/pages.css'], nav: 'landing' },
  naguru: {
    title: 'SKA Naguru | Boutique Hotel Kampala',
    description: 'Book SKA Naguru.',
    css: ['assets/css/branch.css', 'assets/css/rooms-section.css'],
    nav: 'property',
    property: 'naguru',
    bodyClass: '',
  },
  munyonyo: {
    title: 'SKA Munyonyo | Lakeside Boutique Hotel',
    description: 'Book SKA Munyonyo.',
    css: ['assets/css/branch.css', 'assets/css/rooms-section.css'],
    nav: 'property',
    property: 'munyonyo',
    bodyClass: '',
  },
};

const COPY_DIRS = ['assets'];
const COPY_FILES = ['robots.txt', 'sitemap.xml', 'google0f5359f7f26f1d03.html'];

function readPartial(name) {
  return fs.readFileSync(path.join(PARTIALS, name), 'utf8');
}

function rmrf(dir) {
  if (fs.existsSync(dir)) fs.rmSync(dir, { recursive: true, force: true });
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
  return content
    .replace(/<\?php[\s\S]*?\?>/g, '')
    .replace(/<\?=[\s\S]*?\?>/g, '');
}

function fixPaths(html) {
  html = html.replace(/\.php/g, '.html');
  html = html.replace(/(?:src|href)="assets\//g, (m) => m.replace('assets/', `${BASE}/assets/`));
  html = html.replace(/(?:src|href)='assets\//g, (m) => m.replace('assets/', `${BASE}/assets/`));
  html = html.replace(/url\(['"]?assets\//g, (m) => m.replace('assets/', `${BASE}/assets/`));
  html = html.replace(/action="forms\/process_[^"]+"/g, 'action="#" data-ska-form="booking"');
  html = html.replace(/action="forms\/process_inquiry\.php"/g, 'action="#" data-ska-form="inquiry"');
  return html;
}

function propertyNav(slug) {
  const base = `${BASE}/${slug}.html`;
  return `<div class="ska-property-header" id="skaPropertyHeader"><div class="container"><div class="ska-tabs">
    <div class="ska-brand"><a href="${BASE}/index.html"><img src="${BASE}/assets/images/favicon.png" alt="SKA"></a></div>
    <nav class="ska-tab-links" id="skaTabLinks">
      <a href="${base}" class="active">Overview</a>
      <a href="${base}#gallery">Photos</a>
      <a href="${base}#rooms">Rooms</a>
      <a href="${base}#services">Drink + Eat</a>
      <a href="${base}#book">Book</a>
    </nav></div></div></div>`;
}

function buildPage(name, meta) {
  const src = path.join(ROOT, `${name}.php`);
  if (!fs.existsSync(src)) return;

  let body = stripPhp(fs.readFileSync(src, 'utf8')).trim();
  body = fixPaths(body);

  const extraCss = (meta.css || [])
    .map((c) => `<link rel="stylesheet" href="${BASE}/${c.replace(/^assets\//, 'assets/')}">`)
    .join('\n');

  let head = readPartial('head.html')
    .replace('{{TITLE}}', meta.title)
    .replace('{{DESCRIPTION}}', meta.description || meta.title)
    .replace('{{EXTRA_CSS}}', extraCss)
    .replace('{{BODY_CLASS}}', meta.bodyClass || 'has-landing-nav');

  let nav = '';
  if (meta.nav === 'landing') nav = readPartial('nav-landing.html');
  if (meta.nav === 'property') nav = propertyNav(meta.property || name);

  const footer = readPartial('footer.html');
  const html = head + nav + '\n' + body + '\n' + footer;

  fs.writeFileSync(path.join(OUT, `${name}.html`), html);
  console.log(`Built ${name}.html`);
}

function buildAdmin() {
  const adminOut = path.join(OUT, 'admin');
  const adminAssets = path.join(adminOut, 'assets');
  fs.mkdirSync(adminAssets, { recursive: true });

  const login = `<!DOCTYPE html>
<html lang="en" data-ska-static="true"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin | SKA</title><link rel="stylesheet" href="${BASE}/admin/assets/login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body class="ska-login"><div class="ska-login-wrap"><div class="login-card">
<h4>SKA Admin</h4><div id="loginError" class="error" style="display:none"></div>
<form id="adminLoginForm"><label>Email</label><input type="email" id="adminEmail" required><br><br>
<label>Password</label><input type="password" id="adminPass" required><br><br>
<button type="submit">Sign In</button></form>
<p style="font-size:12px;color:#888">Create users in Supabase → Authentication</p>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="${BASE}/assets/js/ska-config.js"></script>
<script src="${BASE}/assets/js/ska-api.js"></script>
<script>
document.getElementById('adminLoginForm').onsubmit=async function(e){e.preventDefault();
try{await SkaApi.adminSignIn(adminEmail.value,adminPass.value);location.href='dashboard.html';}
catch(ex){loginError.style.display='block';loginError.textContent=ex.message;}};
</script></body></html>`;

  fs.writeFileSync(path.join(adminOut, 'login.html'), login);

  const dash = `<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Dashboard | SKA Admin</title>
<link rel="stylesheet" href="${BASE}/admin/assets/admin.css"></head>
<body style="padding:24px;font-family:sans-serif">
<h1>SKA Admin Dashboard</h1>
<p><a href="${BASE}/">View site</a> | <a href="login.html" id="logout">Logout</a></p>
<div id="authWarn" style="display:none;background:#fff3cd;padding:12px">Please <a href="login.html">sign in</a>.</div>
<h2>Bookings</h2><div id="bookings">Loading…</div>
<h2>Inquiries</h2><div id="inquiries">Loading…</div>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="${BASE}/assets/js/ska-config.js"></script>
<script src="${BASE}/assets/js/ska-api.js"></script>
<script>
(async function(){
  if(!await SkaApi.adminSession()){authWarn.style.display='block';return;}
  var sb=SkaApi.client();
  var b=await sb.from('bookings').select('*').order('created_at',{ascending:false}).limit(20);
  var i=await sb.from('inquiries').select('*').order('created_at',{ascending:false}).limit(20);
  function tbl(rows,cols){if(!rows||!rows.length)return'<p>None yet.</p>';
    return'<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%"><tr>'+
    cols.map(c=>'<th>'+c+'</th>').join('')+'</tr>'+
    rows.map(r=>'<tr>'+cols.map(c=>'<td>'+(r[c]??'')+'</td>').join('')+'</tr>').join('')+'</table>';}
  bookings.innerHTML=b.error?b.error.message:tbl(b.data,['created_at','name','email','branch','status']);
  inquiries.innerHTML=i.error?i.error.message:tbl(i.data,['created_at','name','email','subject']);
  logout.onclick=async function(e){e.preventDefault();await SkaApi.adminSignOut();location.href='login.html';};
})();
</script></body></html>`;
  fs.writeFileSync(path.join(adminOut, 'dashboard.html'), dash);
  console.log('Built admin pages');
}

console.log('Building static site → docs/');
rmrf(OUT);
fs.mkdirSync(OUT, { recursive: true });

for (const [name, meta] of Object.entries(PAGES)) buildPage(name, meta);
buildAdmin();

for (const dir of COPY_DIRS) copyDir(path.join(ROOT, dir), path.join(OUT, dir));
copyDir(path.join(ROOT, 'admin', 'assets'), path.join(OUT, 'admin', 'assets'));
for (const file of COPY_FILES) {
  const src = path.join(ROOT, file);
  if (fs.existsSync(src)) fs.copyFileSync(src, path.join(OUT, file));
}
fs.writeFileSync(path.join(OUT, '.nojekyll'), '');
console.log('Done.');

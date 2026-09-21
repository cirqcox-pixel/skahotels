#!/usr/bin/env node
/** Quick Formspree endpoint smoke test */
const forms = [
  { name: 'Naguru', id: 'myegbgjy', branch: 'Naguru', cc: 'naguru.booking@skaboutiquebnb.com' },
  { name: 'Munyonyo', id: 'xzezenyo', branch: 'Munyonyo', cc: 'munyonyo.booking@skaboutiquebnb.com' },
];

const payload = (branch, cc) => ({
  _subject: `SKA Booking Request — ${branch}`,
  _replyto: 'smoke-test@example.com',
  _cc: `${cc},smoke-test@example.com`,
  type: 'booking',
  name: 'Smoke Test',
  email: 'smoke-test@example.com',
  phone: '+256700000000',
  branch,
  room_type: 'Standard Room',
  checkin: '2026-10-01',
  checkout: '2026-10-02',
  price: 150,
  total: 150,
  site: 'SKA The Boutique',
});

let ok = true;
for (const f of forms) {
  const url = `https://formspree.io/f/${f.id}`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload(f.branch, f.cc)),
  });
  const text = await res.text();
  const pass = res.ok || res.status === 200 || res.status === 302;
  console.log(`${f.name} (${f.id}): HTTP ${res.status} ${pass ? 'OK' : 'FAIL'}`);
  if (!pass) {
    console.log('  ', text.slice(0, 200));
    ok = false;
  }
}
process.exit(ok ? 0 : 1);

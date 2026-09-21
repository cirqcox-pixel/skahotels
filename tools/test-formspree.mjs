#!/usr/bin/env node
/** Formspree smoke test — Naguru must pass; Munyonyo tries xzezenyo then myegbgjy fallback */
const payload = (branch, cc, formId) => ({
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
  formspree_form: formId,
});

async function post(id, branch, cc) {
  const url = `https://formspree.io/f/${id}`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify(payload(branch, cc, id)),
  });
  return { id, status: res.status, ok: res.ok || res.status === 200 || res.status === 302 };
}

let ok = true;

const naguru = await post('myegbgjy', 'Naguru', 'naguru.booking@skaboutiquebnb.com');
console.log(`Naguru (myegbgjy): HTTP ${naguru.status} ${naguru.ok ? 'OK' : 'FAIL'}`);
if (!naguru.ok) ok = false;

const muny = await post('xzezenyo', 'Munyonyo', 'munyonyo.booking@skaboutiquebnb.com');
if (muny.ok) {
  console.log(`Munyonyo (xzezenyo): HTTP ${muny.status} OK`);
} else {
  console.log(`Munyonyo (xzezenyo): HTTP ${muny.status} — trying myegbgjy fallback`);
  const fb = await post('myegbgjy', 'Munyonyo', 'munyonyo.booking@skaboutiquebnb.com');
  console.log(`Munyonyo fallback (myegbgjy): HTTP ${fb.status} ${fb.ok ? 'OK' : 'FAIL'}`);
  if (!fb.ok) ok = false;
}

process.exit(ok ? 0 : 1);

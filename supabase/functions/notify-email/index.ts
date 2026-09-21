// SKA booking + inquiry mail. No Formspree.
// Naguru → naguru.booking@skaboutiquebnb.com
// Munyonyo → munyonyo.booking@skaboutiquebnb.com
// Inquiry → info@skaboutiquebnb.com
// Deploy: supabase functions deploy notify-email --no-verify-jwt

import { serve } from 'https://deno.land/std@0.177.0/http/server.ts';

const RESEND_API_KEY = Deno.env.get('RESEND_API_KEY') || '';
const NOTIFY_FROM = Deno.env.get('NOTIFY_FROM') || 'SKA The Boutique <onboarding@resend.dev>';

const cors = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

const NAGURU = 'naguru.booking@skaboutiquebnb.com';
const MUNYONYO = 'munyonyo.booking@skaboutiquebnb.com';
const INFO = 'info@skaboutiquebnb.com';

function json(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...cors, 'Content-Type': 'application/json' },
  });
}

function looksMunyonyo(value: string) {
  return /muny/i.test(value || '');
}

function adminInbox(type: string, branch: string) {
  if (type === 'inquiry' || type === 'inquiry_reply') return INFO;
  return looksMunyonyo(branch) ? MUNYONYO : NAGURU;
}

function esc(v: unknown) {
  return String(v ?? '—')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function details(data: Record<string, unknown>) {
  return [
    `Guest: ${data.name || '—'}`,
    `Email: ${data.email || '—'}`,
    `Phone: ${data.phone || '—'}`,
    `WhatsApp: ${data.whatsapp || '—'}`,
    `Property: ${data.branch || '—'}`,
    `Room / package: ${data.room_type || '—'}`,
    `Option: ${data.package_option || '—'}`,
    `Guests: ${data.guests || '—'}`,
    `Check-in: ${data.checkin || '—'}`,
    `Check-out: ${data.checkout || '—'}`,
    `Total: ${data.currency || 'USD'} ${data.total || data.price || 0}`,
    `Message: ${data.message || '—'}`,
  ].join('\n');
}

function htmlWrap(title: string, intro: string, text: string) {
  const rows = text.split('\n').map((line) => {
    const i = line.indexOf(':');
    if (i < 0) return `<p>${esc(line)}</p>`;
    return `<tr><td style="padding:6px 0;color:#888;width:38%;">${esc(line.slice(0, i))}</td><td style="padding:6px 0;color:#111;">${esc(line.slice(i + 1).trim())}</td></tr>`;
  }).join('');
  return `<!DOCTYPE html><html><body style="margin:0;background:#f6f3ee;font-family:Georgia,serif;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
    <div style="background:#0d1b2e;color:#c9a96e;padding:20px 28px;letter-spacing:.18em;font-size:11px;text-transform:uppercase;">SKA The Boutique</div>
    <div style="padding:28px;">
      <h1 style="font-weight:400;font-size:22px;color:#0d1b2e;margin:0 0 12px;">${esc(title)}</h1>
      <p style="color:#444;line-height:1.6;">${intro}</p>
      <table style="width:100%;border-collapse:collapse;margin:16px 0;">${rows}</table>
    </div>
  </div></body></html>`;
}

async function sendResend(to: string, subject: string, html: string, text: string, replyTo?: string, cc?: string) {
  const payload: Record<string, unknown> = {
    from: NOTIFY_FROM,
    to: [to],
    subject,
    html,
    text,
  };
  if (replyTo) payload.reply_to = replyTo;
  if (cc) payload.cc = [cc];
  const res = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${RESEND_API_KEY}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const result = await res.json();
  if (!res.ok) throw new Error(JSON.stringify(result));
  return result;
}

async function sendFormSubmit(to: string, payload: Record<string, unknown>) {
  const res = await fetch('https://formsubmit.co/ajax/' + encodeURIComponent(to), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      Origin: 'https://www.skaboutiquebnb.com',
      Referer: 'https://www.skaboutiquebnb.com/',
    },
    body: JSON.stringify(payload),
  });
  const text = await res.text();
  let parsed: { success?: string | boolean; message?: string } = {};
  try { parsed = JSON.parse(text); } catch { /* ignore */ }
  const ok = res.ok && String(parsed.success) !== 'false';
  if (!ok) throw new Error(parsed.message || text.slice(0, 180) || ('HTTP ' + res.status));
  return true;
}

serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: cors });

  try {
    const body = await req.json();
    const type = String(body.type || 'inquiry');
    const data = (body.data || {}) as Record<string, unknown>;
    const branch = String(data.branch || '');
    const to = adminInbox(type, branch);
    const guest = String(data.email || '').trim();
    const sent: string[] = [];

    let subject = `SKA Contact: ${data.subject || 'General Inquiry'}`;
    let intro = 'A new contact form message was submitted.';
    if (type === 'booking') {
      subject = `SKA Booking Request — ${branch || 'Property'}`;
      intro = 'A new reservation request needs review in the admin dashboard.';
    } else if (type === 'booking_confirmed') {
      subject = `SKA booking confirmed — ${data.name || 'Guest'}`;
      intro = 'This booking was confirmed in the admin dashboard.';
    } else if (type === 'booking_cancelled') {
      subject = `SKA booking cancelled — ${data.name || 'Guest'}`;
      intro = 'This booking was cancelled in the admin dashboard.';
    } else if (type === 'inquiry_reply') {
      subject = `Re: ${data.subject || 'Your SKA inquiry'}`;
      intro = String(data.reply || data.reply_message || data.message || '');
    }

    const text = type === 'inquiry_reply' ? intro : details(data);
    const html = htmlWrap(subject, type === 'inquiry_reply' ? 'Reply from SKA The Boutique:' : intro, text);
    const formPayload = {
      _subject: subject,
      _captcha: 'false',
      _template: 'table',
      _replyto: type === 'inquiry_reply' ? to : (guest || to),
      _cc: guest || '',
      name: data.name,
      email: guest,
      phone: data.phone,
      whatsapp: data.whatsapp,
      branch,
      room_type: data.room_type,
      package_option: data.package_option,
      guests: data.guests,
      checkin: data.checkin,
      checkout: data.checkout,
      total: `${data.currency || 'USD'} ${data.total || data.price || 0}`,
      staff_reply: String(data.reply || data.reply_message || ''),
      message: type === 'inquiry_reply' ? intro : (data.message || intro),
      type,
      notify_inbox: to,
    };

    if (RESEND_API_KEY) {
      await sendResend(to, subject, html, text, guest || undefined, guest || undefined);
      sent.push('resend:' + to);
      if (guest && guest.toLowerCase() !== to.toLowerCase()) {
        await sendResend(guest, subject, html, text, to);
        sent.push('resend:' + guest);
      }
    } else {
      await sendFormSubmit(to, formPayload);
      sent.push('formsubmit:' + to);
      if (guest && guest.toLowerCase() !== to.toLowerCase()) {
        await sendFormSubmit(guest, { ...formPayload, _cc: to, notify_inbox: guest });
        sent.push('formsubmit:' + guest);
      }
    }

    return json({ ok: true, to, sent });
  } catch (err) {
    return json({ ok: false, error: String(err) }, 500);
  }
});

// Booking + inquiry email via Resend, with Formspree fallback.
// Deploy: supabase functions deploy notify-email
// Secrets: RESEND_API_KEY (optional but preferred), NOTIFY_FROM

import { serve } from 'https://deno.land/std@0.177.0/http/server.ts';

const RESEND_API_KEY = Deno.env.get('RESEND_API_KEY') || '';
const NOTIFY_FROM = Deno.env.get('NOTIFY_FROM') || 'SKA The Boutique <onboarding@resend.dev>';
const FORMSPREE_ID = Deno.env.get('FORMSPREE_ID') || 'myegbgjy';

const cors = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

function json(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...cors, 'Content-Type': 'application/json' },
  });
}

function adminInbox(branch: string) {
  const b = (branch || '').toLowerCase();
  if (b.indexOf('munyonyo') >= 0) return 'munyonyo.booking@skaboutiquebnb.com';
  return 'naguru.booking@skaboutiquebnb.com';
}

function esc(v: unknown) {
  return String(v ?? '—')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function wrap(title: string, intro: string, rows: string, footer: string) {
  return `<!DOCTYPE html><html><body style="margin:0;background:#f6f3ee;font-family:Georgia,serif;padding:24px;">
  <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
    <div style="background:#0d1b2e;color:#c9a96e;padding:20px 28px;letter-spacing:.18em;font-size:11px;text-transform:uppercase;">SKA The Boutique</div>
    <div style="padding:28px;">
      <h1 style="font-weight:400;font-size:24px;color:#0d1b2e;margin:0 0 12px;">${esc(title)}</h1>
      <p style="color:#444;line-height:1.6;">${intro}</p>
      ${rows}
      <p style="color:#888;font-size:13px;line-height:1.6;">${footer}</p>
    </div>
  </div></body></html>`;
}

function detailsTable(data: Record<string, unknown>) {
  const rows: Array<[string, unknown]> = [
    ['Guest', data.name],
    ['Email', data.email],
    ['Phone', data.phone],
    ['WhatsApp', data.whatsapp],
    ['Property', data.branch],
    ['Room / package', data.room_type],
    ['Package option', data.package_option],
    ['Guests', data.guests],
    ['Check-in', data.checkin],
    ['Check-out', data.checkout],
    ['Total', `${data.currency || 'UGX'} ${data.total || data.price || 0}`],
    ['Message', data.message],
  ];
  const body = rows
    .filter(([, v]) => v !== undefined && v !== null && String(v) !== '')
    .map(([k, v]) => `<tr><td style="padding:8px 0;color:#888;width:40%;">${esc(k)}</td><td style="padding:8px 0;color:#111;">${esc(v)}</td></tr>`)
    .join('');
  return `<table style="width:100%;border-collapse:collapse;margin:16px 0;">${body}</table>`;
}

async function sendResend(to: string[], subject: string, html: string, text: string, replyTo?: string) {
  const res = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${RESEND_API_KEY}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      from: NOTIFY_FROM,
      to,
      reply_to: replyTo || undefined,
      subject,
      html,
      text,
    }),
  });
  const result = await res.json();
  if (!res.ok) throw new Error(JSON.stringify(result));
  return result;
}

async function sendFormspree(payload: Record<string, unknown>) {
  const res = await fetch('https://formspree.io/f/' + FORMSPREE_ID, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error('Formspree ' + res.status + ' ' + text.slice(0, 180));
  }
  return true;
}

function bookingText(data: Record<string, unknown>) {
  return [
    `Guest: ${data.name}`,
    `Email: ${data.email}`,
    `Phone: ${data.phone || '—'}`,
    `Property: ${data.branch || '—'}`,
    `Room / package: ${data.room_type || '—'}`,
    `Option: ${data.package_option || '—'}`,
    `Check-in: ${data.checkin || '—'}`,
    `Check-out: ${data.checkout || '—'}`,
    `Total: ${data.currency || ''} ${data.total || data.price || 0}`,
    `Message: ${data.message || '—'}`,
  ].join('\n');
}

serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: cors });

  try {
    const body = await req.json();
    const type = String(body.type || 'inquiry');
    const data = (body.data || {}) as Record<string, unknown>;
    const branchInbox = adminInbox(String(data.branch || body.to || ''));
    const guest = String(data.email || '').trim();
    const sent: string[] = [];

    const sendPair = async (adminTo: string, guestTo: string, adminSubject: string, guestSubject: string, adminIntro: string, guestIntro: string, guestFooter: string) => {
      const table = detailsTable(data);
      const text = bookingText(data);
      if (RESEND_API_KEY) {
        await sendResend([adminTo], adminSubject, wrap(adminSubject, adminIntro, table, 'Reply to this email to contact the guest.'), text, guest || undefined);
        sent.push('resend-admin');
        if (guestTo) {
          await sendResend(
            [guestTo],
            guestSubject,
            wrap(guestSubject, guestIntro, table, guestFooter),
            text,
            adminTo,
          );
          sent.push('resend-guest');
        }
        return;
      }
      await sendFormspree({
        _subject: adminSubject,
        _replyto: guest || adminTo,
        _cc: [adminTo, guestTo].filter(Boolean).join(','),
        type,
        ...data,
        message: `${adminIntro}\n\n${text}`,
      });
      sent.push('formspree');
    };

    if (type === 'booking') {
      await sendPair(
        branchInbox,
        guest,
        `SKA Booking Request — ${data.branch || 'Property'}`,
        'We received your SKA reservation request',
        'A new reservation request needs your review in the admin dashboard.',
        `Dear <strong>${esc(data.name)}</strong>, thank you for choosing SKA The Boutique ${esc(data.branch)}. We have received your request and will confirm within 24 hours.`,
        'Questions? Reply to this email or call the property.',
      );
    } else if (type === 'booking_confirmed') {
      await sendPair(
        branchInbox,
        guest,
        `SKA booking confirmed — ${data.name || 'Guest'}`,
        'Your SKA reservation is confirmed',
        'This booking was confirmed in the admin dashboard. The guest has been emailed.',
        `Dear <strong>${esc(data.name)}</strong>, your stay at SKA The Boutique ${esc(data.branch)} is confirmed. We look forward to welcoming you.`,
        'Please keep this email for your records.',
      );
    } else if (type === 'booking_cancelled') {
      await sendPair(
        branchInbox,
        guest,
        `SKA booking cancelled — ${data.name || 'Guest'}`,
        'Update on your SKA reservation request',
        'This booking was cancelled in the admin dashboard.',
        `Dear <strong>${esc(data.name)}</strong>, unfortunately we are unable to confirm your reservation at SKA The Boutique ${esc(data.branch)}.`,
        String(data.status_reason || data.reason || 'Please reply if you would like alternative dates.'),
      );
    } else {
      const subject = `SKA Contact: ${data.subject || 'General Inquiry'}`;
      const text = `From: ${data.name} <${data.email}>\nPhone: ${data.phone || '—'}\n\n${data.message || ''}`;
      if (RESEND_API_KEY) {
        await sendResend(
          [String(body.to || 'info@skaboutiquebnb.com')],
          subject,
          wrap(subject, 'A new website inquiry was submitted.', `<p>${esc(data.message)}</p>`, ''),
          text,
          guest || undefined,
        );
        sent.push('resend-admin');
      } else {
        await sendFormspree({
          _subject: subject,
          _replyto: guest,
          type: 'inquiry',
          ...data,
        });
        sent.push('formspree');
      }
    }

    return json({ ok: true, sent, via: RESEND_API_KEY ? 'resend' : 'formspree' });
  } catch (err) {
    return json({ error: String(err) }, 500);
  }
});

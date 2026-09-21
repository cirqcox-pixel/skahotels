// Booking + inquiry email via Resend, with Formspree fallback.
// Deploy: supabase functions deploy notify-email
// Secrets: RESEND_API_KEY (optional but preferred), NOTIFY_FROM

import { serve } from 'https://deno.land/std@0.177.0/http/server.ts';

const RESEND_API_KEY = Deno.env.get('RESEND_API_KEY') || '';
const NOTIFY_FROM = Deno.env.get('NOTIFY_FROM') || 'SKA The Boutique <onboarding@resend.dev>';
const FORMSPREE_NAGURU = Deno.env.get('FORMSPREE_NAGURU') || 'myegbgjy';
const FORMSPREE_MUNYONYO_PROJECT = Deno.env.get('FORMSPREE_MUNYONYO_PROJECT') || '3095670307009069001';
const FORMSPREE_MUNYONYO_FORM = Deno.env.get('FORMSPREE_MUNYONYO_FORM') || 'skaMunyonyoBooking';

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

const MUNYONYO_BOOKING = 'munyonyo.booking@skaboutiquebnb.com';
const NAGURU_BOOKING = 'naguru.booking@skaboutiquebnb.com';
const INFO_INBOX = 'info@skaboutiquebnb.com';

function looksMunyonyo(value: string) {
  return /muny/i.test(value || '');
}

function adminInbox(branch: string, hint = '') {
  const blob = `${branch || ''} ${hint || ''}`;
  if (looksMunyonyo(blob)) return MUNYONYO_BOOKING;
  return NAGURU_BOOKING;
}

function uniqEmails(list: string[]) {
  const seen = new Set<string>();
  const out: string[] = [];
  for (const raw of list) {
    const email = String(raw || '').trim();
    if (!email || email.indexOf('@') < 0) continue;
    const key = email.toLowerCase();
    if (seen.has(key)) continue;
    seen.add(key);
    out.push(email);
  }
  return out;
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

async function sendResend(to: string[], subject: string, html: string, text: string, replyTo?: string, cc?: string[]) {
  const payload: Record<string, unknown> = {
    from: NOTIFY_FROM,
    to,
    reply_to: replyTo || undefined,
    subject,
    html,
    text,
  };
  if (cc && cc.length) payload.cc = cc;
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

function formspreeBookingUrl(branch: string) {
  if (looksMunyonyo(branch)) {
    return `https://formspree.io/p/${FORMSPREE_MUNYONYO_PROJECT}/f/${FORMSPREE_MUNYONYO_FORM}`;
  }
  return `https://formspree.io/f/${FORMSPREE_NAGURU}`;
}

function bookingFormspreePayload(type: string, data: Record<string, unknown>) {
  const branch = String(data.branch || 'Property');
  const guest = String(data.email || '').trim();
  const prefix = type === 'booking_confirmed'
    ? 'SKA Booking Confirmed — '
    : type === 'booking_cancelled'
      ? 'SKA Booking Cancelled — '
      : 'SKA Booking Request — ';
  const cc = looksMunyonyo(branch)
    ? guest
    : [adminInbox(branch), guest].filter(Boolean).join(',');
  return {
    _subject: prefix + branch,
    _replyto: guest,
    _cc: cc,
    type: 'booking',
    name: data.name,
    email: data.email,
    phone: data.phone || '',
    whatsapp: data.whatsapp || '',
    branch,
    room_type: data.room_type || '',
    package_option: data.package_option || '',
    guests: data.guests || '',
    currency: data.currency || '',
    checkin: data.checkin || '',
    checkout: data.checkout || '',
    price: data.price || '',
    total: data.total || '',
    season: data.season || '',
    message: data.message || '',
    site: 'SKA The Boutique',
  };
}

async function sendFormspree(payload: Record<string, unknown>, branch = '') {
  const url = branch ? formspreeBookingUrl(branch) : `https://formspree.io/f/${FORMSPREE_NAGURU}`;
  const res = await fetch(url, {
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
    const branchInbox = adminInbox(
      String(data.branch || ''),
      String(data.notify_email || body.to || ''),
    );
    const guest = String(data.email || '').trim();
    const sent: string[] = [];

    const sendPair = async (adminTo: string, guestTo: string, adminSubject: string, guestSubject: string, adminIntro: string, guestIntro: string, guestFooter: string) => {
      const table = detailsTable(data);
      const text = bookingText(data);
      const muny = looksMunyonyo(String(data.branch || adminTo || ''));
      const adminRecipients = uniqEmails([
        adminTo,
        muny ? MUNYONYO_BOOKING : '',
      ]);
      if (RESEND_API_KEY) {
        let adminOk = false;
        for (const addr of adminRecipients) {
          try {
            await sendResend(
              [addr],
              adminSubject,
              wrap(adminSubject, adminIntro, table, 'Reply to this email to contact the guest.'),
              text,
              guest || undefined,
            );
            sent.push('resend-admin:' + addr);
            adminOk = true;
          } catch {
            sent.push('resend-admin-failed:' + addr);
          }
        }
        if (!adminOk && muny) {
          try {
            await sendResend(
              [INFO_INBOX],
              `${adminSubject} — deliver to ${MUNYONYO_BOOKING}`,
              wrap(adminSubject, `Please forward this Munyonyo booking to ${esc(MUNYONYO_BOOKING)}.<br><br>${adminIntro}`, table, 'Reply to this email to contact the guest.'),
              text,
              guest || undefined,
            );
            sent.push('resend-admin-fallback');
          } catch {
            try {
              await sendFormspree({
                _subject: adminSubject,
                _replyto: guest || adminTo,
                _cc: uniqEmails([adminTo, MUNYONYO_BOOKING, guestTo]).join(','),
                type,
                ...data,
                message: `${adminIntro}\n\n${text}`,
              }, String(data.branch || ''));
              sent.push('formspree-admin');
            } catch {
              /* last resort already attempted */
            }
          }
        }
        if (guestTo) {
          try {
            await sendResend(
              [guestTo],
              guestSubject,
              wrap(guestSubject, guestIntro, table, guestFooter),
              text,
              adminTo,
            );
            sent.push('resend-guest');
          } catch {
            sent.push('resend-guest-failed');
          }
        }
        return;
      }
      await sendFormspree({
        _subject: adminSubject,
        _replyto: guest || adminTo,
        _cc: uniqEmails([adminTo, muny ? MUNYONYO_BOOKING : '', guestTo]).join(','),
        type,
        ...data,
        message: `${adminIntro}\n\n${text}`,
      }, String(data.branch || ''));
      sent.push('formspree');
    };

    if (type === 'booking' || type === 'booking_confirmed' || type === 'booking_cancelled') {
      try {
        await sendFormspree(bookingFormspreePayload(type, data), String(data.branch || ''));
        sent.push('formspree-booking');
      } catch (e) {
        sent.push('formspree-booking-failed');
      }
    }

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
    } else if (type === 'inquiry_reply') {
      const info = String(body.to || data.site_email || 'info@skaboutiquebnb.com');
      const reply = String(data.reply || data.reply_message || '');
      const subject = `Re: ${data.subject || 'Your SKA inquiry'}`;
      const intro = `Dear <strong>${esc(data.name)}</strong>, here is our reply to your message.`;
      const table = `<p style="line-height:1.7;color:#333;">${esc(reply).replace(/\n/g, '<br>')}</p>`;
      const text = reply;
      if (RESEND_API_KEY && guest) {
        await sendResend([guest], subject, wrap(subject, intro, table, 'You can reply to this email to continue the conversation.'), text, info);
        sent.push('resend-guest');
      } else {
        await sendFormspree({
          _subject: subject,
          _replyto: info,
          _cc: guest,
          type: 'inquiry_reply',
          name: data.name,
          email: guest,
          message: reply,
        });
        sent.push('formspree');
      }
    } else {
      const info = String(body.to || data.site_email || 'info@skaboutiquebnb.com');
      await sendPair(
        info,
        guest,
        `SKA Contact: ${data.subject || 'General Inquiry'}`,
        'We received your message — SKA The Boutique',
        'A new contact form message needs a reply in the admin Inquiries page.',
        `Dear <strong>${esc(data.name)}</strong>, thank you for writing to SKA The Boutique. We have received your message and will reply within 24 hours.`,
        'You can reply to this email if you need to add more detail.',
      );
    }

    return json({ ok: true, sent, via: RESEND_API_KEY ? 'resend' : 'formspree' });
  } catch (err) {
    return json({ error: String(err) }, 500);
  }
});

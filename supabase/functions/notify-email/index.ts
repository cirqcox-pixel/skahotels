// Supabase Edge Function — email via Resend
// Deploy: supabase functions deploy notify-email
// Secrets: supabase secrets set RESEND_API_KEY=re_xxx NOTIFY_FROM="SKA <onboarding@resend.dev>" NOTIFY_TO=info@skaboutiquebnb.com

import { serve } from 'https://deno.land/std@0.177.0/http/server.ts';

const RESEND_API_KEY = Deno.env.get('RESEND_API_KEY') || '';
const NOTIFY_FROM = Deno.env.get('NOTIFY_FROM') || 'SKA The Boutique <onboarding@resend.dev>';
const NOTIFY_TO = Deno.env.get('NOTIFY_TO') || 'info@skaboutiquebnb.com';

const cors = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

serve(async (req) => {
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: cors });
  }

  try {
    if (!RESEND_API_KEY) {
      return new Response(JSON.stringify({ error: 'RESEND_API_KEY not set' }), {
        status: 500,
        headers: { ...cors, 'Content-Type': 'application/json' },
      });
    }

    const body = await req.json();
    const type = body.type || 'inquiry';
    const data = body.data || {};
    const to = body.to || NOTIFY_TO;

    let subject = 'SKA website message';
    let text = '';

    if (type === 'booking') {
      subject = `SKA Booking Request — ${data.branch || 'Property'}`;
      text = [
        'New reservation request',
        '',
        `Name: ${data.name}`,
        `Email: ${data.email}`,
        `Phone: ${data.phone || '—'}`,
        `WhatsApp: ${data.whatsapp || '—'}`,
        `Branch: ${data.branch || '—'}`,
        `Room: ${data.room_type || '—'}`,
        `Check-in: ${data.checkin || '—'}`,
        `Check-out: ${data.checkout || '—'}`,
        `Nightly: USD ${data.price || 0}`,
        `Total: USD ${data.total || 0}`,
        `Season: ${data.season || '—'}`,
        '',
        `Message: ${data.message || '—'}`,
      ].join('\n');
    } else {
      subject = `SKA Contact: ${data.subject || 'General Inquiry'}`;
      text = [
        'New contact inquiry',
        '',
        `Name: ${data.name}`,
        `Email: ${data.email}`,
        `Phone: ${data.phone || '—'}`,
        `Subject: ${data.subject || '—'}`,
        '',
        data.message || '',
      ].join('\n');
    }

    const res = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${RESEND_API_KEY}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        from: NOTIFY_FROM,
        to: [to],
        reply_to: data.email || undefined,
        subject,
        text,
      }),
    });

    const result = await res.json();
    if (!res.ok) {
      return new Response(JSON.stringify({ error: result }), {
        status: 502,
        headers: { ...cors, 'Content-Type': 'application/json' },
      });
    }

    return new Response(JSON.stringify({ ok: true, id: result.id }), {
      headers: { ...cors, 'Content-Type': 'application/json' },
    });
  } catch (err) {
    return new Response(JSON.stringify({ error: String(err) }), {
      status: 500,
      headers: { ...cors, 'Content-Type': 'application/json' },
    });
  }
});

// Super-admin staff invite: records access, then emails a sign-in / set-password link.
// Deploy: supabase functions deploy invite-staff
// Optional: RESEND_API_KEY + NOTIFY_FROM (falls back to Supabase Auth mail)

import { serve } from 'https://deno.land/std@0.177.0/http/server.ts';
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2.45.4';

const cors = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
};

const ALLOWED_HOSTS = [
  'www.skaboutiquebnb.com',
  'skaboutiquebnb.com',
  'cirqcox-pixel.github.io',
  'localhost',
  '127.0.0.1',
];

function json(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...cors, 'Content-Type': 'application/json' },
  });
}

function pagesForRole(role: string): string[] {
  switch (role) {
    case 'super_admin':
      return ['dashboard', 'rooms', 'promotions', 'packages', 'bookings', 'inquiries', 'users'];
    case 'manager':
      return ['dashboard', 'rooms', 'promotions', 'packages', 'bookings', 'inquiries'];
    case 'reservations':
      return ['dashboard', 'bookings', 'inquiries'];
    case 'marketing':
      return ['dashboard', 'promotions', 'packages'];
    default:
      return ['dashboard', 'bookings'];
  }
}

function roleLabel(role: string): string {
  const labels: Record<string, string> = {
    super_admin: 'Super Admin',
    manager: 'Manager',
    reservations: 'Reservations',
    marketing: 'Marketing',
  };
  return labels[role] || role;
}

function safeRedirect(raw: string): string {
  const fallback = 'https://www.skaboutiquebnb.com/admin/login.html?set_password=1';
  try {
    const u = new URL(raw);
    const hostOk = ALLOWED_HOSTS.some((h) => u.hostname === h);
    if (!hostOk) return fallback;
    if (!/login\.html$/i.test(u.pathname)) return fallback;
    u.searchParams.set('set_password', '1');
    return u.toString();
  } catch {
    return fallback;
  }
}

async function findAuthUser(admin: ReturnType<typeof createClient>, email: string) {
  const anyAdmin = admin.auth.admin as unknown as {
    getUserByEmail?: (e: string) => Promise<{ data: { user: { id: string; email?: string } | null }; error: unknown }>;
    listUsers: (opts: { page: number; perPage: number }) => Promise<{ data: { users: Array<{ id: string; email?: string }> } }>;
  };
  if (typeof anyAdmin.getUserByEmail === 'function') {
    const { data, error } = await anyAdmin.getUserByEmail(email);
    if (!error && data?.user) return data.user;
  }
  const listed = await anyAdmin.listUsers({ page: 1, perPage: 1000 });
  return (listed.data?.users || []).find((u) => (u.email || '').toLowerCase() === email) || null;
}

async function sendResend(to: string, subject: string, html: string, text: string) {
  const key = Deno.env.get('RESEND_API_KEY') || '';
  if (!key) return false;
  const from = Deno.env.get('NOTIFY_FROM') || 'SKA The Boutique <onboarding@resend.dev>';
  const res = await fetch('https://api.resend.com/emails', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${key}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ from, to: [to], subject, html, text }),
  });
  if (!res.ok) {
    const err = await res.text();
    throw new Error('Resend failed: ' + err);
  }
  return true;
}

function inviteHtml(link: string, role: string) {
  return `<!DOCTYPE html><html><body style="font-family:Georgia,serif;background:#f6f3ee;padding:24px;">
  <div style="max-width:520px;margin:0 auto;background:#fff;padding:32px;border-radius:12px;">
    <p style="letter-spacing:.2em;text-transform:uppercase;font-size:11px;color:#c9a96e;margin:0 0 12px;">SKA The Boutique</p>
    <h1 style="font-weight:400;font-size:26px;color:#0d1b2e;margin:0 0 16px;">You are invited to the admin dashboard</h1>
    <p style="color:#444;line-height:1.6;">Your role is <strong>${role}</strong>. Open the link below to set your password and sign in. The link expires, so use it soon.</p>
    <p style="margin:28px 0;"><a href="${link}" style="display:inline-block;background:#0d1b2e;color:#fff;text-decoration:none;padding:12px 22px;border-radius:999px;">Set password &amp; sign in</a></p>
    <p style="font-size:12px;color:#888;word-break:break-all;">${link}</p>
  </div>
  </body></html>`;
}

serve(async (req) => {
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: cors });
  }

  try {
    const authHeader = req.headers.get('Authorization') || '';
    const supabaseUrl = Deno.env.get('SUPABASE_URL') || '';
    const anonKey = Deno.env.get('SUPABASE_ANON_KEY') || '';
    const serviceKey = Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') || '';
    if (!supabaseUrl || !anonKey || !serviceKey) {
      return json({ error: 'Server is missing Supabase keys.' }, 500);
    }

    const userClient = createClient(supabaseUrl, anonKey, {
      global: { headers: { Authorization: authHeader } },
    });
    const { data: userData, error: userErr } = await userClient.auth.getUser();
    if (userErr || !userData.user) {
      return json({ error: 'Sign in required.' }, 401);
    }

    const admin = createClient(supabaseUrl, serviceKey, {
      auth: { persistSession: false, autoRefreshToken: false },
    });

    const { data: me } = await admin
      .from('admin_users')
      .select('uid, email, role')
      .eq('uid', userData.user.id)
      .maybeSingle();
    if (!me || me.role !== 'super_admin') {
      return json({ error: 'Only a Super Admin can invite staff.' }, 403);
    }

    const body = await req.json().catch(() => ({}));
    const email = String(body.email || '').trim().toLowerCase();
    let role = String(body.role || 'manager').trim().toLowerCase();
    const resendOnly = body.resend === true;
    if (!email || !email.includes('@')) {
      return json({ error: 'A valid email is required.' }, 400);
    }
    if (!['super_admin', 'manager', 'reservations', 'marketing'].includes(role)) {
      role = 'manager';
    }

    const pages = pagesForRole(role);
    const existing = await findAuthUser(admin, email);
    let status = existing ? 'active' : 'invited';

    if (existing) {
      await admin.from('admin_users').upsert({
        uid: existing.id,
        email,
        role,
        pages,
      });
      await admin.from('admin_invites').delete().eq('email', email);
    } else {
      const { error: invErr } = await admin.from('admin_invites').upsert(
        { email, role, pages },
        { onConflict: 'email' },
      );
      if (invErr) {
        return json({ error: invErr.message || 'Could not save invite.' }, 500);
      }
    }

    const redirectTo = safeRedirect(String(body.redirectTo || ''));
    const isNew = !existing;
    let emailed = false;
    let provider = '';

    const resendKey = Deno.env.get('RESEND_API_KEY') || '';
    if (resendKey) {
      const linkType = isNew ? 'invite' : 'recovery';
      const { data: linkData, error: linkErr } = await admin.auth.admin.generateLink({
        type: linkType,
        email,
        options: { redirectTo },
      });
      if (linkErr) {
        return json({ error: linkErr.message, ok: true, status, emailed: false }, 200);
      }
      const props = (linkData as { properties?: { action_link?: string } })?.properties;
      const actionLink = props?.action_link;
      if (!actionLink) {
        return json({ error: 'Could not build invite link.', ok: true, status, emailed: false }, 200);
      }
      const label = roleLabel(role);
      await sendResend(
        email,
        'Your SKA Admin invitation',
        inviteHtml(actionLink, label),
        `You are invited to the SKA Admin dashboard as ${label}. Set your password: ${actionLink}`,
      );
      emailed = true;
      provider = 'resend';
    } else if (isNew) {
      const { error: inviteErr } = await admin.auth.admin.inviteUserByEmail(email, {
        redirectTo,
        data: { role },
      });
      if (inviteErr) {
        return json({ error: inviteErr.message, ok: true, status, emailed: false }, 200);
      }
      emailed = true;
      provider = 'supabase_invite';
    } else {
      const { error: recErr } = await admin.auth.resetPasswordForEmail(email, { redirectTo });
      if (recErr) {
        return json({ error: recErr.message, ok: true, status, emailed: false }, 200);
      }
      emailed = true;
      provider = 'supabase_recovery';
    }

    return json({
      ok: true,
      status,
      emailed,
      provider,
      resend: resendOnly,
    });
  } catch (err) {
    return json({ error: String(err) }, 500);
  }
});

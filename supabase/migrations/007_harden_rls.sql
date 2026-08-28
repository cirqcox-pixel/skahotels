-- SKA Hotels — RLS hardening
-- Run in Supabase SQL Editor AFTER 001–006.
-- Dashboard: https://supabase.com/dashboard/project/nllqkepymtwwbvbjnbyz/sql
--
-- What this migration does
--   1. Introduces real admin authorization (an allowlist + is_admin() helper),
--      replacing the flat "any logged-in user = superadmin" rule.
--   2. Scopes public read of site_settings to non-sensitive groups only.
--   3. Constrains public INSERTs on bookings & inquiries (anti-abuse / integrity).
--   4. Locks down (optionally drops) the legacy `admins` table on Supabase.
--
-- SAFE TO RE-RUN: every policy is dropped-if-exists then recreated.
--
-- ┌───────────────────────────────────────────────────────────────────────────┐
-- │ ALSO DO THIS (dashboard, one-time, closes the critical remote-admin path): │
-- │   Authentication → Sign In / Providers → DISABLE public email sign-ups.    │
-- │   Admins must be created by invite only. RLS below is defence-in-depth.     │
-- └───────────────────────────────────────────────────────────────────────────┘

BEGIN;

-- ════════════════════════════════════════════════════════════════════════════
-- 1. ADMIN AUTHORIZATION MODEL
-- ════════════════════════════════════════════════════════════════════════════

-- Allowlist of authorized admin auth users. Being able to log in is NOT enough;
-- the user's id must be present here to gain write access.
CREATE TABLE IF NOT EXISTS public.admin_users (
  uid        UUID PRIMARY KEY REFERENCES auth.users (id) ON DELETE CASCADE,
  email      TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Membership check. SECURITY DEFINER so it reads admin_users regardless of RLS
-- (prevents infinite recursion when admin_users' own policies call it).
CREATE OR REPLACE FUNCTION public.is_admin()
RETURNS BOOLEAN
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path = public
AS $$
  SELECT EXISTS (
    SELECT 1 FROM public.admin_users WHERE uid = auth.uid()
  );
$$;

REVOKE ALL ON FUNCTION public.is_admin() FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.is_admin() TO anon, authenticated;

-- admin_users is itself admin-only.
ALTER TABLE public.admin_users ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "admin_read_admin_users"   ON public.admin_users;
DROP POLICY IF EXISTS "admin_manage_admin_users" ON public.admin_users;
CREATE POLICY "admin_read_admin_users" ON public.admin_users
  FOR SELECT USING (public.is_admin());
CREATE POLICY "admin_manage_admin_users" ON public.admin_users
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- ─────────────────────────────────────────────────────────────────────────────
-- ⚠️  BOOTSTRAP THE FIRST ADMIN  ⚠️
-- The allowlist starts EMPTY, so until you add yourself NO ONE can write.
-- 1) Create your admin user in Authentication → Users (or sign in once).
-- 2) Edit the email below and run this one statement:
--
--   INSERT INTO public.admin_users (uid, email)
--   SELECT id, email FROM auth.users WHERE email = 'you@skaboutiquebnb.com'
--   ON CONFLICT (uid) DO NOTHING;
-- ─────────────────────────────────────────────────────────────────────────────

-- ════════════════════════════════════════════════════════════════════════════
-- 2. REPLACE ALL ADMIN POLICIES WITH is_admin() (standardizes the mixed
--    auth.role()/auth.uid() predicates from 003 & 005)
-- ════════════════════════════════════════════════════════════════════════════

-- rooms
DROP POLICY IF EXISTS "admin_all_rooms" ON rooms;
CREATE POLICY "admin_all_rooms" ON rooms
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- room_images
DROP POLICY IF EXISTS "admin_all_room_images" ON room_images;
CREATE POLICY "admin_all_room_images" ON room_images
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- room_amenities
DROP POLICY IF EXISTS "admin_all_room_amenities" ON room_amenities;
CREATE POLICY "admin_all_room_amenities" ON room_amenities
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- bookings
DROP POLICY IF EXISTS "admin_all_bookings" ON bookings;
CREATE POLICY "admin_all_bookings" ON bookings
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- promotions
DROP POLICY IF EXISTS "admin_all_promotions" ON promotions;
CREATE POLICY "admin_all_promotions" ON promotions
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- inquiries
DROP POLICY IF EXISTS "admin_all_inquiries" ON inquiries;
CREATE POLICY "admin_all_inquiries" ON inquiries
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- site_settings
DROP POLICY IF EXISTS "admin_all_site_settings" ON site_settings;
CREATE POLICY "admin_all_site_settings" ON site_settings
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- cms_pages
DROP POLICY IF EXISTS "admin_all_cms_pages" ON cms_pages;
CREATE POLICY "admin_all_cms_pages" ON cms_pages
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- cms_blocks
DROP POLICY IF EXISTS "admin_all_cms_blocks" ON cms_blocks;
CREATE POLICY "admin_all_cms_blocks" ON cms_blocks
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- property_gallery
DROP POLICY IF EXISTS "admin_all_gallery" ON property_gallery;
CREATE POLICY "admin_all_gallery" ON property_gallery
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

-- ════════════════════════════════════════════════════════════════════════════
-- 3. SCOPE PUBLIC READ OF site_settings TO SAFE GROUPS ONLY
--    (prevents any future secret stored as a setting from being world-readable)
-- ════════════════════════════════════════════════════════════════════════════
DROP POLICY IF EXISTS "public_read_site_settings" ON site_settings;
CREATE POLICY "public_read_site_settings" ON site_settings
  FOR SELECT USING (
    setting_group IN ('contact', 'social', 'homepage', 'general', 'seo', 'branding')
  );
-- NOTE: store any secret (SMTP/API/webhook) under a group NOT in this list
--       (e.g. 'private') — admins still read everything via admin_all_site_settings.

-- ════════════════════════════════════════════════════════════════════════════
-- 4. CONSTRAIN PUBLIC INSERTS (anti-spam / integrity — the client can no longer
--    be trusted to set status, and raw API callers can't inject arbitrary rows)
-- ════════════════════════════════════════════════════════════════════════════

-- bookings: force pending status, sane amounts, valid dates, bounded text
DROP POLICY IF EXISTS "public_insert_bookings" ON bookings;
CREATE POLICY "public_insert_bookings" ON bookings
  FOR INSERT WITH CHECK (
    status = 'pending'
    AND char_length(name)  BETWEEN 1 AND 200
    AND char_length(email) BETWEEN 3 AND 320
    AND COALESCE(char_length(message), 0) <= 2000
    AND COALESCE(price, 0) >= 0
    AND COALESCE(total, 0) >= 0
    AND checkin  >= CURRENT_DATE - 1
    AND checkout >  checkin
  );

-- inquiries: force unread, bounded text
DROP POLICY IF EXISTS "public_insert_inquiries" ON inquiries;
CREATE POLICY "public_insert_inquiries" ON inquiries
  FOR INSERT WITH CHECK (
    is_read = false
    AND char_length(name)    BETWEEN 1 AND 200
    AND char_length(email)   BETWEEN 3 AND 320
    AND char_length(message) BETWEEN 1 AND 2000
  );

-- ════════════════════════════════════════════════════════════════════════════
-- 5. LEGACY `admins` TABLE (used only by the cPanel/PHP deployment, never by the
--    Supabase/GitHub-Pages path). Remove its Supabase attack surface.
-- ════════════════════════════════════════════════════════════════════════════
-- Revoke the broad access it had under 003 (any authenticated user could read
-- and manage it). With RLS on and no policies, it is deny-all on Supabase.
DROP POLICY IF EXISTS "admin_read_admins"   ON admins;
DROP POLICY IF EXISTS "admin_manage_admins" ON admins;

-- RECOMMENDED (irreversible): if this Supabase project is GitHub-Pages-only and
-- never uses the PHP admin, drop the table entirely. Uncomment to apply:
--   DROP TABLE IF EXISTS admins;

COMMIT;

-- ── Verify (optional) ───────────────────────────────────────────────────────
-- SELECT tablename, policyname, cmd, qual, with_check
--   FROM pg_policies WHERE schemaname = 'public' ORDER BY tablename, policyname;
-- SELECT * FROM public.admin_users;

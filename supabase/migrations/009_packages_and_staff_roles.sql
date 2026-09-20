-- Packages table (safe if 008 already ran) + staff roles / invites
-- Project: nllqkepymtwwbvbjnbyz

-- ── Packages ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS packages (
  id              BIGSERIAL PRIMARY KEY,
  title           TEXT NOT NULL,
  tag             TEXT,
  description     TEXT,
  inclusions      TEXT,
  options         TEXT,
  currency        TEXT NOT NULL DEFAULT 'UGX',
  price           NUMERIC(12,2) DEFAULT 0,
  pricing_mode    TEXT NOT NULL DEFAULT 'fixed',
  branch          TEXT NOT NULL,
  image           TEXT,
  booking_url     TEXT,
  active          BOOLEAN NOT NULL DEFAULT TRUE,
  valid_from      DATE,
  valid_to        DATE,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_packages_branch ON packages(branch);

ALTER TABLE bookings ADD COLUMN IF NOT EXISTS package_id BIGINT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS package_option TEXT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS currency TEXT DEFAULT 'USD';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guests INT;

ALTER TABLE packages ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "public_read_active_packages" ON packages;
CREATE POLICY "public_read_active_packages" ON packages
  FOR SELECT USING (
    active = true
    AND (valid_from IS NULL OR valid_from <= CURRENT_DATE)
    AND (valid_to IS NULL OR valid_to >= CURRENT_DATE)
  );

DROP POLICY IF EXISTS "admin_all_packages" ON packages;
CREATE POLICY "admin_all_packages" ON packages
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

INSERT INTO packages (title, tag, description, inclusions, options, currency, price, pricing_mode, branch, image, booking_url, active, sort_order)
SELECT
  'Conference Package Menu',
  'Corporate',
  'Delegate and boardroom packages for agendas that run from the morning briefing through to closing remarks — or a focused half-day session.',
  E'Break teas (tea, coffee and assorted bites)\nHydration — mineral water per delegate\nStationery — notebook and executive pen\nLunch — main buffet or plated meal with a soda or mineral water\nEquipment — PA system set up in your meeting room',
  '[{"label":"Full Day Conference Package","price":120000,"detail":"Per delegate, per day","pricing":"per_person"},{"label":"Half Day Conference Package","price":100000,"detail":"Per delegate, per session","pricing":"per_person"},{"label":"Video Conferencing","price":150000,"detail":"Connect remote and hybrid attendees","pricing":"fixed"},{"label":"Boardroom Hire — Half Day","price":300000,"detail":"Exclusive morning or afternoon session","pricing":"fixed"},{"label":"Boardroom Hire — Full Day","price":450000,"detail":"Exclusive use for private meetings and interviews","pricing":"fixed"}]',
  'UGX', 120000, 'per_person', 'Naguru',
  'assets/images/packages/conference-package.jpg',
  'naguru.html?package=conference#book', true, 1
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE title = 'Conference Package Menu' AND branch = 'Naguru');

INSERT INTO packages (title, tag, description, inclusions, options, currency, price, pricing_mode, branch, image, booking_url, active, sort_order)
SELECT
  'Get Wedding Ready With Your Tribe',
  'Wedding',
  'Sleep here, stress less, slay the wedding. Exclusive-use and luxury group stays with dinner and breakfast included.',
  E'Exclusive use of all 6 beautifully appointed rooms (full buyout)\nAccommodation for up to 12 guests\nDinner for all guests\nBreakfast the following morning\nComplete privacy throughout your stay\nA peaceful and intimate atmosphere exclusively for your celebration',
  '[{"label":"Exclusive stay — 12 guests (all 6 rooms)","price":1680000,"detail":"Dinner and breakfast included","pricing":"fixed"},{"label":"Luxury 2 Pax","price":300000,"detail":"1 room, dinner and breakfast included","pricing":"fixed"},{"label":"Luxury 4 Pax","price":560000,"detail":"2 rooms, dinner and breakfast included","pricing":"fixed"},{"label":"Luxury 6 Pax","price":840000,"detail":"3 rooms, dinner and breakfast included","pricing":"fixed"},{"label":"Additional room (2 guests sharing)","price":280000,"detail":"Dinner and breakfast included","pricing":"fixed"}]',
  'UGX', 1680000, 'fixed', 'Naguru',
  'assets/images/packages/wedding-package.jpg',
  'naguru.html?package=wedding#book', true, 2
WHERE NOT EXISTS (SELECT 1 FROM packages WHERE title = 'Get Wedding Ready With Your Tribe' AND branch = 'Naguru');

-- ── Staff roles ──────────────────────────────────────────────────────────────
ALTER TABLE public.admin_users ADD COLUMN IF NOT EXISTS role TEXT NOT NULL DEFAULT 'super_admin';
ALTER TABLE public.admin_users ADD COLUMN IF NOT EXISTS pages TEXT[];

UPDATE public.admin_users
SET role = COALESCE(NULLIF(role, ''), 'super_admin')
WHERE role IS NULL OR role = '';

CREATE TABLE IF NOT EXISTS public.admin_invites (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email       TEXT NOT NULL UNIQUE,
  role        TEXT NOT NULL DEFAULT 'manager',
  pages       TEXT[],
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE public.admin_invites ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "admin_all_invites" ON public.admin_invites;
CREATE POLICY "admin_all_invites" ON public.admin_invites
  FOR ALL USING (public.is_admin()) WITH CHECK (public.is_admin());

CREATE OR REPLACE FUNCTION public.ska_pages_for_role(p_role TEXT)
RETURNS TEXT[]
LANGUAGE sql
IMMUTABLE
AS $$
  SELECT CASE lower(coalesce(p_role, 'manager'))
    WHEN 'super_admin' THEN ARRAY['dashboard','rooms','promotions','packages','bookings','inquiries','users']
    WHEN 'manager' THEN ARRAY['dashboard','rooms','promotions','packages','bookings','inquiries']
    WHEN 'reservations' THEN ARRAY['dashboard','bookings','inquiries']
    WHEN 'marketing' THEN ARRAY['dashboard','promotions','packages']
    ELSE ARRAY['dashboard','bookings']
  END;
$$;

CREATE OR REPLACE FUNCTION public.ska_admin_profile()
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  rec RECORD;
  inv RECORD;
  out_pages TEXT[];
BEGIN
  IF auth.uid() IS NULL THEN
    RETURN jsonb_build_object('ok', false, 'reason', 'not_signed_in');
  END IF;

  SELECT * INTO rec FROM admin_users WHERE uid = auth.uid();
  IF rec.uid IS NULL THEN
    SELECT * INTO inv FROM admin_invites
      WHERE lower(email) = lower(coalesce(auth.jwt()->>'email', ''));
    IF inv.email IS NOT NULL THEN
      INSERT INTO admin_users (uid, email, role, pages)
      VALUES (auth.uid(), inv.email, inv.role, inv.pages)
      ON CONFLICT (uid) DO UPDATE SET role = excluded.role, pages = excluded.pages, email = excluded.email;
      DELETE FROM admin_invites WHERE id = inv.id;
      SELECT * INTO rec FROM admin_users WHERE uid = auth.uid();
    END IF;
  END IF;

  IF rec.uid IS NULL THEN
    RETURN jsonb_build_object('ok', false, 'reason', 'not_admin');
  END IF;

  out_pages := rec.pages;
  IF out_pages IS NULL OR array_length(out_pages, 1) IS NULL THEN
    out_pages := public.ska_pages_for_role(rec.role);
  END IF;

  RETURN jsonb_build_object(
    'ok', true,
    'email', rec.email,
    'role', rec.role,
    'pages', to_jsonb(out_pages)
  );
END;
$$;

CREATE OR REPLACE FUNCTION public.ska_list_staff()
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  me RECORD;
  result JSONB;
BEGIN
  SELECT * INTO me FROM admin_users WHERE uid = auth.uid();
  IF me.uid IS NULL THEN
    RAISE EXCEPTION 'not_admin';
  END IF;

  SELECT coalesce(jsonb_agg(x ORDER BY x->>'email'), '[]'::jsonb) INTO result
  FROM (
    SELECT jsonb_build_object(
      'id', u.uid::text,
      'email', u.email,
      'role', u.role,
      'pages', to_jsonb(coalesce(u.pages, public.ska_pages_for_role(u.role))),
      'status', 'active'
    ) AS x
    FROM admin_users u
    UNION ALL
    SELECT jsonb_build_object(
      'id', i.id::text,
      'email', i.email,
      'role', i.role,
      'pages', to_jsonb(coalesce(i.pages, public.ska_pages_for_role(i.role))),
      'status', 'invited'
    )
    FROM admin_invites i
  ) s;

  RETURN result;
END;
$$;

CREATE OR REPLACE FUNCTION public.ska_add_staff(p_email TEXT, p_role TEXT)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  me RECORD;
  existing_uid UUID;
  role_norm TEXT;
BEGIN
  SELECT * INTO me FROM admin_users WHERE uid = auth.uid();
  IF me.uid IS NULL OR me.role <> 'super_admin' THEN
    RAISE EXCEPTION 'only_super_admin';
  END IF;

  role_norm := lower(coalesce(nullif(trim(p_role), ''), 'manager'));
  IF role_norm NOT IN ('super_admin', 'manager', 'reservations', 'marketing') THEN
    role_norm := 'manager';
  END IF;

  SELECT id INTO existing_uid FROM auth.users WHERE lower(email) = lower(trim(p_email));

  IF existing_uid IS NOT NULL THEN
    INSERT INTO admin_users (uid, email, role, pages)
    VALUES (existing_uid, lower(trim(p_email)), role_norm, public.ska_pages_for_role(role_norm))
    ON CONFLICT (uid) DO UPDATE
      SET role = excluded.role,
          pages = excluded.pages,
          email = excluded.email;
    RETURN jsonb_build_object('ok', true, 'status', 'active');
  END IF;

  INSERT INTO admin_invites (email, role, pages)
  VALUES (lower(trim(p_email)), role_norm, public.ska_pages_for_role(role_norm))
  ON CONFLICT (email) DO UPDATE
    SET role = excluded.role, pages = excluded.pages;

  RETURN jsonb_build_object('ok', true, 'status', 'invited');
END;
$$;

CREATE OR REPLACE FUNCTION public.ska_update_staff(p_email TEXT, p_role TEXT)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  me RECORD;
  role_norm TEXT;
BEGIN
  SELECT * INTO me FROM admin_users WHERE uid = auth.uid();
  IF me.uid IS NULL OR me.role <> 'super_admin' THEN
    RAISE EXCEPTION 'only_super_admin';
  END IF;

  role_norm := lower(coalesce(nullif(trim(p_role), ''), 'manager'));
  IF role_norm NOT IN ('super_admin', 'manager', 'reservations', 'marketing') THEN
    role_norm := 'manager';
  END IF;

  UPDATE admin_users
     SET role = role_norm, pages = public.ska_pages_for_role(role_norm)
   WHERE lower(email) = lower(trim(p_email));

  UPDATE admin_invites
     SET role = role_norm, pages = public.ska_pages_for_role(role_norm)
   WHERE lower(email) = lower(trim(p_email));

  RETURN jsonb_build_object('ok', true);
END;
$$;

CREATE OR REPLACE FUNCTION public.ska_remove_staff(p_email TEXT)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  me RECORD;
BEGIN
  SELECT * INTO me FROM admin_users WHERE uid = auth.uid();
  IF me.uid IS NULL OR me.role <> 'super_admin' THEN
    RAISE EXCEPTION 'only_super_admin';
  END IF;

  IF lower(me.email) = lower(trim(p_email)) THEN
    RAISE EXCEPTION 'cannot_remove_self';
  END IF;

  DELETE FROM admin_users WHERE lower(email) = lower(trim(p_email));
  DELETE FROM admin_invites WHERE lower(email) = lower(trim(p_email));
  RETURN jsonb_build_object('ok', true);
END;
$$;

REVOKE ALL ON FUNCTION public.ska_pages_for_role(TEXT) FROM PUBLIC;
REVOKE ALL ON FUNCTION public.ska_admin_profile() FROM PUBLIC;
REVOKE ALL ON FUNCTION public.ska_list_staff() FROM PUBLIC;
REVOKE ALL ON FUNCTION public.ska_add_staff(TEXT, TEXT) FROM PUBLIC;
REVOKE ALL ON FUNCTION public.ska_update_staff(TEXT, TEXT) FROM PUBLIC;
REVOKE ALL ON FUNCTION public.ska_remove_staff(TEXT) FROM PUBLIC;

GRANT EXECUTE ON FUNCTION public.ska_pages_for_role(TEXT) TO authenticated;
GRANT EXECUTE ON FUNCTION public.ska_admin_profile() TO authenticated;
GRANT EXECUTE ON FUNCTION public.ska_list_staff() TO authenticated;
GRANT EXECUTE ON FUNCTION public.ska_add_staff(TEXT, TEXT) TO authenticated;
GRANT EXECUTE ON FUNCTION public.ska_update_staff(TEXT, TEXT) TO authenticated;
GRANT EXECUTE ON FUNCTION public.ska_remove_staff(TEXT) TO authenticated;

-- SKA Hotels — Row Level Security policies
-- Run AFTER 001_schema.sql and AFTER 002_seed.sql

ALTER TABLE rooms             ENABLE ROW LEVEL SECURITY;
ALTER TABLE room_images         ENABLE ROW LEVEL SECURITY;
ALTER TABLE room_amenities      ENABLE ROW LEVEL SECURITY;
ALTER TABLE bookings            ENABLE ROW LEVEL SECURITY;
ALTER TABLE promotions          ENABLE ROW LEVEL SECURITY;
ALTER TABLE inquiries           ENABLE ROW LEVEL SECURITY;
ALTER TABLE site_settings       ENABLE ROW LEVEL SECURITY;
ALTER TABLE cms_pages           ENABLE ROW LEVEL SECURITY;
ALTER TABLE cms_blocks          ENABLE ROW LEVEL SECURITY;
ALTER TABLE property_gallery    ENABLE ROW LEVEL SECURITY;
ALTER TABLE admins              ENABLE ROW LEVEL SECURITY;

-- Drop existing policies (idempotent re-run)
DO $$ DECLARE r RECORD;
BEGIN
  FOR r IN (
    SELECT policyname, tablename FROM pg_policies WHERE schemaname = 'public'
  ) LOOP
    EXECUTE format('DROP POLICY IF EXISTS %I ON %I', r.policyname, r.tablename);
  END LOOP;
END $$;

-- ── Public read: content tables ──────────────────────────────────────────
CREATE POLICY "public_read_rooms" ON rooms
  FOR SELECT USING (true);

CREATE POLICY "public_read_room_images" ON room_images
  FOR SELECT USING (true);

CREATE POLICY "public_read_room_amenities" ON room_amenities
  FOR SELECT USING (true);

CREATE POLICY "public_read_active_promotions" ON promotions
  FOR SELECT USING (
    active = true
    AND (valid_from IS NULL OR valid_from <= CURRENT_DATE)
    AND (valid_to IS NULL OR valid_to >= CURRENT_DATE)
  );

CREATE POLICY "public_read_site_settings" ON site_settings
  FOR SELECT USING (true);

CREATE POLICY "public_read_active_cms_pages" ON cms_pages
  FOR SELECT USING (active = true);

CREATE POLICY "public_read_active_cms_blocks" ON cms_blocks
  FOR SELECT USING (active = true);

CREATE POLICY "public_read_active_gallery" ON property_gallery
  FOR SELECT USING (active = true);

-- ── Public insert: bookings & inquiries ──────────────────────────────────
CREATE POLICY "public_insert_bookings" ON bookings
  FOR INSERT WITH CHECK (true);

CREATE POLICY "public_insert_inquiries" ON inquiries
  FOR INSERT WITH CHECK (true);

-- ── Authenticated admin: full CRUD ───────────────────────────────────────
-- Create admin users in Supabase Auth dashboard, then sign in from admin portal.

CREATE POLICY "admin_all_rooms" ON rooms
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_room_images" ON room_images
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_room_amenities" ON room_amenities
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_bookings" ON bookings
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_promotions" ON promotions
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_inquiries" ON inquiries
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_site_settings" ON site_settings
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_cms_pages" ON cms_pages
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_cms_blocks" ON cms_blocks
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_all_gallery" ON property_gallery
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

CREATE POLICY "admin_read_admins" ON admins
  FOR SELECT USING (auth.role() = 'authenticated');

CREATE POLICY "admin_manage_admins" ON admins
  FOR ALL USING (auth.role() = 'authenticated') WITH CHECK (auth.role() = 'authenticated');

-- Allow service role to bypass (server-side PHP with service key)
-- Service role bypasses RLS automatically in Supabase.

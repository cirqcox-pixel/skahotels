-- ============================================================================
-- SKA Hotels — Supabase: run-everything bundle (schema + seed + HARDENED RLS)
-- ============================================================================
-- Paste this whole file into the Supabase SQL Editor on a fresh project to get
-- the complete, SECURE database in one run:
--   https://supabase.com/dashboard/project/nllqkepymtwwbvbjnbyz/sql
--
-- This is a generated concatenation of migrations/001..007 in order. The final
-- RLS state comes from 007 (admin allowlist + is_admin(), scoped settings read,
-- constrained public inserts, locked-down legacy admins table).
--
-- ⚠️  Also disable public email sign-ups in Authentication settings — see 007.
--
-- To regenerate this file: concatenate migrations/*.sql in numeric order.
-- ============================================================================


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/001_schema.sql
-- ═══════════════════════════════════════════════════════════════════════════
-- SKA Hotels — Supabase PostgreSQL schema
-- Run in Supabase SQL Editor: https://supabase.com/dashboard/project/nllqkepymtwwbvbjnbyz/sql

-- Extensions
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ── Rooms ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rooms (
  id              BIGSERIAL PRIMARY KEY,
  name            TEXT NOT NULL,
  price           NUMERIC(10,2) NOT NULL DEFAULT 0,
  price_low       NUMERIC(10,2),
  price_shoulder  NUMERIC(10,2),
  price_high      NUMERIC(10,2),
  description     TEXT,
  branch          TEXT NOT NULL,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS room_images (
  id          BIGSERIAL PRIMARY KEY,
  room_id     BIGINT NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
  image_path  TEXT NOT NULL,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS room_amenities (
  id          BIGSERIAL PRIMARY KEY,
  room_id     BIGINT NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
  icon_class  TEXT,
  name        TEXT NOT NULL
);

-- ── Bookings ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
  id          BIGSERIAL PRIMARY KEY,
  name        TEXT NOT NULL,
  email       TEXT NOT NULL,
  phone       TEXT,
  whatsapp    TEXT,
  room_type   TEXT NOT NULL,
  price       NUMERIC(10,2) DEFAULT 0,
  checkin     DATE NOT NULL,
  checkout    DATE NOT NULL,
  total       NUMERIC(10,2) DEFAULT 0,
  message     TEXT,
  season      TEXT DEFAULT 'low',
  branch      TEXT NOT NULL,
  status      TEXT NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(status);
CREATE INDEX IF NOT EXISTS idx_bookings_branch ON bookings(branch);

-- ── Promotions ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS promotions (
  id              BIGSERIAL PRIMARY KEY,
  title           TEXT NOT NULL,
  tag             TEXT,
  description     TEXT,
  discount_type   TEXT DEFAULT 'percent',
  discount_value  NUMERIC(10,2) DEFAULT 0,
  min_nights      INT DEFAULT 1,
  branch          TEXT DEFAULT 'Both',
  image           TEXT,
  booking_url     TEXT,
  active          BOOLEAN NOT NULL DEFAULT TRUE,
  valid_from      DATE,
  valid_to        DATE,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── Inquiries ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inquiries (
  id          BIGSERIAL PRIMARY KEY,
  name        TEXT NOT NULL,
  email       TEXT NOT NULL,
  phone       TEXT,
  subject     TEXT,
  message     TEXT NOT NULL,
  is_read     BOOLEAN NOT NULL DEFAULT FALSE,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── CMS ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS site_settings (
  setting_key     TEXT PRIMARY KEY,
  setting_value   TEXT,
  setting_group   TEXT DEFAULT 'general',
  updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS cms_pages (
  id                BIGSERIAL PRIMARY KEY,
  slug              TEXT NOT NULL UNIQUE,
  page_title        TEXT NOT NULL,
  meta_description  TEXT,
  hero_eyebrow      TEXT,
  hero_title        TEXT,
  hero_subtitle     TEXT,
  hero_image        TEXT,
  body_html         TEXT,
  active            BOOLEAN NOT NULL DEFAULT TRUE,
  updated_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS cms_blocks (
  id          BIGSERIAL PRIMARY KEY,
  page_slug   TEXT NOT NULL,
  block_key   TEXT NOT NULL,
  tag         TEXT,
  title       TEXT,
  subtitle    TEXT,
  body        TEXT,
  image       TEXT,
  link_url    TEXT,
  link_label  TEXT,
  sort_order  INT NOT NULL DEFAULT 0,
  active      BOOLEAN NOT NULL DEFAULT TRUE,
  updated_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  UNIQUE (page_slug, block_key)
);

CREATE TABLE IF NOT EXISTS property_gallery (
  id          BIGSERIAL PRIMARY KEY,
  branch      TEXT NOT NULL,
  image_path  TEXT NOT NULL,
  caption     TEXT,
  sort_order  INT NOT NULL DEFAULT 0,
  active      BOOLEAN NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── Legacy admin table (optional — prefer Supabase Auth) ─────────────────
CREATE TABLE IF NOT EXISTS admins (
  id          BIGSERIAL PRIMARY KEY,
  username    TEXT NOT NULL UNIQUE,
  password    TEXT NOT NULL,
  created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── Storage bucket for uploads (run separately if needed) ──────────────────
-- INSERT INTO storage.buckets (id, name, public) VALUES ('ska-uploads', 'ska-uploads', true)
-- ON CONFLICT DO NOTHING;

-- ── Updated_at trigger ───────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION ska_set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_promotions_updated ON promotions;
CREATE TRIGGER trg_promotions_updated
  BEFORE UPDATE ON promotions FOR EACH ROW EXECUTE FUNCTION ska_set_updated_at();

DROP TRIGGER IF EXISTS trg_cms_pages_updated ON cms_pages;
CREATE TRIGGER trg_cms_pages_updated
  BEFORE UPDATE ON cms_pages FOR EACH ROW EXECUTE FUNCTION ska_set_updated_at();

DROP TRIGGER IF EXISTS trg_cms_blocks_updated ON cms_blocks;
CREATE TRIGGER trg_cms_blocks_updated
  BEFORE UPDATE ON cms_blocks FOR EACH ROW EXECUTE FUNCTION ska_set_updated_at();

DROP TRIGGER IF EXISTS trg_site_settings_updated ON site_settings;
CREATE TRIGGER trg_site_settings_updated
  BEFORE UPDATE ON site_settings FOR EACH ROW EXECUTE FUNCTION ska_set_updated_at();


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/002_seed.sql
-- ═══════════════════════════════════════════════════════════════════════════
-- SKA Hotels — default CMS seed data
-- Run AFTER 001_schema.sql (safe to re-run — uses ON CONFLICT)

INSERT INTO site_settings (setting_key, setting_value, setting_group) VALUES
  ('site_email', 'info@skaboutiquebnb.com', 'contact'),
  ('site_phone_main', '+256 200 98777', 'contact'),
  ('site_phone_naguru', '+256 741 186 891', 'contact'),
  ('site_phone_munyonyo', '+256 200 904 877', 'contact'),
  ('facebook_url', 'https://www.facebook.com/skaboutiquebnb', 'social'),
  ('instagram_url', 'https://www.instagram.com/skanaguru/', 'social'),
  ('whatsapp_url', 'https://wa.me/256741186891', 'social'),
  ('hero_slide_1_image', 'assets/images/ska_naguru_home.jpeg', 'homepage'),
  ('hero_slide_1_alt', 'SKA Naguru boutique hotel in Kampala', 'homepage'),
  ('hero_slide_2_image', 'assets/images/ska_munyonyo_home2.jpg', 'homepage'),
  ('hero_slide_2_alt', 'SKA Munyonyo lakeside boutique retreat', 'homepage')
ON CONFLICT (setting_key) DO NOTHING;

INSERT INTO cms_pages (slug, page_title, meta_description, hero_eyebrow, hero_title, hero_subtitle, hero_image, body_html) VALUES
  ('offers', 'Special Offers & Packages', 'Exclusive direct-booking offers at SKA The Boutique.', 'Deals & Packages', 'Get Away, Get More', 'Book direct for our best rates — free breakfast, Wi-Fi, and flexible check-in included with every reservation.', 'assets/images/ska_naguru_home.jpeg', ''),
  ('about-us', 'About Us | SKA The Boutique', 'Discover SKA The Boutique — two distinctive properties in Kampala.', 'Our Story', 'Redefining Boutique Hospitality', 'A distinguished collection of elegant retreats in Naguru and Munyonyo.', 'assets/images/dube_munyonyo.jpg', ''),
  ('meetings-events', 'Meetings & Events', 'Intimate meetings, weddings and events at SKA The Boutique.', 'Events & Meetings', 'Memorable Gatherings, Intimate Scale', 'From boardroom briefings to sunset celebrations — SKA offers refined spaces with boutique warmth.', 'assets/images/ska_munyonyo_home2.jpg', ''),
  ('help', 'Help Centre', 'Answers to common questions about booking and stays at SKA.', 'Help Centre', 'How Can We Help?', 'Everything you need to know before, during, and after your stay.', NULL, ''),
  ('careers', 'Careers at SKA', 'Join SKA The Boutique — hospitality careers in Kampala.', 'Careers', 'Discover Career Opportunities at SKA', 'Hospitality · Front Office · Kitchen & Housekeeping', 'assets/images/ska_art_home.jpg', ''),
  ('loyalty', 'SKA Rewards', 'Join SKA Rewards for member rates and exclusive offers.', 'SKA Rewards', 'Your Boutique Loyalty Programme', 'Every direct stay brings you closer to exclusive perks.', 'assets/images/ska_art_home.jpg', ''),
  ('privacy-policy', 'Privacy Policy', 'Privacy Policy for SKA The Boutique.', NULL, 'Privacy Policy', 'How we collect, use and protect your personal data.', NULL, '<h2>1. Introduction</h2><p>SKA The Boutique operates skaboutiquebnb.com. This policy explains how we collect, use, and safeguard your personal information.</p><h3>2. Contact</h3><p>Questions: info@skaboutiquebnb.com</p>'),
  ('terms-of-use', 'Terms of Use', 'Terms governing use of skaboutiquebnb.com.', NULL, 'Terms of Use', 'Terms for reservations and website use.', NULL, '<h2>1. Acceptance</h2><p>By accessing skaboutiquebnb.com, you agree to these Terms of Use.</p>'),
  ('cookie-policy', 'Cookie Policy', 'Cookie Policy for skaboutiquebnb.com.', NULL, 'Cookie Policy', 'How we use cookies on our website.', NULL, '<h2>What Are Cookies?</h2><p>Cookies are small text files stored on your device when you visit a website.</p>'),
  ('naguru', 'SKA Naguru', 'Boutique hotel in Naguru, Kampala.', NULL, NULL, NULL, NULL, ''),
  ('munyonyo', 'SKA Munyonyo', 'Lakeside boutique hotel in Munyonyo.', NULL, NULL, NULL, NULL, '')
ON CONFLICT (slug) DO NOTHING;

INSERT INTO cms_blocks (page_slug, block_key, tag, title, subtitle, body, image, link_url, link_label, sort_order) VALUES
  ('help', 'booking', 'Booking', 'How do I make a reservation?', NULL, 'Select your property on our homepage, choose dates and room type, and submit a reservation request. Our team confirms within 24 hours.', NULL, NULL, NULL, 1),
  ('help', 'rates', 'Rates', 'Is booking on this website the best rate?', NULL, 'Yes — our Best Rate Guarantee ensures the lowest price when you book direct, plus complimentary breakfast and Wi-Fi.', NULL, NULL, NULL, 2),
  ('help', 'cancel', 'Cancellation', 'Can I modify or cancel my booking?', NULL, 'Contact us at least 48 hours before check-in. Flexible cancellation terms apply to direct bookings.', NULL, NULL, NULL, 3),
  ('meetings-events', 'business', 'Corporate', 'Business Meetings', NULL, 'Private meeting rooms with natural light, high-speed Wi-Fi, refreshments, and dedicated support.', 'assets/images/ska_naguru_home.jpeg', 'contact.html?subject=Business+Meeting', 'Plan a Meeting', 1),
  ('meetings-events', 'weddings', 'Celebrations', 'Weddings', NULL, 'Intimate wedding ceremonies and receptions surrounded by gardens and lake views.', 'assets/images/ska_munyonyo_home2.jpg', 'contact.html?subject=Wedding+Enquiry', 'Start Planning', 2),
  ('meetings-events', 'social', 'Social', 'Social Events', NULL, 'Birthdays, anniversaries, baby showers, and private dinners.', 'assets/images/ska_art_home.jpg', 'contact.html?subject=Social+Event', 'Enquire', 3),
  ('loyalty', 'member_rates', NULL, 'Member Rates', NULL, 'Access preferential pricing on select room categories when you book direct as a SKA Rewards member.', NULL, NULL, NULL, 1),
  ('loyalty', 'early_access', NULL, 'Early Access', NULL, 'Be first to know about seasonal offers, new packages, and limited availability dates.', NULL, NULL, NULL, 2),
  ('loyalty', 'stay_perks', NULL, 'Stay Perks', NULL, 'Complimentary room upgrades, welcome amenities, and late checkout — subject to availability.', NULL, NULL, NULL, 3),
  ('careers', 'front_office', NULL, 'Front Office', NULL, 'Guest relations, reservations, and concierge — the face of SKA hospitality.', NULL, NULL, NULL, 1),
  ('careers', 'kitchen', NULL, 'Kitchen & Dining', NULL, 'From breakfast service to event catering — culinary excellence in a boutique setting.', NULL, NULL, NULL, 2),
  ('careers', 'housekeeping', NULL, 'Housekeeping', NULL, 'Impeccable standards that make every room feel like a private retreat.', NULL, NULL, NULL, 3),
  ('naguru', 'dining', 'RESTAURANT', 'Fine Dining', NULL, 'Savor refined cuisine crafted with precision and artistry throughout your stay.', 'assets/images/naguru/restaurant.jpg', '#contact', 'Learn More', 1),
  ('naguru', 'garden', 'GARDENS', 'Serene Settings', NULL, 'Wander through lush gardens and unwind in tranquil greenery.', 'assets/images/naguru/garden.jpg', '#contact', 'Learn More', 2),
  ('naguru', 'hero_video', NULL, NULL, NULL, NULL, 'assets/video/ska_naguru.mp4', NULL, NULL, 0),
  ('munyonyo', 'dining', 'RESTAURANT', 'Fine Dining', NULL, 'Exceptional dining experiences with lake-view ambiance.', 'assets/images/naguru/restaurant.jpg', '#contact', 'Learn More', 1),
  ('munyonyo', 'garden', 'GARDENS', 'Serene Settings', NULL, 'Lakeside gardens perfect for relaxation and events.', 'assets/images/naguru/garden.jpg', '#contact', 'Learn More', 2),
  ('munyonyo', 'hero_video', NULL, NULL, NULL, NULL, 'assets/video/ska_munyonyo.mp4', NULL, NULL, 0),
  ('offers', 'corporate', NULL, 'Corporate & Group Rates', NULL, 'Hosting a delegation or team retreat? We craft tailored packages for corporate travellers and group bookings.', NULL, 'contact.html?subject=Corporate+Rates', 'Request a Quote', 10),
  ('offers', 'early_bird', NULL, 'Early Bird Packages', NULL, 'Book 21 days or more in advance and receive preferential rates on select room categories.', NULL, 'naguru.html#book', 'Check Availability', 11),
  ('offers', 'gift_voucher', NULL, 'Gift Vouchers', NULL, 'Give the gift of a boutique escape — redeemable at either property.', NULL, 'contact.html?subject=Gift+Voucher', 'Purchase a Voucher', 12)
ON CONFLICT (page_slug, block_key) DO NOTHING;

-- Sample promotions (optional — skip if already present)
INSERT INTO promotions (title, tag, description, branch, image, booking_url, active, sort_order)
SELECT 'Book Direct & Save', 'Best Rate Guarantee', 'Our lowest prices are always here. Free Wi-Fi, breakfast, and flexible cancellation when you book on our website.', 'Both', 'assets/images/ska_naguru_home.jpeg', 'index.html#book-search', true, 1
WHERE NOT EXISTS (SELECT 1 FROM promotions LIMIT 1);


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/003_rls.sql
-- ═══════════════════════════════════════════════════════════════════════════
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


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/004_rooms_seed.sql
-- ═══════════════════════════════════════════════════════════════════════════
-- SKA Hotels — sample room inventory (run after 001_schema.sql)
-- Safe to re-run: skips if rooms already exist

INSERT INTO rooms (name, price, price_low, price_shoulder, price_high, description, branch)
SELECT * FROM (VALUES
  ('Standard Room', 150, 130, 150, 170, 'Cosy ensuite room with garden views — ideal for solo travellers and short stays.', 'Naguru'),
  ('Deluxe Room', 180, 160, 180, 200, 'Spacious deluxe room with premium linens, smart TV and boutique ensuite.', 'Naguru'),
  ('Deluxe Twin', 190, 170, 190, 210, 'Twin deluxe configuration — perfect for friends or colleagues travelling together.', 'Naguru'),
  ('Superior Room', 220, 200, 220, 250, 'Our finest Naguru category with elevated views, extra space and curated amenities.', 'Naguru'),
  ('Standard Double', 180, 160, 180, 200, 'Comfortable lakeside double room with ensuite and garden access.', 'Munyonyo'),
  ('Deluxe Room', 210, 190, 210, 230, 'Deluxe lakeside room with refined finishes and tranquil views.', 'Munyonyo'),
  ('Superior Room', 240, 220, 240, 270, 'Superior category with generous space and premium Munyonyo outlook.', 'Munyonyo'),
  ('Dube Suite', 280, 260, 280, 320, 'Signature suite — the ultimate lakeside boutique escape at SKA Munyonyo.', 'Munyonyo')
) AS v(name, price, price_low, price_shoulder, price_high, description, branch)
WHERE NOT EXISTS (SELECT 1 FROM rooms LIMIT 1);

INSERT INTO room_images (room_id, image_path)
SELECT r.id, m.path FROM rooms r
JOIN (VALUES
  ('Standard Room', 'Naguru', 'assets/images/standard_naguru.jpeg'),
  ('Deluxe Room', 'Naguru', 'assets/images/deluxe_naguru.jpeg'),
  ('Deluxe Twin', 'Naguru', 'assets/images/deluxe_twin_naguru.jpeg'),
  ('Superior Room', 'Naguru', 'assets/images/superior_naguru.jpeg'),
  ('Standard Double', 'Munyonyo', 'assets/images/munyonyo/standard_double_munyonyo.jpg'),
  ('Deluxe Room', 'Munyonyo', 'assets/images/deluxe_munyonyo.jpg'),
  ('Superior Room', 'Munyonyo', 'assets/images/superior_munyonyo.jpg'),
  ('Dube Suite', 'Munyonyo', 'assets/images/dube_munyonyo.jpg')
) AS m(name, branch, path) ON r.name = m.name AND r.branch = m.branch
WHERE NOT EXISTS (SELECT 1 FROM room_images LIMIT 1);

INSERT INTO property_gallery (branch, image_path, caption, sort_order, active)
SELECT * FROM (VALUES
  ('Naguru', 'assets/images/naguru/IMG_1044.jpg', 'SKA Naguru', 1, true),
  ('Naguru', 'assets/images/naguru/IMG_1066.jpg', 'Garden views', 2, true),
  ('Naguru', 'assets/images/naguru/IMG_1069.jpg', 'Boutique interiors', 3, true),
  ('Naguru', 'assets/images/naguru/IMG_1093.jpg', 'Relaxation spaces', 4, true),
  ('Naguru', 'assets/images/naguru/IMG_1120.jpg', 'SKA Naguru retreat', 5, true),
  ('Naguru', 'assets/images/naguru/IMG_1157.jpg', 'Hillside setting', 6, true),
  ('Munyonyo', 'assets/images/munyonyo/IMG_0879.jpg', 'SKA Munyonyo', 1, true),
  ('Munyonyo', 'assets/images/munyonyo/IMG_0883.jpg', 'Lakeside views', 2, true),
  ('Munyonyo', 'assets/images/munyonyo/IMG_0912.jpg', 'Boutique comfort', 3, true),
  ('Munyonyo', 'assets/images/munyonyo/IMG_0973.jpg', 'Serene gardens', 4, true)
) AS g(branch, image_path, caption, sort_order, active)
WHERE NOT EXISTS (SELECT 1 FROM property_gallery LIMIT 1);


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/005_fix_admin_rls.sql
-- ═══════════════════════════════════════════════════════════════════════════
-- Fix admin RLS: auth.role() can fail; use auth.uid() for authenticated access
-- Run in Supabase SQL Editor after 003_rls.sql

DROP POLICY IF EXISTS "admin_all_rooms" ON rooms;
DROP POLICY IF EXISTS "admin_all_bookings" ON bookings;
DROP POLICY IF EXISTS "admin_all_inquiries" ON inquiries;
DROP POLICY IF EXISTS "admin_all_promotions" ON promotions;

CREATE POLICY "admin_all_rooms" ON rooms
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);

CREATE POLICY "admin_all_bookings" ON bookings
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);

CREATE POLICY "admin_all_inquiries" ON inquiries
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);

CREATE POLICY "admin_all_promotions" ON promotions
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/006_seed_offers.sql
-- ═══════════════════════════════════════════════════════════════════════════
-- Seed the classic homepage offers (run in Supabase SQL Editor)
-- Safe to re-run: only inserts titles that are missing

INSERT INTO promotions (title, tag, description, discount_type, discount_value, min_nights, branch, image, booking_url, active, sort_order)
SELECT * FROM (VALUES
  ('Book Direct & Save', 'Best Rate Guarantee', 'Our lowest prices are always here. Free Wi-Fi, breakfast, and flexible cancellation when you book on our website.', 'percent', 0::numeric, 1, 'Both', 'assets/images/ska_naguru_home.jpeg', 'index.html#book-search', true, 1),
  ('Book 7 Days Early', 'Early Bird', 'Plan ahead and unlock exclusive savings when you reserve at least seven days before arrival.', 'percent', 10::numeric, 1, 'Both', 'assets/images/ska_art_home.jpg', 'naguru.html#book', true, 2),
  ('Stay 3 Nights, Pay for 2', 'Extended Stay', 'Celebrate longer stays — enjoy three nights and only pay for two at either property.', 'free_night', 1::numeric, 3, 'Both', 'assets/images/ska_furniture_home.jpg', 'index.html#book-search', true, 3),
  ('Direct Booking Bonus', 'Member Perk', 'Extra value when you book with us — complimentary upgrades subject to availability and welcome treats.', 'percent', 5::numeric, 1, 'Both', 'assets/images/ska_munyonyo_home2.jpg', 'loyalty.html', true, 4),
  ('Munyonyo Lakeside Weekend', 'Weekend Escape', 'Unwind by the lake with a weekend package at SKA Munyonyo — serene gardens and boutique comfort.', 'percent', 15::numeric, 2, 'Munyonyo', 'assets/images/ska_munyonyo_home2.jpg', 'munyonyo.html#book', true, 5)
) AS v(title, tag, description, discount_type, discount_value, min_nights, branch, image, booking_url, active, sort_order)
WHERE NOT EXISTS (
  SELECT 1 FROM promotions p WHERE p.title = v.title
);


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/007_harden_rls.sql
-- ═══════════════════════════════════════════════════════════════════════════
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


-- ═══════════════════════════════════════════════════════════════════════════
-- ▶ migrations/008_bootstrap_admin.sql
-- ═══════════════════════════════════════════════════════════════════════════
-- Bootstrap the first GitHub Pages admin.
-- Run in SQL Editor: https://supabase.com/dashboard/project/nllqkepymtwwbvbjnbyz/sql
-- Safe to re-run.

-- Allow the first signed-in Auth user to claim admin if the allowlist is empty.
CREATE OR REPLACE FUNCTION public.claim_first_admin()
RETURNS BOOLEAN
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF auth.uid() IS NULL THEN
    RETURN FALSE;
  END IF;

  IF EXISTS (SELECT 1 FROM public.admin_users) THEN
    RETURN EXISTS (SELECT 1 FROM public.admin_users WHERE uid = auth.uid());
  END IF;

  INSERT INTO public.admin_users (uid, email)
  SELECT id, email FROM auth.users WHERE id = auth.uid()
  ON CONFLICT (uid) DO NOTHING;

  RETURN TRUE;
END;
$$;

REVOKE ALL ON FUNCTION public.claim_first_admin() FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.claim_first_admin() TO authenticated;

-- Known staff user (Authentication → Users)
INSERT INTO public.admin_users (uid, email)
VALUES (
  'ed0e920a-07ef-4edc-9b00-abd90bf3e39c',
  'admin@skahotels.com'
)
ON CONFLICT (uid) DO NOTHING;


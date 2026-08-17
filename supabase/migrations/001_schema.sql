-- SKA Hotels — Supabase PostgreSQL schema
-- Run in Supabase SQL Editor: https://supabase.com/dashboard/project/aoofgjyhwbxasdvhdwoe/sql

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

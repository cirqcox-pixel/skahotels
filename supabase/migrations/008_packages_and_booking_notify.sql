-- Packages (bookable event/stay products) + booking fields
-- Run after 001–007.

BEGIN;

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
CREATE INDEX IF NOT EXISTS idx_packages_active ON packages(active);

ALTER TABLE bookings ADD COLUMN IF NOT EXISTS package_id BIGINT REFERENCES packages(id) ON DELETE SET NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS package_option TEXT;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS currency TEXT DEFAULT 'USD';
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guests INT;

DROP TRIGGER IF EXISTS trg_packages_updated ON packages;
CREATE TRIGGER trg_packages_updated
  BEFORE UPDATE ON packages FOR EACH ROW EXECUTE FUNCTION ska_set_updated_at();

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

-- Seed Naguru conference + wedding packages (idempotent by title+branch)
INSERT INTO packages (title, tag, description, inclusions, options, currency, price, pricing_mode, branch, image, booking_url, active, sort_order)
SELECT
  'Conference Package Menu',
  'Corporate',
  'Delegate and boardroom packages for agendas that run from the morning briefing through to closing remarks — or a focused half-day session.',
  E'Break teas (tea, coffee and assorted bites)\nHydration — mineral water per delegate\nStationery — notebook and executive pen\nLunch — main buffet or plated meal with a soda or mineral water\nEquipment — PA system set up in your meeting room',
  '[{"label":"Full Day Conference Package","price":120000,"detail":"Per delegate, per day","pricing":"per_person"},{"label":"Half Day Conference Package","price":100000,"detail":"Per delegate, per session","pricing":"per_person"},{"label":"Video Conferencing","price":150000,"detail":"Connect remote and hybrid attendees","pricing":"fixed"},{"label":"Boardroom Hire — Half Day","price":300000,"detail":"Exclusive morning or afternoon session","pricing":"fixed"},{"label":"Boardroom Hire — Full Day","price":450000,"detail":"Exclusive use for private meetings and interviews","pricing":"fixed"}]',
  'UGX',
  120000,
  'per_person',
  'Naguru',
  'assets/images/packages/conference-package.jpg',
  'naguru.html?package=conference#book',
  true,
  1
WHERE NOT EXISTS (
  SELECT 1 FROM packages WHERE title = 'Conference Package Menu' AND branch = 'Naguru'
);

INSERT INTO packages (title, tag, description, inclusions, options, currency, price, pricing_mode, branch, image, booking_url, active, sort_order)
SELECT
  'Get Wedding Ready With Your Tribe',
  'Wedding',
  'Sleep here, stress less, slay the wedding. Exclusive-use and luxury group stays with dinner and breakfast included.',
  E'Exclusive use of all 6 beautifully appointed rooms (full buyout)\nAccommodation for up to 12 guests\nDinner for all guests\nBreakfast the following morning\nComplete privacy throughout your stay\nA peaceful and intimate atmosphere exclusively for your celebration',
  '[{"label":"Exclusive stay — 12 guests (all 6 rooms)","price":1680000,"detail":"Dinner and breakfast included","pricing":"fixed"},{"label":"Luxury 2 Pax","price":300000,"detail":"1 room, dinner and breakfast included","pricing":"fixed"},{"label":"Luxury 4 Pax","price":560000,"detail":"2 rooms, dinner and breakfast included","pricing":"fixed"},{"label":"Luxury 6 Pax","price":840000,"detail":"3 rooms, dinner and breakfast included","pricing":"fixed"},{"label":"Additional room (2 guests sharing)","price":280000,"detail":"Dinner and breakfast included","pricing":"fixed"}]',
  'UGX',
  1680000,
  'fixed',
  'Naguru',
  'assets/images/packages/wedding-package.jpg',
  'naguru.html?package=wedding#book',
  true,
  2
WHERE NOT EXISTS (
  SELECT 1 FROM packages WHERE title = 'Get Wedding Ready With Your Tribe' AND branch = 'Naguru'
);

COMMIT;

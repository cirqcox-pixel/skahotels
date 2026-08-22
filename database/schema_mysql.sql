-- ============================================================================
-- SKA Hotels — canonical MySQL / MariaDB schema (cPanel / Namecheap)
-- ============================================================================
-- Import this once via cPanel → phpMyAdmin → your DB → Import.
--
-- You do NOT strictly need to run this: config/cms.php auto-creates every table
-- on first page load (self-healing). This file is the explicit, reviewable
-- version with the SAME columns, plus a little sample content so the property
-- pages aren't empty on day one.
--
-- Charset utf8mb4, engine InnoDB (required for the foreign keys below).
-- Safe to re-run: every statement is CREATE TABLE IF NOT EXISTS / INSERT ...
-- guarded so it will not clobber existing data.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Rooms ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rooms (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(255) NOT NULL,
  price          DECIMAL(10,2) NOT NULL DEFAULT 0,
  price_low      DECIMAL(10,2) DEFAULT NULL,
  price_shoulder DECIMAL(10,2) DEFAULT NULL,
  price_high     DECIMAL(10,2) DEFAULT NULL,
  description    TEXT,
  branch         VARCHAR(50) NOT NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rooms_branch (branch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_images (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  room_id    INT NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_room_images_room (room_id),
  CONSTRAINT fk_room_images_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_amenities (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  room_id    INT NOT NULL,
  icon_class VARCHAR(120) DEFAULT NULL,
  name       VARCHAR(120) NOT NULL,
  INDEX idx_room_amenities_room (room_id),
  CONSTRAINT fk_room_amenities_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Bookings ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL,
  phone      VARCHAR(50) DEFAULT NULL,
  whatsapp   VARCHAR(50) DEFAULT NULL,
  room_type  VARCHAR(255) NOT NULL,
  price      DECIMAL(10,2) DEFAULT 0,
  checkin    DATE NOT NULL,
  checkout   DATE NOT NULL,
  total      DECIMAL(10,2) DEFAULT 0,
  message    TEXT,
  season     VARCHAR(20) DEFAULT 'low',
  branch     VARCHAR(50) NOT NULL,
  status     VARCHAR(20) NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bookings_status (status),
  INDEX idx_bookings_branch (branch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Promotions ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS promotions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  title          VARCHAR(255) NOT NULL,
  tag            VARCHAR(120) DEFAULT NULL,
  description    TEXT,
  discount_type  VARCHAR(20) DEFAULT 'percent',
  discount_value DECIMAL(10,2) DEFAULT 0,
  min_nights     INT DEFAULT 1,
  branch         VARCHAR(50) DEFAULT 'Both',
  image          VARCHAR(500) DEFAULT NULL,
  booking_url    VARCHAR(500) DEFAULT NULL,
  active         TINYINT(1) NOT NULL DEFAULT 1,
  valid_from     DATE DEFAULT NULL,
  valid_to       DATE DEFAULT NULL,
  sort_order     INT NOT NULL DEFAULT 0,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Inquiries (general contact form) ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inquiries (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(255) NOT NULL,
  email      VARCHAR(255) NOT NULL,
  phone      VARCHAR(50) DEFAULT NULL,
  subject    VARCHAR(255) DEFAULT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CMS: settings, pages, blocks, gallery ───────────────────────────────────
CREATE TABLE IF NOT EXISTS site_settings (
  setting_key   VARCHAR(100) PRIMARY KEY,
  setting_value TEXT,
  setting_group VARCHAR(50) DEFAULT 'general',
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_pages (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  slug             VARCHAR(80) NOT NULL UNIQUE,
  page_title       VARCHAR(255) NOT NULL,
  meta_description TEXT,
  hero_eyebrow     VARCHAR(255) DEFAULT NULL,
  hero_title       VARCHAR(255) DEFAULT NULL,
  hero_subtitle    TEXT,
  hero_image       VARCHAR(500) DEFAULT NULL,
  body_html        MEDIUMTEXT,
  active           TINYINT(1) NOT NULL DEFAULT 1,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_blocks (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  page_slug  VARCHAR(80) NOT NULL,
  block_key  VARCHAR(80) NOT NULL,
  tag        VARCHAR(120) DEFAULT NULL,
  title      VARCHAR(255) DEFAULT NULL,
  subtitle   VARCHAR(255) DEFAULT NULL,
  body       TEXT,
  image      VARCHAR(500) DEFAULT NULL,
  link_url   VARCHAR(500) DEFAULT NULL,
  link_label VARCHAR(120) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active     TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_page_block (page_slug, block_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS property_gallery (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  branch     VARCHAR(50) NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  caption    VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Admins (server-side login; bcrypt via admin/setup.php) ──────────────────
CREATE TABLE IF NOT EXISTS admins (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- NOTE: do NOT hard-code an admin password here. After importing, visit
--       /admin/setup.php once to create the first admin (bcrypt-hashed).

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- OPTIONAL sample content — remove this block if you prefer to add rooms
-- yourself in the admin. Runs only when the rooms table is still empty.
-- ============================================================================
INSERT INTO rooms (name, price, price_low, price_shoulder, price_high, description, branch)
SELECT * FROM (SELECT
  'Deluxe King Room' AS name, 120.00 AS price, 110.00 AS price_low,
  130.00 AS price_shoulder, 160.00 AS price_high,
  'Elegant king room with city views, workspace and en-suite bathroom.' AS description,
  'Naguru' AS branch) AS s
WHERE NOT EXISTS (SELECT 1 FROM rooms LIMIT 1);

INSERT INTO rooms (name, price, price_low, price_shoulder, price_high, description, branch)
SELECT * FROM (SELECT
  'Lakeview Suite', 180.00, 165.00, 195.00, 240.00,
  'Spacious suite overlooking Lake Victoria with private balcony.',
  'Munyonyo') AS s
WHERE (SELECT COUNT(*) FROM rooms) = 1;

INSERT INTO promotions (title, tag, description, discount_type, discount_value, min_nights, branch, active, sort_order)
SELECT * FROM (SELECT
  'Book Direct & Save' AS title, 'Best Rate' AS tag,
  'Book direct for our lowest rates plus complimentary breakfast and Wi-Fi.' AS description,
  'percent' AS discount_type, 10.00 AS discount_value, 1 AS min_nights,
  'Both' AS branch, 1 AS active, 1 AS sort_order) AS s
WHERE NOT EXISTS (SELECT 1 FROM promotions LIMIT 1);

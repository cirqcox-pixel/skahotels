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
-- OPTIONAL sample content — same inventory as supabase/migrations/004 + 006.
-- Runs only when the target table is still empty (will not clobber live data).
-- ============================================================================
INSERT INTO rooms (name, price, price_low, price_shoulder, price_high, description, branch)
SELECT v.name, v.price, v.price_low, v.price_shoulder, v.price_high, v.description, v.branch
FROM (
  SELECT 'Standard Room' AS name, 150.00 AS price, 130.00 AS price_low, 150.00 AS price_shoulder, 170.00 AS price_high, 'Cosy ensuite room with garden views — ideal for solo travellers and short stays.' AS description, 'Naguru' AS branch
  UNION ALL SELECT 'Deluxe Room', 180.00, 160.00, 180.00, 200.00, 'Spacious deluxe room with premium linens, smart TV and boutique ensuite.', 'Naguru'
  UNION ALL SELECT 'Deluxe Twin', 190.00, 170.00, 190.00, 210.00, 'Twin deluxe configuration — perfect for friends or colleagues travelling together.', 'Naguru'
  UNION ALL SELECT 'Superior Room', 220.00, 200.00, 220.00, 250.00, 'Our finest Naguru category with elevated views, extra space and curated amenities.', 'Naguru'
  UNION ALL SELECT 'Standard Double', 180.00, 160.00, 180.00, 200.00, 'Comfortable lakeside double room with ensuite and garden access.', 'Munyonyo'
  UNION ALL SELECT 'Deluxe Room', 210.00, 190.00, 210.00, 230.00, 'Deluxe lakeside room with refined finishes and tranquil views.', 'Munyonyo'
  UNION ALL SELECT 'Superior Room', 240.00, 220.00, 240.00, 270.00, 'Superior category with generous space and premium Munyonyo outlook.', 'Munyonyo'
  UNION ALL SELECT 'Dube Suite', 280.00, 260.00, 280.00, 320.00, 'Signature suite — the ultimate lakeside boutique escape at SKA Munyonyo.', 'Munyonyo'
) AS v
WHERE NOT EXISTS (SELECT 1 FROM rooms LIMIT 1);

INSERT INTO room_images (room_id, image_path)
SELECT r.id, m.path FROM rooms r
JOIN (
  SELECT 'Standard Room' AS name, 'Naguru' AS branch, 'assets/images/standard_naguru.jpeg' AS path
  UNION ALL SELECT 'Deluxe Room', 'Naguru', 'assets/images/deluxe_naguru.jpeg'
  UNION ALL SELECT 'Deluxe Twin', 'Naguru', 'assets/images/deluxe_twin_naguru.jpeg'
  UNION ALL SELECT 'Superior Room', 'Naguru', 'assets/images/superior_naguru.jpeg'
  UNION ALL SELECT 'Standard Double', 'Munyonyo', 'assets/images/munyonyo/standard_double_munyonyo.jpg'
  UNION ALL SELECT 'Deluxe Room', 'Munyonyo', 'assets/images/deluxe_munyonyo.jpg'
  UNION ALL SELECT 'Superior Room', 'Munyonyo', 'assets/images/superior_munyonyo.jpg'
  UNION ALL SELECT 'Dube Suite', 'Munyonyo', 'assets/images/dube_munyonyo.jpg'
) AS m ON r.name = m.name AND r.branch = m.branch
WHERE NOT EXISTS (SELECT 1 FROM room_images LIMIT 1);

INSERT INTO property_gallery (branch, image_path, caption, sort_order, active)
SELECT g.branch, g.image_path, g.caption, g.sort_order, g.active
FROM (
  SELECT 'Naguru' AS branch, 'assets/images/naguru/IMG_1044.jpg' AS image_path, 'SKA Naguru' AS caption, 1 AS sort_order, 1 AS active
  UNION ALL SELECT 'Naguru', 'assets/images/naguru/IMG_1066.jpg', 'Garden views', 2, 1
  UNION ALL SELECT 'Naguru', 'assets/images/naguru/IMG_1069.jpg', 'Boutique interiors', 3, 1
  UNION ALL SELECT 'Naguru', 'assets/images/naguru/IMG_1093.jpg', 'Relaxation spaces', 4, 1
  UNION ALL SELECT 'Naguru', 'assets/images/naguru/IMG_1120.jpg', 'SKA Naguru retreat', 5, 1
  UNION ALL SELECT 'Naguru', 'assets/images/naguru/IMG_1157.jpg', 'Hillside setting', 6, 1
  UNION ALL SELECT 'Munyonyo', 'assets/images/munyonyo/IMG_0879.jpg', 'SKA Munyonyo', 1, 1
  UNION ALL SELECT 'Munyonyo', 'assets/images/munyonyo/IMG_0883.jpg', 'Lakeside views', 2, 1
  UNION ALL SELECT 'Munyonyo', 'assets/images/munyonyo/IMG_0912.jpg', 'Boutique comfort', 3, 1
  UNION ALL SELECT 'Munyonyo', 'assets/images/munyonyo/IMG_0973.jpg', 'Serene gardens', 4, 1
) AS g
WHERE NOT EXISTS (SELECT 1 FROM property_gallery LIMIT 1);

INSERT INTO promotions (title, tag, description, discount_type, discount_value, min_nights, branch, image, booking_url, active, sort_order)
SELECT v.title, v.tag, v.description, v.discount_type, v.discount_value, v.min_nights, v.branch, v.image, v.booking_url, v.active, v.sort_order
FROM (
  SELECT 'Book Direct & Save' AS title, 'Best Rate Guarantee' AS tag, 'Our lowest prices are always here. Free Wi-Fi, breakfast, and flexible cancellation when you book on our website.' AS description, 'percent' AS discount_type, 0.00 AS discount_value, 1 AS min_nights, 'Both' AS branch, 'assets/images/ska_naguru_home.jpeg' AS image, 'index.php#book-search' AS booking_url, 1 AS active, 1 AS sort_order
  UNION ALL SELECT 'Book 7 Days Early', 'Early Bird', 'Plan ahead and unlock exclusive savings when you reserve at least seven days before arrival.', 'percent', 10.00, 1, 'Both', 'assets/images/ska_art_home.jpg', 'naguru.php#book', 1, 2
  UNION ALL SELECT 'Stay 3 Nights, Pay for 2', 'Extended Stay', 'Celebrate longer stays — enjoy three nights and only pay for two at either property.', 'free_night', 1.00, 3, 'Both', 'assets/images/ska_furniture_home.jpg', 'index.php#book-search', 1, 3
  UNION ALL SELECT 'Direct Booking Bonus', 'Member Perk', 'Extra value when you book with us — complimentary upgrades subject to availability and welcome treats.', 'percent', 5.00, 1, 'Both', 'assets/images/ska_munyonyo_home2.jpg', 'loyalty.php', 1, 4
  UNION ALL SELECT 'Munyonyo Lakeside Weekend', 'Weekend Escape', 'Unwind by the lake with a weekend package at SKA Munyonyo — serene gardens and boutique comfort.', 'percent', 15.00, 2, 'Munyonyo', 'assets/images/ska_munyonyo_home2.jpg', 'munyonyo.php#book', 1, 5
) AS v
WHERE NOT EXISTS (SELECT 1 FROM promotions LIMIT 1);

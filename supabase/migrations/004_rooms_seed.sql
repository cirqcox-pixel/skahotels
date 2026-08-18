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

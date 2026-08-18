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

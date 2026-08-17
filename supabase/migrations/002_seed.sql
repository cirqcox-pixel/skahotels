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

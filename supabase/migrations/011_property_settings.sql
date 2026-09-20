-- Property contacts, notify inboxes, maps, and Settings page access.

INSERT INTO site_settings (setting_key, setting_value, setting_group) VALUES
  ('site_address', 'Naguru & Munyonyo, Kampala', 'contact'),
  ('footer_tagline', 'Oasis in Kampala', 'contact'),
  ('footer_blurb', 'A distinguished collection of elegant retreats redefining hospitality in Uganda — where boutique charm meets genuine warmth.', 'contact'),
  ('naguru_name', 'SKA The Boutique B&B — Naguru', 'contact'),
  ('naguru_address', '16 Naguru Vale Rd, Naguru, Kampala, Uganda', 'contact'),
  ('naguru_phone', '+256 741 186 891', 'contact'),
  ('naguru_email', 'naguru.booking@skaboutiquebnb.com', 'contact'),
  ('naguru_email_alt', 'skatheboutiquenaguru@gmail.com', 'contact'),
  ('naguru_notify_email', 'naguru.booking@skaboutiquebnb.com', 'contact'),
  ('naguru_whatsapp', 'https://wa.me/256741186891', 'contact'),
  ('naguru_map_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7474914464715!2d32.604376874723435!3d0.34140269965525194!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177dbb00112dc205%3A0xb5497e995a83a3c9!2sSKA%20The%20Boutique%20Naguru!5e0!3m2!1sen!2sug!4v1774681497576!5m2!1sen!2sug', 'contact'),
  ('munyonyo_name', 'SKA The Boutique B&B — Munyonyo', 'contact'),
  ('munyonyo_address', 'Wavamunno, Munyonyo, Kampala, Uganda', 'contact'),
  ('munyonyo_phone', '+256 200 904 877', 'contact'),
  ('munyonyo_email', 'munyonyo.booking@skaboutiquebnb.com', 'contact'),
  ('munyonyo_email_alt', 'skaboutiquebb@gmail.com', 'contact'),
  ('munyonyo_notify_email', 'munyonyo.booking@skaboutiquebnb.com', 'contact'),
  ('munyonyo_whatsapp', 'https://wa.me/256200904877', 'contact'),
  ('munyonyo_map_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.781404895553!2d32.625248074723295!3d0.24647079975110975!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177d9575a8b310b5%3A0xe6a89a69212b594d!2sSKA%20the%20Boutique%20B%26B!5e0!3m2!1sen!2sug!4v1774622522650!5m2!1sen!2sug', 'contact')
ON CONFLICT (setting_key) DO NOTHING;

CREATE OR REPLACE FUNCTION public.ska_pages_for_role(p_role TEXT)
RETURNS TEXT[]
LANGUAGE sql
IMMUTABLE
AS $$
  SELECT CASE lower(coalesce(p_role, 'manager'))
    WHEN 'super_admin' THEN ARRAY['dashboard','rooms','promotions','packages','bookings','inquiries','users','settings']
    WHEN 'manager' THEN ARRAY['dashboard','rooms','promotions','packages','bookings','inquiries','settings']
    WHEN 'reservations' THEN ARRAY['dashboard','bookings','inquiries']
    WHEN 'marketing' THEN ARRAY['dashboard','promotions','packages']
    ELSE ARRAY['dashboard','bookings']
  END;
$$;

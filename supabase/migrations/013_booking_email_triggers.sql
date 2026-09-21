-- Email every new booking / inquiry without Formspree.
-- Posts from the database so mail still goes out if the browser is blocked.

CREATE EXTENSION IF NOT EXISTS pg_net;

CREATE OR REPLACE FUNCTION public.ska_http_notify(p_to TEXT, p_body JSONB)
RETURNS BIGINT
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, net
AS $$
DECLARE
  req_id BIGINT;
BEGIN
  SELECT net.http_post(
    url := 'https://formsubmit.co/ajax/' || p_to,
    headers := jsonb_build_object(
      'Content-Type', 'application/json',
      'Accept', 'application/json',
      'Origin', 'https://www.skaboutiquebnb.com',
      'Referer', 'https://www.skaboutiquebnb.com/'
    ),
    body := p_body
  ) INTO req_id;
  RETURN req_id;
END;
$$;

CREATE OR REPLACE FUNCTION public.ska_notify_booking_row()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, net
AS $$
DECLARE
  inbox TEXT;
  subject TEXT;
BEGIN
  inbox := CASE
    WHEN NEW.branch ILIKE '%muny%' THEN 'munyonyo.booking@skaboutiquebnb.com'
    ELSE 'naguru.booking@skaboutiquebnb.com'
  END;
  subject := 'SKA Booking Request — ' || COALESCE(NEW.branch, 'Property');
  IF TG_OP = 'UPDATE' AND NEW.status IS DISTINCT FROM OLD.status THEN
    IF NEW.status = 'confirmed' THEN
      subject := 'SKA Booking Confirmed — ' || COALESCE(NEW.branch, 'Property');
    ELSIF NEW.status = 'cancelled' THEN
      subject := 'SKA Booking Cancelled — ' || COALESCE(NEW.branch, 'Property');
    ELSE
      RETURN NEW;
    END IF;
  END IF;

  PERFORM public.ska_http_notify(inbox, jsonb_build_object(
    '_subject', subject,
    '_captcha', 'false',
    '_template', 'table',
    '_replyto', COALESCE(NEW.email, inbox),
    '_cc', COALESCE(NEW.email, ''),
    'type', 'booking',
    'name', NEW.name,
    'email', NEW.email,
    'phone', NEW.phone,
    'whatsapp', NEW.whatsapp,
    'branch', NEW.branch,
    'room_type', NEW.room_type,
    'package_option', NEW.package_option,
    'guests', NEW.guests,
    'checkin', NEW.checkin,
    'checkout', NEW.checkout,
    'total', COALESCE(NEW.currency, 'USD') || ' ' || COALESCE(NEW.total, NEW.price, 0),
    'message', NEW.message,
    'notify_inbox', inbox,
    'site', 'SKA The Boutique'
  ));
  RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS ska_notify_booking_insert ON public.bookings;
CREATE TRIGGER ska_notify_booking_insert
  AFTER INSERT ON public.bookings
  FOR EACH ROW
  EXECUTE PROCEDURE public.ska_notify_booking_row();

DROP TRIGGER IF EXISTS ska_notify_booking_status ON public.bookings;
CREATE TRIGGER ska_notify_booking_status
  AFTER UPDATE OF status ON public.bookings
  FOR EACH ROW
  WHEN (OLD.status IS DISTINCT FROM NEW.status)
  EXECUTE PROCEDURE public.ska_notify_booking_row();

CREATE OR REPLACE FUNCTION public.ska_notify_inquiry_row()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, net
AS $$
BEGIN
  PERFORM public.ska_http_notify('info@skaboutiquebnb.com', jsonb_build_object(
    '_subject', 'SKA Contact: ' || COALESCE(NEW.subject, 'General Inquiry'),
    '_captcha', 'false',
    '_template', 'table',
    '_replyto', COALESCE(NEW.email, 'info@skaboutiquebnb.com'),
    '_cc', COALESCE(NEW.email, ''),
    'type', 'inquiry',
    'name', NEW.name,
    'email', NEW.email,
    'phone', NEW.phone,
    'subject', NEW.subject,
    'message', NEW.message,
    'notify_inbox', 'info@skaboutiquebnb.com',
    'site', 'SKA The Boutique'
  ));
  RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS ska_notify_inquiry_insert ON public.inquiries;
CREATE TRIGGER ska_notify_inquiry_insert
  AFTER INSERT ON public.inquiries
  FOR EACH ROW
  EXECUTE PROCEDURE public.ska_notify_inquiry_row();

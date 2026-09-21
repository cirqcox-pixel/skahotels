-- Super Admin only: permanently delete a booking row
CREATE OR REPLACE FUNCTION public.ska_delete_booking(p_id BIGINT)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  me RECORD;
BEGIN
  SELECT * INTO me FROM admin_users WHERE uid = auth.uid();
  IF me.uid IS NULL OR me.role <> 'super_admin' THEN
    RAISE EXCEPTION 'only_super_admin';
  END IF;

  DELETE FROM bookings WHERE id = p_id;
  IF NOT FOUND THEN
    RAISE EXCEPTION 'booking_not_found';
  END IF;

  RETURN jsonb_build_object('ok', true);
END;
$$;

GRANT EXECUTE ON FUNCTION public.ska_delete_booking(BIGINT) TO authenticated;

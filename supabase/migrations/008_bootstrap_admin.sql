-- Bootstrap the first GitHub Pages admin.
-- Run in SQL Editor: https://supabase.com/dashboard/project/nllqkepymtwwbvbjnbyz/sql
-- Safe to re-run.

-- Allow the first signed-in Auth user to claim admin if the allowlist is empty.
CREATE OR REPLACE FUNCTION public.claim_first_admin()
RETURNS BOOLEAN
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
BEGIN
  IF auth.uid() IS NULL THEN
    RETURN FALSE;
  END IF;

  IF EXISTS (SELECT 1 FROM public.admin_users) THEN
    RETURN EXISTS (SELECT 1 FROM public.admin_users WHERE uid = auth.uid());
  END IF;

  INSERT INTO public.admin_users (uid, email)
  SELECT id, email FROM auth.users WHERE id = auth.uid()
  ON CONFLICT (uid) DO NOTHING;

  RETURN TRUE;
END;
$$;

REVOKE ALL ON FUNCTION public.claim_first_admin() FROM PUBLIC;
GRANT EXECUTE ON FUNCTION public.claim_first_admin() TO authenticated;

-- Known staff user (Authentication → Users)
INSERT INTO public.admin_users (uid, email)
VALUES (
  'ed0e920a-07ef-4edc-9b00-abd90bf3e39c',
  'admin@skahotels.com'
)
ON CONFLICT (uid) DO NOTHING;

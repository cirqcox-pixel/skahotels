-- Public image uploads for admin packages / promotions
INSERT INTO storage.buckets (id, name, public)
VALUES ('ska-uploads', 'ska-uploads', true)
ON CONFLICT (id) DO UPDATE SET public = true;

DROP POLICY IF EXISTS "public_read_ska_uploads" ON storage.objects;
CREATE POLICY "public_read_ska_uploads" ON storage.objects
  FOR SELECT USING (bucket_id = 'ska-uploads');

DROP POLICY IF EXISTS "admin_write_ska_uploads" ON storage.objects;
CREATE POLICY "admin_write_ska_uploads" ON storage.objects
  FOR ALL
  USING (bucket_id = 'ska-uploads' AND public.is_admin())
  WITH CHECK (bucket_id = 'ska-uploads' AND public.is_admin());

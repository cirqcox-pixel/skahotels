-- Fix admin image/video uploads: ensure bucket exists, allow video, raise size cap,
-- and give admins explicit insert/update/delete rights. Safe to re-run.
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES (
  'ska-uploads', 'ska-uploads', true, 209715200,
  ARRAY['image/jpeg','image/png','image/webp','image/gif','video/mp4','video/webm','video/quicktime']
)
ON CONFLICT (id) DO UPDATE SET
  public = true,
  file_size_limit = 209715200,
  allowed_mime_types = EXCLUDED.allowed_mime_types;

DROP POLICY IF EXISTS "public_read_ska_uploads" ON storage.objects;
CREATE POLICY "public_read_ska_uploads" ON storage.objects
  FOR SELECT USING (bucket_id = 'ska-uploads');

DROP POLICY IF EXISTS "admin_write_ska_uploads" ON storage.objects;
DROP POLICY IF EXISTS "admin_insert_ska_uploads" ON storage.objects;
DROP POLICY IF EXISTS "admin_update_ska_uploads" ON storage.objects;
DROP POLICY IF EXISTS "admin_delete_ska_uploads" ON storage.objects;
CREATE POLICY "admin_insert_ska_uploads" ON storage.objects
  FOR INSERT TO authenticated WITH CHECK (bucket_id = 'ska-uploads' AND public.is_admin());
CREATE POLICY "admin_update_ska_uploads" ON storage.objects
  FOR UPDATE TO authenticated USING (bucket_id = 'ska-uploads' AND public.is_admin())
  WITH CHECK (bucket_id = 'ska-uploads' AND public.is_admin());
CREATE POLICY "admin_delete_ska_uploads" ON storage.objects
  FOR DELETE TO authenticated USING (bucket_id = 'ska-uploads' AND public.is_admin());

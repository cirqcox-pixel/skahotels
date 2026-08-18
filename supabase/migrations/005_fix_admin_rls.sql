-- Fix admin RLS: auth.role() can fail; use auth.uid() for authenticated access
-- Run in Supabase SQL Editor after 003_rls.sql

DROP POLICY IF EXISTS "admin_all_rooms" ON rooms;
DROP POLICY IF EXISTS "admin_all_bookings" ON bookings;
DROP POLICY IF EXISTS "admin_all_inquiries" ON inquiries;
DROP POLICY IF EXISTS "admin_all_promotions" ON promotions;

CREATE POLICY "admin_all_rooms" ON rooms
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);

CREATE POLICY "admin_all_bookings" ON bookings
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);

CREATE POLICY "admin_all_inquiries" ON inquiries
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);

CREATE POLICY "admin_all_promotions" ON promotions
  FOR ALL USING (auth.uid() IS NOT NULL) WITH CHECK (auth.uid() IS NOT NULL);

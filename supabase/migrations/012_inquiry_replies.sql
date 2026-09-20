-- Inquiry replies stored for admin follow-up
ALTER TABLE inquiries
  ADD COLUMN IF NOT EXISTS reply_message TEXT,
  ADD COLUMN IF NOT EXISTS replied_at TIMESTAMPTZ;

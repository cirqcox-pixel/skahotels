# Email alerts for bookings & contact forms

On GitHub Pages, forms save to **Supabase** and can also email you via **Formspree** (easiest) or **Resend** (Edge Function).

---

## Option A — Formspree (recommended, ~2 minutes)

1. Create a free account at [formspree.io](https://formspree.io)
2. Create a new form → set email to `info@skaboutiquebnb.com` (or your inbox)
3. Copy the form ID (looks like `xyzabcde`)
4. Open `assets/js/ska-config.js` and paste:

```js
formspree: {
  booking: 'YOUR_NAGURU_FORM_ID',          // notification → naguru.booking@
  bookingMunyonyo: 'YOUR_MUNYONYO_FORM_ID', // duplicate form → munyonyo.booking@
  inquiry: 'YOUR_FORM_ID'
},
```

**Munyonyo must use its own Formspree form** (same fields as Naguru). If Munyonyo bookings only CC `munyonyo.booking@` on the Naguru form, Formspree marks them as spam and nothing is delivered. Duplicate the form, set the notification email to `munyonyo.booking@skaboutiquebnb.com`, verify it, and paste the new ID into `bookingMunyonyo` (or **Admin → Property settings → Formspree form ID**).

5. Rebuild / push so `docs/` updates (or wait for GitHub Actions)

Confirm the form email in Formspree’s inbox the first time you test.

---

## Option B — Resend (via Supabase Edge Function)

Use this if you want branded “from” addresses and more control.

1. Sign up at [resend.com](https://resend.com) → create an API key  
2. Install Supabase CLI and link the project  
3. Set secrets:

```bash
supabase secrets set RESEND_API_KEY=re_xxxxxxxxx
supabase secrets set NOTIFY_FROM="SKA The Boutique <onboarding@resend.dev>"
supabase secrets set NOTIFY_TO=info@skaboutiquebnb.com
```

4. Deploy:

```bash
supabase functions deploy notify-email
```

5. In `assets/js/ska-config.js`:

```js
notify: {
  webhookUrl: 'https://nllqkepymtwwbvbjnbyz.supabase.co/functions/v1/notify-email',
  to: 'info@skaboutiquebnb.com'
},
```

You can use Formspree **and** Resend together — both fire after a successful save.

---

## What gets emailed

| Form | Subject example |
|------|-----------------|
| Booking | `SKA Booking Request — Naguru` |
| Contact | `SKA Contact: Reservation` |

Submissions still appear in **Admin → Bookings / Inquiries** even if email fails.

---

## PHP hosting

On cPanel/PHP, emails already go through `forms/process_*.php` + `SkaMailer`.

Property inboxes:

- Naguru bookings → `naguru.booking@skaboutiquebnb.com`
- Munyonyo bookings → `munyonyo.booking@skaboutiquebnb.com`

Formspree/Resend are still needed for the static GitHub Pages site so those same inboxes receive a copy after Supabase saves the booking.

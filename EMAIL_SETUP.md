# SKA booking and inquiry email

Formspree is no longer used.

| Event | Inbox |
|-------|--------|
| Naguru booking | `naguru.booking@skaboutiquebnb.com` |
| Munyonyo booking | `munyonyo.booking@skaboutiquebnb.com` |
| Website inquiry | `info@skaboutiquebnb.com` |

Guest email is CC'd so they also receive a copy. Reply-To is the guest, so staff can reply from the inbox.

## First-time activation (once)

The first live submission to each inbox sends an **Activate Form** email from FormSubmit.

Open these three mailboxes (and Spam) and click **Activate Form**:

1. `naguru.booking@skaboutiquebnb.com`
2. `munyonyo.booking@skaboutiquebnb.com`
3. `info@skaboutiquebnb.com`

After that, every booking and inquiry is delivered automatically. No Formspree form IDs.

## Optional: branded sending (Resend)

```bash
supabase secrets set RESEND_API_KEY=re_xxxxxxxxx
supabase secrets set NOTIFY_FROM="SKA The Boutique <onboarding@resend.dev>"
supabase functions deploy notify-email --no-verify-jwt
```

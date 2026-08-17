<?php
/**
 * FILE: config/SkaMailer.php
 *
 * SKA Mailer — wraps PHP's mail() with professional HTML templates.
 * No Composer needed. Swap send() for PHPMailer / SMTP if required.
 *
 * BRANCH-AWARE: reads $b['branch'] and routes admin emails + contact
 * details to the correct property automatically.
 *
 * PUBLIC METHODS
 * ──────────────
 * sendBookingPlaced($b)              → emails GUEST when they submit a request
 * sendAdminNewBooking($b)            → emails the correct BRANCH ADMIN TEAM
 * sendBookingConfirmed($b)           → emails GUEST when admin approves
 * sendBookingCancelled($b, $reason)  → emails GUEST when admin rejects/cancels
 */
class SkaMailer
{
    /* ── Shared sender identity ── */
    private string $fromEmail = 'noreply@skaboutiquebnb.com';
    private string $fromName  = 'SKA The Boutique';

    /* ══════════════════════════════════════════════════════
       BRANCH CONFIG
       Add a new entry here if you open a third property.
    ══════════════════════════════════════════════════════ */
    private array $branchConfig = [
        'Naguru' => [
            'adminEmails' => 'bookings.naguru@skaboutiquebnb.com, skatheboutiquenaguru@gmail.com',
            'replyTo'     => 'bookings.naguru@skaboutiquebnb.com',
            'phone'       => '+256 741 186 891',
            'phoneHref'   => '+256741186891',
            'email'       => 'bookings.naguru@skaboutiquebnb.com',
            'address'     => 'Naguru, Kampala, Uganda',
            'adminUrl'    => 'https://skaboutiquebnb.com/admin/bookings.php?status=pending&branch=Naguru',
        ],
        'Munyonyo' => [
            'adminEmails' => 'bookings.munyonyo@skaboutiquebnb.com, skaboutiquebb@gmail.com',
            'replyTo'     => 'bookings.munyonyo@skaboutiquebnb.com',
            'phone'       => '+256 200 904 877',
            'phoneHref'   => '+256200904877',
            'email'       => 'bookings.munyonyo@skaboutiquebnb.com',
            'address'     => 'Munyonyo, Kampala, Uganda',
            'adminUrl'    => 'https://skaboutiquebnb.com/admin/bookings.php?status=pending&branch=Munyonyo',
        ],
    ];

    /* ── Fallback if branch is unknown ── */
    private array $defaultConfig = [
        'adminEmails' => 'bookings.naguru@skaboutiquebnb.com, bookings.munyonyo@skaboutiquebnb.com',
        'replyTo'     => 'info@skaboutiquebnb.com',
        'phone'       => '+256 741 186 891',
        'phoneHref'   => '+256741186891',
        'email'       => 'info@skaboutiquebnb.com',
        'address'     => 'Kampala, Uganda',
        'adminUrl'    => 'https://skaboutiquebnb.com/admin/bookings.php?status=pending',
    ];

    /* ── Resolve config for a booking array ── */
    private function cfg(array $b): array
    {
        $branch = trim($b['branch'] ?? '');
        return $this->branchConfig[$branch] ?? $this->defaultConfig;
    }

    /* ══════════════════════════════════════════════════════
       1.  GUEST — booking request received (pending)
    ══════════════════════════════════════════════════════ */
    public function sendBookingPlaced(array $b): bool
    {
        $cfg     = $this->cfg($b);
        $subject = 'Your Booking Request — SKA The Boutique';

        $body = $this->wrap(
            'Booking Request Received',
            "Dear <strong>" . htmlspecialchars($b['name']) . "</strong>,<br><br>
             Thank you for choosing <strong>SKA The Boutique " . htmlspecialchars($b['branch'] ?? '') . "</strong>.
             We have received your reservation request and our team is reviewing it.
             You will hear from us within <strong>24 hours</strong> with a confirmation.<br><br>
             If you need to reach us sooner, please call
             <a href='tel:{$cfg['phoneHref']}' style='color:#1e5b84;'>{$cfg['phone']}</a>
             or email
             <a href='mailto:{$cfg['email']}' style='color:#1e5b84;'>{$cfg['email']}</a>.",
            $b,
            '#1e5b84',
            'Pending Review',
            '',
            $cfg
        );

        return $this->send($b['email'], $subject, $body, $cfg['replyTo']);
    }

    /* ══════════════════════════════════════════════════════
       2.  ADMIN — new booking alert (routed to correct branch)
    ══════════════════════════════════════════════════════ */
    public function sendAdminNewBooking(array $b): bool
    {
        $cfg       = $this->cfg($b);
        $bookingId = !empty($b['id']) ? '#' . $b['id'] : '';
        $branch    = htmlspecialchars($b['branch'] ?? 'SKA');
        $subject   = "New Booking Request {$bookingId} — "
                     . htmlspecialchars($b['name']) . " ({$branch})";

        /* Extra contact/request rows only shown to admins */
        $extraRows = '';
        if (!empty($b['phone'])) {
            $ph = htmlspecialchars($b['phone']);
            $extraRows .= "
            <tr>
              <td style='padding:12px 20px;font-size:13px;color:#777;
                         border-bottom:1px solid #e8e4dc;width:40%;'>Guest Phone</td>
              <td style='padding:12px 20px;font-size:13px;font-weight:600;
                         color:#1a1a1a;border-bottom:1px solid #e8e4dc;'>
                <a href='tel:{$b['phone']}' style='color:#1e5b84;'>{$ph}</a>
              </td>
            </tr>";
        }
        if (!empty($b['whatsapp'])) {
            $wa = htmlspecialchars($b['whatsapp']);
            $extraRows .= "
            <tr>
              <td style='padding:12px 20px;font-size:13px;color:#777;
                         border-bottom:1px solid #e8e4dc;'>WhatsApp</td>
              <td style='padding:12px 20px;font-size:13px;font-weight:600;
                         color:#1a1a1a;border-bottom:1px solid #e8e4dc;'>{$wa}</td>
            </tr>";
        }
        if (!empty($b['message'])) {
            $msg = nl2br(htmlspecialchars($b['message']));
            $extraRows .= "
            <tr>
              <td style='padding:12px 20px;font-size:13px;color:#777;
                         border-bottom:1px solid #e8e4dc;'>Special Requests</td>
              <td style='padding:12px 20px;font-size:13px;color:#1a1a1a;
                         border-bottom:1px solid #e8e4dc;'>{$msg}</td>
            </tr>";
        }

        $guestEmail = htmlspecialchars($b['email']);
        $guestName  = htmlspecialchars($b['name']);

        $body = $this->wrap(
            "New Booking Request {$bookingId}",
            "A new reservation request has been submitted for
             <strong>SKA The Boutique {$branch}</strong>.<br><br>
             <strong>Guest:</strong> {$guestName}
             &lt;<a href='mailto:{$b['email']}' style='color:#1e5b84;'>{$guestEmail}</a>&gt;<br><br>
             Please review and <strong>confirm</strong> or <strong>reject</strong> this booking
             in the admin portal.<br><br>
             <a href='{$cfg['adminUrl']}'
                style='display:inline-block;padding:12px 28px;background:#c9a96e;color:#fff;
                       text-decoration:none;border-radius:6px;font-weight:600;font-size:13px;
                       letter-spacing:0.04em;'>
               Review in Admin Portal →
             </a>",
            $b,
            '#e5a320',
            'Awaiting Review',
            $extraRows,
            $cfg
        );

        /* Reply-To = guest's email so admins can reply directly to them */
        return $this->send($cfg['adminEmails'], $subject, $body, $b['email']);
    }

    /* ══════════════════════════════════════════════════════
       3.  GUEST — booking confirmed by admin
    ══════════════════════════════════════════════════════ */
    public function sendBookingConfirmed(array $b): bool
    {
        $cfg     = $this->cfg($b);
        $subject = 'Booking Confirmed — SKA The Boutique ✓';

        $body = $this->wrap(
            'Your Booking is Confirmed!',
            "Dear <strong>" . htmlspecialchars($b['name']) . "</strong>,<br><br>
             Great news — your reservation at
             <strong>SKA The Boutique " . htmlspecialchars($b['branch'] ?? '') . "</strong>
             has been <strong style='color:#2e9e6b;'>confirmed</strong>.
             We look forward to welcoming you!<br><br>
             Please arrive from <strong>2:00 PM</strong> on your check-in date.
             Check-out is by <strong>12:00 PM</strong>.<br><br>
             If you need to make any changes, reply to this email or call us at
             <a href='tel:{$cfg['phoneHref']}' style='color:#1e5b84;'>{$cfg['phone']}</a>.",
            $b,
            '#2e9e6b',
            'Confirmed',
            '',
            $cfg
        );

        return $this->send($b['email'], $subject, $body, $cfg['replyTo']);
    }

    /* ══════════════════════════════════════════════════════
       4.  GUEST — booking rejected / cancelled by admin
    ══════════════════════════════════════════════════════ */
    public function sendBookingCancelled(array $b, string $reason = ''): bool
    {
        $cfg     = $this->cfg($b);
        $subject = 'Booking Unavailable — SKA The Boutique';

        $reasonHtml = $reason
            ? "<br><br><strong>Reason provided:</strong><br>" . nl2br(htmlspecialchars($reason))
            : "<br><br>We are sorry we cannot accommodate this request at this time.";

        $contactEmail = htmlspecialchars($cfg['email']);

        $body = $this->wrap(
            'Booking Request Unavailable',
            "Dear <strong>" . htmlspecialchars($b['name']) . "</strong>,<br><br>
             Unfortunately, we are unable to confirm your reservation at
             <strong>SKA The Boutique " . htmlspecialchars($b['branch'] ?? '') . "</strong>
             at this time.{$reasonHtml}<br><br>
             We would love to help you find an alternative date or room — please reach out
             and we will do our very best to accommodate you.<br><br>
             <a href='tel:{$cfg['phoneHref']}' style='color:#1e5b84;'>{$cfg['phone']}</a>
             &nbsp;|&nbsp;
             <a href='mailto:{$cfg['email']}' style='color:#1e5b84;'>{$contactEmail}</a>",
            $b,
            '#d94f4f',
            'Unavailable',
            '',
            $cfg
        );

        return $this->send($b['email'], $subject, $body, $cfg['replyTo']);
    }

    /* ══════════════════════════════════════════════════════
       PRIVATE — raw mail() wrapper
    ══════════════════════════════════════════════════════ */
    private function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $replyTo = ''
    ): bool {
        if (empty($replyTo)) $replyTo = 'info@skaboutiquebnb.com';

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        return mail($to, $subject, $htmlBody, $headers);
    }

    /* ══════════════════════════════════════════════════════
       PRIVATE — shared HTML email template
       $extraRows = optional extra <tr> blocks (admin emails)
       $cfg       = resolved branch config array
    ══════════════════════════════════════════════════════ */
    private function wrap(
        string $heading,
        string $intro,
        array  $b,
        string $statusColor,
        string $statusLabel,
        string $extraRows = '',
        array  $cfg       = []
    ): string {

        /* Calculate nights */
        $nights = 0;
        if (!empty($b['checkin']) && !empty($b['checkout'])) {
            try {
                $ci = new DateTime($b['checkin']);
                $co = new DateTime($b['checkout']);
                if ($co > $ci) $nights = (int)$co->diff($ci)->days;
            } catch (Exception $e) {}
        }

        $total      = !empty($b['total'])
                        ? 'USD ' . number_format((float)$b['total'], 0)
                        : '—';
        $priceNight = !empty($b['price'])
                        ? 'USD ' . number_format((float)$b['price'], 0) . ' / night'
                        : '—';
        $branch      = !empty($b['branch']) ? htmlspecialchars($b['branch']) : 'SKA The Boutique';
        $bookingId   = !empty($b['id'])     ? '#' . $b['id']                : '';
        $year        = date('Y');

        /* Footer address from cfg */
        $footerAddr = !empty($cfg['address'])
            ? htmlspecialchars($cfg['address'])
            : 'Kampala, Uganda';

        /* Phone from cfg (safe fallback) */
        $phoneHref  = $cfg['phoneHref'] ?? '+256741186891';
        $phoneLabel = $cfg['phone']     ?? '+256 741 186 891';

        /* Booking ID line in header */
        $idRow = $bookingId
            ? "<p style='margin:8px 0 0;font-size:12px;color:rgba(255,255,255,0.4);
                         letter-spacing:0.08em;'>Booking {$bookingId}</p>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$heading}</title>
</head>
<body style="margin:0;padding:0;background:#f4f1eb;
             font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#f4f1eb;padding:40px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0"
             style="max-width:600px;width:100%;">

        <!-- HEADER -->
        <tr>
          <td style="background:#0d1b2e;padding:32px 40px;
                     border-radius:12px 12px 0 0;text-align:center;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;
                      letter-spacing:0.22em;text-transform:uppercase;
                      color:rgba(255,255,255,0.45);">
              SKA THE BOUTIQUE — {$branch}
            </p>
            <h1 style="margin:0;font-size:24px;font-weight:300;
                       color:#ffffff;letter-spacing:0.04em;">
              {$heading}
            </h1>
            {$idRow}
            <span style="display:inline-block;margin-top:14px;padding:5px 18px;
                         background:{$statusColor};color:#fff;font-size:11px;
                         font-weight:700;letter-spacing:0.14em;text-transform:uppercase;
                         border-radius:20px;">
              {$statusLabel}
            </span>
          </td>
        </tr>

        <!-- BODY -->
        <tr>
          <td style="background:#ffffff;padding:36px 40px;">

            <p style="margin:0 0 28px;font-size:15px;line-height:1.75;color:#3a3a3a;">
              {$intro}
            </p>

            <!-- Reservation details -->
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f9f7f3;border:1px solid #e8e4dc;
                          border-radius:8px;overflow:hidden;margin-bottom:24px;">
              <tr style="background:#0d1b2e;">
                <td colspan="2" style="padding:12px 20px;">
                  <p style="margin:0;font-size:10px;font-weight:700;
                            letter-spacing:0.18em;text-transform:uppercase;
                            color:rgba(255,255,255,0.6);">
                    Reservation Details
                  </p>
                </td>
              </tr>
              <tr>
                <td style="padding:12px 20px;font-size:13px;color:#777;
                           border-bottom:1px solid #e8e4dc;width:40%;">Property</td>
                <td style="padding:12px 20px;font-size:13px;font-weight:600;
                           color:#1a1a1a;border-bottom:1px solid #e8e4dc;">{$branch}</td>
              </tr>
              <tr>
                <td style="padding:12px 20px;font-size:13px;color:#777;
                           border-bottom:1px solid #e8e4dc;">Room Type</td>
                <td style="padding:12px 20px;font-size:13px;font-weight:600;
                           color:#1a1a1a;border-bottom:1px solid #e8e4dc;">{$b['room_type']}</td>
              </tr>
              <tr>
                <td style="padding:12px 20px;font-size:13px;color:#777;
                           border-bottom:1px solid #e8e4dc;">Check-in</td>
                <td style="padding:12px 20px;font-size:13px;font-weight:600;
                           color:#1a1a1a;border-bottom:1px solid #e8e4dc;">{$b['checkin']}</td>
              </tr>
              <tr>
                <td style="padding:12px 20px;font-size:13px;color:#777;
                           border-bottom:1px solid #e8e4dc;">Check-out</td>
                <td style="padding:12px 20px;font-size:13px;font-weight:600;
                           color:#1a1a1a;border-bottom:1px solid #e8e4dc;">{$b['checkout']}</td>
              </tr>
              <tr>
                <td style="padding:12px 20px;font-size:13px;color:#777;
                           border-bottom:1px solid #e8e4dc;">Duration</td>
                <td style="padding:12px 20px;font-size:13px;font-weight:600;
                           color:#1a1a1a;border-bottom:1px solid #e8e4dc;">{$nights} night(s)</td>
              </tr>
              <tr>
                <td style="padding:12px 20px;font-size:13px;color:#777;
                           border-bottom:1px solid #e8e4dc;">Rate</td>
                <td style="padding:12px 20px;font-size:13px;font-weight:600;
                           color:#1a1a1a;border-bottom:1px solid #e8e4dc;">{$priceNight}</td>
              </tr>
              {$extraRows}
              <tr>
                <td style="padding:14px 20px;font-size:13px;color:#777;">Total Estimate</td>
                <td style="padding:14px 20px;font-size:17px;font-weight:700;color:#c9a96e;">
                  {$total}
                </td>
              </tr>
            </table>

            <p style="margin:0;font-size:13px;color:#888;line-height:1.7;">
              Questions? Reply to this email or call us at
              <a href="tel:{$phoneHref}" style="color:#1e5b84;">{$phoneLabel}</a>.
            </p>

          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background:#0d1b2e;padding:24px 40px;
                     border-radius:0 0 12px 12px;text-align:center;">
            <p style="margin:0 0 4px;font-size:13px;color:rgba(255,255,255,0.5);">
              SKA The Boutique — {$branch} &bull; {$footerAddr}
            </p>
            <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.3);">
              &copy; {$year} SKA The Boutique. All rights reserved.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>

</body>
</html>
HTML;
    }
}
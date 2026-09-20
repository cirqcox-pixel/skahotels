<?php
/**
 * Shared booking save + email for Naguru / Munyonyo property forms.
 */
require_once __DIR__ . '/../config/cms.php';
require_once __DIR__ . '/../config/SkaMailer.php';

function ska_submit_property_booking(string $branch, string $redirectPage, string $errorPhone): void
{
    $c = cms_conn();

    $name     = trim($_POST['name']      ?? '');
    $email    = trim($_POST['email']     ?? '');
    $whatsapp = trim($_POST['whatsapp']  ?? '');
    $phone    = trim($_POST['phone']     ?? '');
    $room     = trim($_POST['room_type'] ?? '');
    $price    = (float)($_POST['price']  ?? 0);
    $checkin  = trim($_POST['checkin']   ?? '');
    $checkout = trim($_POST['checkout']  ?? '');
    $message  = trim($_POST['message']   ?? '');
    $season   = trim($_POST['season']    ?? 'low');
    $packageId = (int)($_POST['package_id'] ?? 0);
    $packageOption = trim($_POST['package_option'] ?? '');
    $currency = strtoupper(trim($_POST['currency'] ?? 'USD')) ?: 'USD';
    $guests   = (int)($_POST['guests'] ?? 0);

    $errors = [];
    if ($name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if ($phone === '') $errors[] = 'Phone number is required.';
    if ($checkin === '') $errors[] = 'Check-in / event date is required.';
    if ($checkout === '') $errors[] = 'Check-out date is required.';

    $pkg = null;
    if ($packageId > 0) {
        $pkg = cms_package($packageId);
        if (!$pkg || (!$pkg['active'] && $pkg['active'] !== '1' && $pkg['active'] !== 1)) {
            $errors[] = 'The selected package is no longer available.';
        } elseif (!in_array($pkg['branch'], [$branch, 'Both', 'All'], true)) {
            $errors[] = 'That package is not offered at this property.';
        } else {
            $currency = strtoupper($pkg['currency'] ?: 'UGX');
            $opts = cms_package_options($pkg);
            $chosen = null;
            foreach ($opts as $opt) {
                if ($packageOption === '' || $packageOption === ($opt['label'] ?? '')) {
                    $chosen = $opt;
                    if ($packageOption !== '') break;
                }
            }
            if (!$chosen) $chosen = $opts[0] ?? null;
            if (!$chosen) {
                $errors[] = 'Please select a package option.';
            } else {
                $packageOption = $chosen['label'] ?? $pkg['title'];
                $unit = (float)($chosen['price'] ?? $pkg['price'] ?? 0);
                $pricing = $chosen['pricing'] ?? ($pkg['pricing_mode'] ?? 'fixed');
                $price = $unit;
                if ($pricing === 'per_person') {
                    if ($guests < 1) $guests = 1;
                    $total = $unit * $guests;
                } else {
                    $total = $unit;
                    if ($guests < 1) $guests = 0;
                }
                $room = $pkg['title'] . ' — ' . $packageOption;
            }
        }
    } else {
        if ($room === '') $errors[] = 'Please select a room type.';
    }

    if (!empty($errors)) {
        $msg = urlencode(implode(' ', $errors));
        header("Location: {$redirectPage}?error={$msg}#book");
        exit;
    }

    try {
        $ci = new DateTime($checkin);
        $co = new DateTime($checkout);
        if ($co <= $ci) {
            $co = (clone $ci)->modify('+1 day');
            $checkout = $co->format('Y-m-d');
        }
        $nights = (int)$co->diff($ci)->days;
        if ($nights < 1) $nights = 1;
    } catch (Exception $e) {
        $nights = 1;
    }

    if ($pkg === null) {
        $posted = $_POST['total'] ?? '';
        $total = ($posted !== '' && is_numeric($posted))
            ? (float)$posted
            : $price * $nights;
        $currency = 'USD';
    }

    $pkgIdSql = $packageId > 0 ? $packageId : null;
    $guestsSql = $guests > 0 ? $guests : null;

    $dbSaved = false;
    $bookingId = null;

    $stmt = $c->prepare("
        INSERT INTO bookings
            (name, email, phone, whatsapp, room_type, price,
             checkin, checkout, total, message, season, branch, status,
             package_id, package_option, currency, guests, created_at)
        VALUES
            (?, ?, ?, ?, ?, ?,
             ?, ?, ?, ?, ?, ?, 'pending',
             ?, ?, ?, ?, NOW())
    ");

    if ($stmt) {
        $stmt->bind_param(
            'sssssdssdsssissi',
            $name, $email, $phone, $whatsapp, $room, $price,
            $checkin, $checkout, $total, $message, $season, $branch,
            $pkgIdSql, $packageOption, $currency, $guestsSql
        );
        if ($stmt->execute()) {
            $dbSaved = true;
            $bookingId = $c->insert_id;
        }
        $stmt->close();
    }

    $booking = [
        'id'             => $bookingId,
        'name'           => $name,
        'email'          => $email,
        'phone'          => $phone,
        'whatsapp'       => $whatsapp,
        'room_type'      => $room,
        'price'          => $price,
        'checkin'        => $checkin,
        'checkout'       => $checkout,
        'total'          => $total,
        'message'        => $message,
        'season'         => $season,
        'branch'         => $branch,
        'status'         => 'pending',
        'package_id'     => $pkgIdSql,
        'package_option' => $packageOption,
        'currency'       => $currency,
        'guests'         => $guestsSql,
    ];

    $guestSent = false;
    if ($dbSaved) {
        $mailer = new SkaMailer();
        $guestSent = $mailer->sendBookingPlaced($booking);
        $mailer->sendAdminNewBooking($booking);
    }

    if ($dbSaved) {
        $flag = $guestSent ? 'success' : 'saved';
        header("Location: {$redirectPage}?booking={$flag}#book");
    } else {
        $err = urlencode('We could not record your booking. Please call us directly on ' . $errorPhone . '.');
        header("Location: {$redirectPage}?error={$err}#book");
    }
    exit;
}

<?php
/**
 * SKA CMS — database helpers, settings, page blocks, gallery
 */
require_once __DIR__ . '/site.php';

function cms_conn(): mysqli
{
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        require __DIR__ . '/db.php';
    }
    return $conn;
}

function cms_bootstrap(): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $c = cms_conn();
    $c->query("CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        subject VARCHAR(255) DEFAULT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @$c->query("ALTER TABLE inquiries ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0");

    $c->query("CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        setting_group VARCHAR(50) DEFAULT 'general',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS cms_pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(80) NOT NULL UNIQUE,
        page_title VARCHAR(255) NOT NULL,
        meta_description TEXT,
        hero_eyebrow VARCHAR(255) DEFAULT NULL,
        hero_title VARCHAR(255) DEFAULT NULL,
        hero_subtitle TEXT,
        hero_image VARCHAR(500) DEFAULT NULL,
        body_html MEDIUMTEXT,
        active TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS cms_blocks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_slug VARCHAR(80) NOT NULL,
        block_key VARCHAR(80) NOT NULL,
        tag VARCHAR(120) DEFAULT NULL,
        title VARCHAR(255) DEFAULT NULL,
        subtitle VARCHAR(255) DEFAULT NULL,
        body TEXT,
        image VARCHAR(500) DEFAULT NULL,
        link_url VARCHAR(500) DEFAULT NULL,
        link_label VARCHAR(120) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_page_block (page_slug, block_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS property_gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        branch VARCHAR(50) NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        caption VARCHAR(255) DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    cms_seed_defaults($c);
}

function cms_seed_defaults(mysqli $c): void
{
    $check = $c->query("SELECT COUNT(*) AS n FROM site_settings");
    if ($check && (int)$check->fetch_assoc()['n'] === 0) {
        cms_seed_settings($c);
    }

    $pc = $c->query("SELECT COUNT(*) AS n FROM cms_pages");
    if ($pc && (int)$pc->fetch_assoc()['n'] === 0) {
        cms_seed_pages_and_blocks($c);
    }
}

function cms_seed_settings(mysqli $c): void
{
    $settings = [
        ['site_email', SITE_EMAIL, 'contact'],
        ['site_phone_main', '+256 200 98777', 'contact'],
        ['site_phone_naguru', '+256 741 186 891', 'contact'],
        ['site_phone_munyonyo', '+256 200 904 877', 'contact'],
        ['facebook_url', 'https://www.facebook.com/skaboutiquebnb', 'social'],
        ['instagram_url', 'https://www.instagram.com/skanaguru/', 'social'],
        ['whatsapp_url', 'https://wa.me/256741186891', 'social'],
        ['hero_slide_1_image', 'assets/images/ska_naguru_home.jpeg', 'homepage'],
        ['hero_slide_1_alt', 'SKA Naguru boutique hotel in Kampala', 'homepage'],
        ['hero_slide_2_image', 'assets/images/ska_munyonyo_home2.jpg', 'homepage'],
        ['hero_slide_2_alt', 'SKA Munyonyo lakeside boutique retreat', 'homepage'],
    ];
    $stmt = $c->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");
    foreach ($settings as $s) {
        $stmt->bind_param('sss', $s[0], $s[1], $s[2]);
        $stmt->execute();
    }
    $stmt->close();
}

function cms_seed_pages_and_blocks(mysqli $c): void
{
    $pages = [
        ['offers', 'Special Offers & Packages', 'Exclusive direct-booking offers at SKA The Boutique.', 'Deals & Packages', 'Get Away, Get More', 'Book direct for our best rates — free breakfast, Wi-Fi, and flexible check-in included with every reservation.', 'assets/images/ska_naguru_home.jpeg', ''],
        ['about-us', 'About Us | SKA The Boutique', 'Discover SKA The Boutique — two distinctive properties in Kampala.', 'Our Story', 'Redefining Boutique Hospitality', 'A distinguished collection of elegant retreats in Naguru and Munyonyo.', 'assets/images/dube_munyonyo.jpg', ''],
        ['meetings-events', 'Meetings & Events', 'Intimate meetings, weddings and events at SKA The Boutique.', 'Events & Meetings', 'Memorable Gatherings, Intimate Scale', 'From boardroom briefings to sunset celebrations — SKA offers refined spaces with boutique warmth.', 'assets/images/ska_munyonyo_home2.jpg', ''],
        ['help', 'Help Centre', 'Answers to common questions about booking and stays at SKA.', 'Help Centre', 'How Can We Help?', 'Everything you need to know before, during, and after your stay.', null, ''],
        ['careers', 'Careers at SKA', 'Join SKA The Boutique — hospitality careers in Kampala.', 'Careers', 'Discover Career Opportunities at SKA', 'Hospitality · Front Office · Kitchen & Housekeeping', 'assets/images/ska_art_home.jpg', ''],
        ['loyalty', 'SKA Rewards', 'Join SKA Rewards for member rates and exclusive offers.', 'SKA Rewards', 'Your Boutique Loyalty Programme', 'Every direct stay brings you closer to exclusive perks.', 'assets/images/ska_art_home.jpg', ''],
        ['privacy-policy', 'Privacy Policy', 'Privacy Policy for SKA The Boutique.', null, 'Privacy Policy', 'How we collect, use and protect your personal data.', null, cms_default_privacy()],
        ['terms-of-use', 'Terms of Use', 'Terms governing use of skaboutiquebnb.com.', null, 'Terms of Use', 'Terms for reservations and website use.', null, cms_default_terms()],
        ['cookie-policy', 'Cookie Policy', 'Cookie Policy for skaboutiquebnb.com.', null, 'Cookie Policy', 'How we use cookies on our website.', null, cms_default_cookies()],
        ['naguru', 'SKA Naguru', 'Boutique hotel in Naguru, Kampala.', null, null, null, null, ''],
        ['munyonyo', 'SKA Munyonyo', 'Lakeside boutique hotel in Munyonyo.', null, null, null, null, ''],
    ];
    $pStmt = $c->prepare("INSERT INTO cms_pages (slug, page_title, meta_description, hero_eyebrow, hero_title, hero_subtitle, hero_image, body_html) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($pages as $p) {
        $pStmt->bind_param('ssssssss', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7]);
        $pStmt->execute();
    }
    $pStmt->close();

    $blocks = [
        ['help', 'booking', 'Booking', 'How do I make a reservation?', null, 'Select your property on our homepage, choose dates and room type, and submit a reservation request. Our team confirms within 24 hours.', null, null, null, 1],
        ['help', 'rates', 'Rates', 'Is booking on this website the best rate?', null, 'Yes — our Best Rate Guarantee ensures the lowest price when you book direct, plus complimentary breakfast and Wi-Fi.', null, null, null, 2],
        ['help', 'cancel', 'Cancellation', 'Can I modify or cancel my booking?', null, 'Contact us at least 48 hours before check-in. Flexible cancellation terms apply to direct bookings.', null, null, null, 3],
        ['meetings-events', 'business', 'Corporate', 'Business Meetings', null, 'Private meeting rooms with natural light, high-speed Wi-Fi, refreshments, and dedicated support.', 'assets/images/ska_naguru_home.jpeg', 'contact.php?subject=Business+Meeting', 'Plan a Meeting', 1],
        ['meetings-events', 'weddings', 'Celebrations', 'Weddings', null, 'Intimate wedding ceremonies and receptions surrounded by gardens and lake views.', 'assets/images/ska_munyonyo_home2.jpg', 'contact.php?subject=Wedding+Enquiry', 'Start Planning', 2],
        ['meetings-events', 'social', 'Social', 'Social Events', null, 'Birthdays, anniversaries, baby showers, and private dinners.', 'assets/images/ska_art_home.jpg', 'contact.php?subject=Social+Event', 'Enquire', 3],
        ['loyalty', 'member_rates', null, 'Member Rates', null, 'Access preferential pricing on select room categories when you book direct as a SKA Rewards member.', null, null, null, 1],
        ['loyalty', 'early_access', null, 'Early Access', null, 'Be first to know about seasonal offers, new packages, and limited availability dates.', null, null, null, 2],
        ['loyalty', 'stay_perks', null, 'Stay Perks', null, 'Complimentary room upgrades, welcome amenities, and late checkout — subject to availability.', null, null, null, 3],
        ['careers', 'front_office', null, 'Front Office', null, 'Guest relations, reservations, and concierge — the face of SKA hospitality.', null, null, null, 1],
        ['careers', 'kitchen', null, 'Kitchen & Dining', null, 'From breakfast service to event catering — culinary excellence in a boutique setting.', null, null, null, 2],
        ['careers', 'housekeeping', null, 'Housekeeping', null, 'Impeccable standards that make every room feel like a private retreat.', null, null, null, 3],
        ['naguru', 'dining', 'RESTAURANT', 'Fine Dining', null, 'Savor refined cuisine crafted with precision and artistry throughout your stay.', 'assets/images/naguru/restaurant.jpg', '#contact', 'Learn More', 1],
        ['naguru', 'garden', 'GARDENS', 'Serene Settings', null, 'Wander through lush gardens and unwind in tranquil greenery.', 'assets/images/naguru/garden.jpg', '#contact', 'Learn More', 2],
        ['naguru', 'hero_video', null, null, null, null, 'assets/video/ska_naguru.mp4', null, null, 0],
        ['munyonyo', 'dining', 'RESTAURANT', 'Fine Dining', null, 'Exceptional dining experiences with lake-view ambiance.', 'assets/images/naguru/restaurant.jpg', '#contact', 'Learn More', 1],
        ['munyonyo', 'garden', 'GARDENS', 'Serene Settings', null, 'Lakeside gardens perfect for relaxation and events.', 'assets/images/naguru/garden.jpg', '#contact', 'Learn More', 2],
        ['munyonyo', 'hero_video', null, null, null, null, 'assets/video/ska_munyonyo.mp4', null, null, 0],
        ['offers', 'corporate', null, 'Corporate & Group Rates', null, 'Hosting a delegation or team retreat? We craft tailored packages for corporate travellers and group bookings.', null, 'contact.php?subject=Corporate+Rates', 'Request a Quote', 10],
        ['offers', 'early_bird', null, 'Early Bird Packages', null, 'Book 21 days or more in advance and receive preferential rates on select room categories.', null, 'naguru.php#book', 'Check Availability', 11],
        ['offers', 'gift_voucher', null, 'Gift Vouchers', null, 'Give the gift of a boutique escape — redeemable at either property.', null, 'contact.php?subject=Gift+Voucher', 'Purchase a Voucher', 12],
    ];
    $bStmt = $c->prepare("INSERT INTO cms_blocks (page_slug, block_key, tag, title, subtitle, body, image, link_url, link_label, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)");
    foreach ($blocks as $b) {
        $bStmt->bind_param('sssssssssi', $b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7], $b[8], $b[9]);
        $bStmt->execute();
    }
    $bStmt->close();
}

function cms_default_privacy(): string
{
    return '<h2>1. Introduction</h2><p>SKA The Boutique operates skaboutiquebnb.com. This policy explains how we collect, use, and safeguard your personal information.</p><h3>2. Information We Collect</h3><p>When you make a reservation or contact us, we may collect name, email, phone, stay dates, and special requests.</p><h3>3. Contact</h3><p>Questions: info@skaboutiquebnb.com</p>';
}

function cms_default_terms(): string
{
    return '<h2>1. Acceptance</h2><p>By accessing skaboutiquebnb.com, you agree to these Terms of Use.</p><h2>2. Reservations</h2><p>Online submissions constitute reservation requests. Confirmation is sent within 24 hours.</p><h2>3. Governing Law</h2><p>These terms are governed by the laws of the Republic of Uganda.</p>';
}

function cms_default_cookies(): string
{
    return '<h2>What Are Cookies?</h2><p>Cookies are small text files stored on your device when you visit a website.</p><h3>Cookies We Use</h3><ul><li><strong>Essential cookies</strong> — required for the website to function</li><li><strong>Analytics cookies</strong> — help us understand visitor behaviour</li></ul>';
}

function cms_setting(string $key, string $default = ''): string
{
    cms_bootstrap();
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];

    $c = cms_conn();
    $stmt = $c->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $val = ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') ? $row['setting_value'] : $default;
    $cache[$key] = $val;
    return $val;
}

function cms_page(string $slug): ?array
{
    cms_bootstrap();
    static $cache = [];
    if (isset($cache[$slug])) return $cache[$slug];

    $c = cms_conn();
    $stmt = $c->prepare("SELECT * FROM cms_pages WHERE slug = ? AND active = 1 LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $cache[$slug] = $row ?: null;
    return $cache[$slug];
}

function cms_blocks(string $pageSlug, bool $activeOnly = true): array
{
    cms_bootstrap();
    $c = cms_conn();
    $sql = "SELECT * FROM cms_blocks WHERE page_slug = ?" . ($activeOnly ? " AND active = 1" : "") . " ORDER BY sort_order ASC, id ASC";
    $stmt = $c->prepare($sql);
    $stmt->bind_param('s', $pageSlug);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function cms_block(string $pageSlug, string $blockKey, ?array $defaults = null): array
{
    cms_bootstrap();
    $c = cms_conn();
    $stmt = $c->prepare("SELECT * FROM cms_blocks WHERE page_slug = ? AND block_key = ? AND active = 1 LIMIT 1");
    $stmt->bind_param('ss', $pageSlug, $blockKey);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) return $row;
    return $defaults ?? ['tag' => '', 'title' => '', 'subtitle' => '', 'body' => '', 'image' => '', 'link_url' => '', 'link_label' => ''];
}

function cms_promotions(bool $activeOnly = true): array
{
    cms_bootstrap();
    $c = cms_conn();
    $sql = "SELECT * FROM promotions";
    if ($activeOnly) {
        $sql .= " WHERE active = 1 AND (valid_from IS NULL OR valid_from <= CURDATE()) AND (valid_to IS NULL OR valid_to >= CURDATE())";
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";
    $res = $c->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function cms_gallery(string $branch, bool $includeRoomImages = true): array
{
    cms_bootstrap();
    $c = cms_conn();
    $images = [];

    $stmt = $c->prepare("SELECT image_path AS path, caption FROM property_gallery WHERE branch = ? AND active = 1 ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param('s', $branch);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $g) {
        $images[] = ['path' => $g['path'], 'caption' => $g['caption'] ?? ''];
    }
    $stmt->close();

    if ($includeRoomImages) {
        $b = $branch;
        $rs = $c->prepare("SELECT ri.image_path AS path, r.name AS caption FROM room_images ri JOIN rooms r ON r.id = ri.room_id WHERE r.branch = ? ORDER BY ri.id ASC LIMIT 12");
        $rs->bind_param('s', $b);
        $rs->execute();
        foreach ($rs->get_result()->fetch_all(MYSQLI_ASSOC) as $g) {
            $images[] = ['path' => $g['path'], 'caption' => $g['caption'] ?? ''];
        }
        $rs->close();
    }

    return $images;
}

function cms_all_settings(): array
{
    cms_bootstrap();
    $c = cms_conn();
    $out = [];
    $res = $c->query("SELECT setting_key, setting_value, setting_group FROM site_settings ORDER BY setting_group, setting_key");
    if ($res) while ($r = $res->fetch_assoc()) $out[$r['setting_key']] = $r;
    return $out;
}

function cms_save_setting(string $key, string $value, string $group = 'general'): void
{
    cms_bootstrap();
    $c = cms_conn();
    $stmt = $c->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)");
    $stmt->bind_param('sss', $key, $value, $group);
    $stmt->execute();
    $stmt->close();
}

cms_bootstrap();

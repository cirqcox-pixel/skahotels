<?php
/**
 * SKA CMS — database helpers, settings, page blocks, gallery
 */
require_once __DIR__ . '/site.php';

function cms_conn(): mysqli
{
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        require_once __DIR__ . '/db.php';
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

    // ── Rooms / bookings / promotions / admins ──────────────────────────────
    // These are referenced by public pages (rooms, promotions, gallery) and the
    // booking forms. Self-healing here means the site works on a fresh cPanel
    // MySQL database without a manual import. See database/schema_mysql.sql for
    // the canonical, importable version (identical columns).
    $c->query("CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        price_low DECIMAL(10,2) DEFAULT NULL,
        price_shoulder DECIMAL(10,2) DEFAULT NULL,
        price_high DECIMAL(10,2) DEFAULT NULL,
        description TEXT,
        branch VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_rooms_branch (branch)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS room_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_room_images_room (room_id),
        CONSTRAINT fk_room_images_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS room_amenities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        icon_class VARCHAR(120) DEFAULT NULL,
        name VARCHAR(120) NOT NULL,
        INDEX idx_room_amenities_room (room_id),
        CONSTRAINT fk_room_amenities_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) DEFAULT NULL,
        whatsapp VARCHAR(50) DEFAULT NULL,
        room_type VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) DEFAULT 0,
        checkin DATE NOT NULL,
        checkout DATE NOT NULL,
        total DECIMAL(10,2) DEFAULT 0,
        message TEXT,
        season VARCHAR(20) DEFAULT 'low',
        branch VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bookings_status (status),
        INDEX idx_bookings_branch (branch)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS promotions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        tag VARCHAR(120) DEFAULT NULL,
        description TEXT,
        discount_type VARCHAR(20) DEFAULT 'percent',
        discount_value DECIMAL(10,2) DEFAULT 0,
        min_nights INT DEFAULT 1,
        branch VARCHAR(50) DEFAULT 'Both',
        image VARCHAR(500) DEFAULT NULL,
        booking_url VARCHAR(500) DEFAULT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        valid_from DATE DEFAULT NULL,
        valid_to DATE DEFAULT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $c->query("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
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

    $rc = $c->query("SELECT COUNT(*) AS n FROM rooms");
    if ($rc && (int)$rc->fetch_assoc()['n'] === 0) {
        cms_seed_rooms($c);
    }

    $pr = $c->query("SELECT COUNT(*) AS n FROM promotions");
    if ($pr && (int)$pr->fetch_assoc()['n'] === 0) {
        cms_seed_promotions($c);
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

function cms_seed_rooms(mysqli $c): void
{
    $rooms = [
        ['Standard Room', 150, 130, 150, 170, 'Cosy ensuite room with garden views — ideal for solo travellers and short stays.', 'Naguru'],
        ['Deluxe Room', 180, 160, 180, 200, 'Spacious deluxe room with premium linens, smart TV and boutique ensuite.', 'Naguru'],
        ['Deluxe Twin', 190, 170, 190, 210, 'Twin deluxe configuration — perfect for friends or colleagues travelling together.', 'Naguru'],
        ['Superior Room', 220, 200, 220, 250, 'Our finest Naguru category with elevated views, extra space and curated amenities.', 'Naguru'],
        ['Standard Double', 180, 160, 180, 200, 'Comfortable lakeside double room with ensuite and garden access.', 'Munyonyo'],
        ['Deluxe Room', 210, 190, 210, 230, 'Deluxe lakeside room with refined finishes and tranquil views.', 'Munyonyo'],
        ['Superior Room', 240, 220, 240, 270, 'Superior category with generous space and premium Munyonyo outlook.', 'Munyonyo'],
        ['Dube Suite', 280, 260, 280, 320, 'Signature suite — the ultimate lakeside boutique escape at SKA Munyonyo.', 'Munyonyo'],
    ];
    $stmt = $c->prepare("INSERT INTO rooms (name, price, price_low, price_shoulder, price_high, description, branch) VALUES (?,?,?,?,?,?,?)");
    foreach ($rooms as $r) {
        $stmt->bind_param('sddddss', $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6]);
        $stmt->execute();
    }
    $stmt->close();

    $images = [
        ['Standard Room', 'Naguru', 'assets/images/standard_naguru.jpeg'],
        ['Deluxe Room', 'Naguru', 'assets/images/deluxe_naguru.jpeg'],
        ['Deluxe Twin', 'Naguru', 'assets/images/deluxe_twin_naguru.jpeg'],
        ['Superior Room', 'Naguru', 'assets/images/superior_naguru.jpeg'],
        ['Standard Double', 'Munyonyo', 'assets/images/munyonyo/standard_double_munyonyo.jpg'],
        ['Deluxe Room', 'Munyonyo', 'assets/images/deluxe_munyonyo.jpg'],
        ['Superior Room', 'Munyonyo', 'assets/images/superior_munyonyo.jpg'],
        ['Dube Suite', 'Munyonyo', 'assets/images/dube_munyonyo.jpg'],
    ];
    $img = $c->prepare("INSERT INTO room_images (room_id, image_path) SELECT id, ? FROM rooms WHERE name = ? AND branch = ? LIMIT 1");
    foreach ($images as $i) {
        $img->bind_param('sss', $i[2], $i[0], $i[1]);
        $img->execute();
    }
    $img->close();

    $galCheck = $c->query("SELECT COUNT(*) AS n FROM property_gallery");
    if ($galCheck && (int)$galCheck->fetch_assoc()['n'] === 0) {
        $gallery = [
            ['Naguru', 'assets/images/naguru/IMG_1044.jpg', 'SKA Naguru', 1],
            ['Naguru', 'assets/images/naguru/IMG_1066.jpg', 'Garden views', 2],
            ['Naguru', 'assets/images/naguru/IMG_1069.jpg', 'Boutique interiors', 3],
            ['Naguru', 'assets/images/naguru/IMG_1093.jpg', 'Relaxation spaces', 4],
            ['Naguru', 'assets/images/naguru/IMG_1120.jpg', 'SKA Naguru retreat', 5],
            ['Naguru', 'assets/images/naguru/IMG_1157.jpg', 'Hillside setting', 6],
            ['Munyonyo', 'assets/images/munyonyo/IMG_0879.jpg', 'SKA Munyonyo', 1],
            ['Munyonyo', 'assets/images/munyonyo/IMG_0883.jpg', 'Lakeside views', 2],
            ['Munyonyo', 'assets/images/munyonyo/IMG_0912.jpg', 'Boutique comfort', 3],
            ['Munyonyo', 'assets/images/munyonyo/IMG_0973.jpg', 'Serene gardens', 4],
        ];
        $gStmt = $c->prepare("INSERT INTO property_gallery (branch, image_path, caption, sort_order, active) VALUES (?,?,?,?,1)");
        foreach ($gallery as $g) {
            $gStmt->bind_param('sssi', $g[0], $g[1], $g[2], $g[3]);
            $gStmt->execute();
        }
        $gStmt->close();
    }
}

function cms_seed_promotions(mysqli $c): void
{
    $promos = [
        ['Book Direct & Save', 'Best Rate Guarantee', 'Our lowest prices are always here. Free Wi-Fi, breakfast, and flexible cancellation when you book on our website.', 'percent', 0, 1, 'Both', 'assets/images/ska_naguru_home.jpeg', 'index.php#book-search', 1],
        ['Book 7 Days Early', 'Early Bird', 'Plan ahead and unlock exclusive savings when you reserve at least seven days before arrival.', 'percent', 10, 1, 'Both', 'assets/images/ska_art_home.jpg', 'naguru.php#book', 2],
        ['Stay 3 Nights, Pay for 2', 'Extended Stay', 'Celebrate longer stays — enjoy three nights and only pay for two at either property.', 'free_night', 1, 3, 'Both', 'assets/images/ska_furniture_home.jpg', 'index.php#book-search', 3],
        ['Direct Booking Bonus', 'Member Perk', 'Extra value when you book with us — complimentary upgrades subject to availability and welcome treats.', 'percent', 5, 1, 'Both', 'assets/images/ska_munyonyo_home2.jpg', 'loyalty.php', 4],
        ['Munyonyo Lakeside Weekend', 'Weekend Escape', 'Unwind by the lake with a weekend package at SKA Munyonyo — serene gardens and boutique comfort.', 'percent', 15, 2, 'Munyonyo', 'assets/images/ska_munyonyo_home2.jpg', 'munyonyo.php#book', 5],
    ];
    $stmt = $c->prepare("INSERT INTO promotions (title, tag, description, discount_type, discount_value, min_nights, branch, image, booking_url, active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,1,?)");
    foreach ($promos as $p) {
        $stmt->bind_param('ssssdisssi', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9]);
        $stmt->execute();
    }
    $stmt->close();
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

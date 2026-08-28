<?php
/**
 * SKA The Boutique — global site configuration
 */
define('SITE_NAME', 'SKA The Boutique');
define('SITE_TAGLINE', 'Oasis in Kampala');
define('SITE_URL', 'https://www.skaboutiquebnb.com');
define('SITE_EMAIL', 'info@skaboutiquebnb.com');

define('BUILDER_NAME', 'Cirqco Systems');
define('BUILDER_URL', 'https://cirqco.com/');
define('BUILDER_FOUNDER', 'Maxwell Odoi');
define('BUILDER_FOUNDER_TITLE', 'Founder & CEO');
define('BUILDER_DESCRIPTION', 'Cirqco Systems is a technology company that designs and engineers software, digital products, and web platforms.');
define('BUILDER_CREDIT', 'Engineered by Cirqco Systems, a technology company. Founded by Maxwell Odoi, CEO.');

define('BRANCHES', [
    'naguru' => [
        'name'     => 'SKA Naguru',
        'full'     => 'SKA The Boutique Naguru',
        'slug'     => 'naguru',
        'location' => 'Naguru, Kampala',
        'phone'    => '+256 741 186 891',
        'phoneHref'=> '+256741186891',
        'email'    => 'bookings.naguru@skaboutiquebnb.com',
        'mapUrl'   => 'https://maps.app.goo.gl/v7MTcy8fwFjPbHF8A',
        'rating'   => '4.2',
        'reviews'  => 5,
        'image'    => 'assets/images/ska_naguru_home.jpeg',
        'form'     => 'forms/process_contact_naguru.php',
        'page'     => 'naguru.php',
    ],
    'munyonyo' => [
        'name'     => 'SKA Munyonyo',
        'full'     => 'SKA The Boutique Munyonyo',
        'slug'     => 'munyonyo',
        'location' => 'Munyonyo, Kampala',
        'phone'    => '+256 200 904 877',
        'phoneHref'=> '+256200904877',
        'email'    => 'bookings.munyonyo@skaboutiquebnb.com',
        'mapUrl'   => 'https://maps.app.goo.gl/search/SKA+The+Boutique+Munyonyo',
        'rating'   => '4.6',
        'reviews'  => 12,
        'image'    => 'assets/images/ska_munyonyo_home2.jpg',
        'form'     => 'forms/process_contact_munyonyo.php',
        'page'     => 'munyonyo.php',
    ],
]);

function ska_branch(string $slug): array
{
    return BRANCHES[$slug] ?? BRANCHES['naguru'];
}

function ska_url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return rtrim(SITE_URL, '/') . ($path ? '/' . $path : '');
}

function ska_builder_org_id(): string
{
    return rtrim(BUILDER_URL, '/') . '/#organization';
}

function ska_builder_founder_id(): string
{
    return rtrim(BUILDER_URL, '/') . '/#founder';
}

function ska_builder_credit_html(): string
{
    $name = htmlspecialchars(BUILDER_NAME, ENT_QUOTES, 'UTF-8');
    $url  = htmlspecialchars(BUILDER_URL, ENT_QUOTES, 'UTF-8');
    return '<p class="ska-footer__credit">'
        . 'Engineered by <a href="' . $url . '" target="_blank" rel="noopener noreferrer author">' . $name . '</a>'
        . '</p>';
}

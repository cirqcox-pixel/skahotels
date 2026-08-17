<?php
/**
 * SKA The Boutique — global site configuration
 */
define('SITE_NAME', 'SKA The Boutique');
define('SITE_TAGLINE', 'Oasis in Kampala');
define('SITE_URL', 'https://www.skaboutiquebnb.com');
define('SITE_EMAIL', 'info@skaboutiquebnb.com');
define('BUILDER_NAME', 'Cirqco');
define('BUILDER_URL', 'https://cirqco.com/');

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

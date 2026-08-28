<?php
/**
 * World-class SEO head renderer for SKA The Boutique
 *
 * Pass $pageMeta before including page-start.php:
 *   $pageMeta = [
 *     'title'       => 'Page Title',
 *     'description' => '...',
 *     'path'        => 'offers',
 *     'image'       => 'assets/images/...',
 *     'type'        => 'website', // or article, hotel
 *     'schema'      => [...],     // optional extra JSON-LD
 *     'noindex'     => false,
 *   ];
 */
function ska_render_seo(array $meta = []): void
{
    require_once __DIR__ . '/../config/site.php';

    $title       = htmlspecialchars($meta['title'] ?? SITE_NAME . ' | ' . SITE_TAGLINE);
    $description = htmlspecialchars($meta['description'] ?? 'Luxury boutique bed & breakfast in Naguru and Munyonyo, Kampala. Book direct for the best rates, free breakfast and Wi-Fi.');
    $path        = trim($meta['path'] ?? '', '/');
    $canonical   = ska_url($path);
    $image       = $meta['image'] ?? 'assets/images/ska_naguru_home.jpeg';
    $imageAbs    = str_starts_with($image, 'http') ? $image : ska_url($image);
    $type        = $meta['type'] ?? 'website';
    $robots      = !empty($meta['noindex']) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1';

    echo "<meta charset=\"UTF-8\">\n";
    echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, viewport-fit=cover\">\n";
    echo "<title>{$title}</title>\n";
    echo "<meta name=\"description\" content=\"{$description}\">\n";
    echo "<meta name=\"author\" content=\"" . SITE_NAME . "\">\n";
    echo "<meta name=\"robots\" content=\"{$robots}\">\n";
    echo "<link rel=\"canonical\" href=\"{$canonical}\">\n";
    echo "<meta name=\"theme-color\" content=\"#0d1b2e\">\n";

    /* Geo / local SEO */
    echo "<meta name=\"geo.region\" content=\"UG-102\">\n";
    echo "<meta name=\"geo.placename\" content=\"Kampala, Uganda\">\n";
    echo "<meta name=\"ICBM\" content=\"0.3476, 32.5825\">\n";

    /* Open Graph */
    echo "<meta property=\"og:site_name\" content=\"" . SITE_NAME . "\">\n";
    echo "<meta property=\"og:title\" content=\"{$title}\">\n";
    echo "<meta property=\"og:description\" content=\"{$description}\">\n";
    echo "<meta property=\"og:type\" content=\"{$type}\">\n";
    echo "<meta property=\"og:url\" content=\"{$canonical}\">\n";
    echo "<meta property=\"og:image\" content=\"{$imageAbs}\">\n";
    echo "<meta property=\"og:image:width\" content=\"1200\">\n";
    echo "<meta property=\"og:image:height\" content=\"630\">\n";
    echo "<meta property=\"og:locale\" content=\"en_UG\">\n";

    /* Twitter */
    echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    echo "<meta name=\"twitter:title\" content=\"{$title}\">\n";
    echo "<meta name=\"twitter:description\" content=\"{$description}\">\n";
    echo "<meta name=\"twitter:image\" content=\"{$imageAbs}\">\n";

    echo "<link rel=\"icon\" href=\"assets/images/favicon.png\" type=\"image/png\">\n";
    echo "<link rel=\"apple-touch-icon\" href=\"assets/images/favicon.png\">\n";

    /* Default Organization schema */
    $orgSchema = [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => SITE_NAME,
        'url'      => SITE_URL,
        'logo'     => ska_url('assets/images/ska_logo1.png'),
        'email'    => SITE_EMAIL,
        'telephone'=> ['+256200987770', '+256741186891'],
        'sameAs'   => [
            'https://www.instagram.com/skanaguru/',
        ],
        'address'  => [
            '@type'           => 'PostalAddress',
            'addressLocality' => 'Kampala',
            'addressCountry'  => 'UG',
        ],
    ];

    $schemas = [$orgSchema];
    if (!empty($meta['schema'])) {
        $extra = $meta['schema'];
        $schemas = array_merge($schemas, isset($extra['@type']) ? [$extra] : $extra);
    }

    foreach ($schemas as $schema) {
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
    }
}

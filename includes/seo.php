<?php
/**
 * SEO head renderer for SKA The Boutique
 *
 * Hotel pages stay hotel-focused. Cirqco Systems is credited as the
 * website creator (schema.org creator / creditText), not as a hotel brand.
 *
 * Pass $pageMeta before including page-start.php:
 *   $pageMeta = [
 *     'title'       => 'Page Title',
 *     'description' => '...',
 *     'path'        => 'offers',
 *     'image'       => 'assets/images/...',
 *     'type'        => 'website',
 *     'schema'      => [...],
 *     'noindex'     => false,
 *   ];
 */
function ska_render_seo(array $meta = []): void
{
    require_once __DIR__ . '/../config/site.php';

    $title       = htmlspecialchars($meta['title'] ?? SITE_NAME . ' | ' . SITE_TAGLINE, ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($meta['description'] ?? 'Book direct at SKA The Boutique — refined boutique bed & breakfast in Naguru and Munyonyo, Kampala. Best rates, free breakfast, Wi-Fi and flexible check-in.', ENT_QUOTES, 'UTF-8');
    $path        = trim($meta['path'] ?? '', '/');
    $canonical   = ska_url($path);
    $image       = $meta['image'] ?? 'assets/images/ska_naguru_home.jpeg';
    $imageAbs    = str_starts_with($image, 'http') ? $image : ska_url($image);
    $ogType      = ($meta['type'] ?? 'website') === 'article' ? 'article' : 'website';
    $robots      = !empty($meta['noindex']) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $siteId      = rtrim(SITE_URL, '/') . '/#website';
    $hotelId     = rtrim(SITE_URL, '/') . '/#hotel';
    $pageId      = $canonical . '#webpage';
    $builderId   = ska_builder_org_id();
    $founderId   = ska_builder_founder_id();
    $builderName = htmlspecialchars(BUILDER_NAME, ENT_QUOTES, 'UTF-8');
    $builderUrl  = htmlspecialchars(BUILDER_URL, ENT_QUOTES, 'UTF-8');
    $founderName = htmlspecialchars(BUILDER_FOUNDER, ENT_QUOTES, 'UTF-8');

    echo "<meta charset=\"UTF-8\">\n";
    echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, viewport-fit=cover\">\n";
    echo "<title>{$title}</title>\n";
    echo "<meta name=\"description\" content=\"{$description}\">\n";
    echo "<meta name=\"author\" content=\"{$builderName}\">\n";
    echo "<meta name=\"creator\" content=\"{$builderName}\">\n";
    echo "<meta name=\"designer\" content=\"{$builderName}\">\n";
    echo "<meta name=\"web_author\" content=\"{$founderName}, {$builderName}\">\n";
    echo "<meta name=\"generator\" content=\"{$builderName}\">\n";
    echo "<meta name=\"publisher\" content=\"" . htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "<meta name=\"robots\" content=\"{$robots}\">\n";
    echo "<meta name=\"googlebot\" content=\"{$robots}\">\n";
    echo "<meta name=\"bingbot\" content=\"index, follow\">\n";
    echo "<link rel=\"canonical\" href=\"{$canonical}\">\n";
    echo "<link rel=\"author\" href=\"{$builderUrl}\" title=\"{$builderName}\">\n";
    echo "<link rel=\"author\" type=\"text/plain\" href=\"" . ska_url('humans.txt') . "\">\n";
    echo "<link rel=\"alternate\" hreflang=\"en\" href=\"{$canonical}\">\n";
    echo "<link rel=\"alternate\" hreflang=\"x-default\" href=\"{$canonical}\">\n";
    echo "<meta name=\"theme-color\" content=\"#0d1b2e\">\n";
    echo "<meta name=\"color-scheme\" content=\"dark light\">\n";
    echo "<meta name=\"format-detection\" content=\"telephone=yes\">\n";
    echo "<meta name=\"referrer\" content=\"strict-origin-when-cross-origin\">\n";

    echo "<meta name=\"geo.region\" content=\"UG-102\">\n";
    echo "<meta name=\"geo.placename\" content=\"Kampala, Uganda\">\n";
    echo "<meta name=\"geo.position\" content=\"0.3476;32.5825\">\n";
    echo "<meta name=\"ICBM\" content=\"0.3476, 32.5825\">\n";
    echo "<meta name=\"language\" content=\"English\">\n";

    echo "<meta property=\"og:site_name\" content=\"" . htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "<meta property=\"og:title\" content=\"{$title}\">\n";
    echo "<meta property=\"og:description\" content=\"{$description}\">\n";
    echo "<meta property=\"og:type\" content=\"{$ogType}\">\n";
    echo "<meta property=\"og:url\" content=\"{$canonical}\">\n";
    echo "<meta property=\"og:image\" content=\"{$imageAbs}\">\n";
    echo "<meta property=\"og:image:alt\" content=\"" . htmlspecialchars(SITE_NAME . ' boutique hotel in Kampala, Uganda', ENT_QUOTES, 'UTF-8') . "\">\n";
    echo "<meta property=\"og:image:width\" content=\"1200\">\n";
    echo "<meta property=\"og:image:height\" content=\"630\">\n";
    echo "<meta property=\"og:locale\" content=\"en_UG\">\n";
    echo "<meta property=\"og:see_also\" content=\"{$builderUrl}\">\n";

    echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    echo "<meta name=\"twitter:title\" content=\"{$title}\">\n";
    echo "<meta name=\"twitter:description\" content=\"{$description}\">\n";
    echo "<meta name=\"twitter:image\" content=\"{$imageAbs}\">\n";
    echo "<meta name=\"twitter:image:alt\" content=\"" . htmlspecialchars(SITE_NAME . ' boutique hotel in Kampala', ENT_QUOTES, 'UTF-8') . "\">\n";

    echo "<link rel=\"icon\" href=\"assets/images/favicon.png\" type=\"image/png\">\n";
    echo "<link rel=\"apple-touch-icon\" href=\"assets/images/favicon.png\">\n";
    echo "<link rel=\"manifest\" href=\"" . ska_url('site.webmanifest') . "\">\n";

    $graph = [
        [
            '@type'       => 'Organization',
            '@id'         => $builderId,
            'name'        => BUILDER_NAME,
            'legalName'   => BUILDER_NAME,
            'url'         => BUILDER_URL,
            'description' => BUILDER_DESCRIPTION,
            'founder'     => ['@id' => $founderId],
            'sameAs'      => [
                BUILDER_URL,
                'https://www.linkedin.com/company/cirqco',
            ],
        ],
        [
            '@type'       => 'Person',
            '@id'         => $founderId,
            'name'        => BUILDER_FOUNDER,
            'jobTitle'    => BUILDER_FOUNDER_TITLE,
            'worksFor'    => ['@id' => $builderId],
            'sameAs'      => [
                'https://www.linkedin.com/in/sir-maxwell-odoi-37474495',
            ],
        ],
        [
            '@type'      => 'Hotel',
            '@id'        => $hotelId,
            'name'       => SITE_NAME,
            'alternateName' => 'SKA The Boutique B&B',
            'url'        => SITE_URL,
            'logo'       => ska_url('assets/images/ska_logo1.png'),
            'image'      => ska_url('assets/images/ska_naguru_home.jpeg'),
            'email'      => SITE_EMAIL,
            'telephone'  => ['+256200987770', '+256741186891'],
            'description'=> 'Boutique bed and breakfast with properties in Naguru and Munyonyo, Kampala, Uganda.',
            'sameAs'     => [
                'https://www.instagram.com/skanaguru/',
                'https://www.facebook.com/skaboutiquebnb',
            ],
            'address'    => [
                '@type'           => 'PostalAddress',
                'addressLocality' => 'Kampala',
                'addressCountry'  => 'UG',
            ],
            'geo'        => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => 0.3476,
                'longitude' => 32.5825,
            ],
            'amenityFeature' => [
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Free WiFi', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Free Breakfast', 'value' => true],
                ['@type' => 'LocationFeatureSpecification', 'name' => 'Airport transfers', 'value' => true],
            ],
            'starRating' => ['@type' => 'Rating', 'ratingValue' => '4.5', 'bestRating' => '5'],
            'priceRange' => '$$',
            'checkinTime'  => '14:00',
            'checkoutTime' => '12:00',
            'petsAllowed'  => false,
        ],
        [
            '@type'            => 'WebSite',
            '@id'              => $siteId,
            'url'              => SITE_URL,
            'name'             => SITE_NAME,
            'description'      => 'Official website of SKA The Boutique — boutique hotels in Naguru and Munyonyo, Kampala.',
            'inLanguage'       => 'en-UG',
            'publisher'        => ['@id' => $hotelId],
            'creator'          => ['@id' => $builderId],
            'author'           => ['@id' => $builderId],
            'copyrightHolder'  => ['@id' => $hotelId],
            'sourceOrganization'=> ['@id' => $builderId],
            'creditText'       => BUILDER_CREDIT,
        ],
        [
            '@type'       => 'WebPage',
            '@id'         => $pageId,
            'url'         => $canonical,
            'name'        => $meta['title'] ?? SITE_NAME,
            'description' => $meta['description'] ?? '',
            'isPartOf'    => ['@id' => $siteId],
            'about'       => ['@id' => $hotelId],
            'primaryImageOfPage' => $imageAbs,
            'inLanguage'  => 'en-UG',
            'creator'     => ['@id' => $builderId],
            'author'      => ['@id' => $builderId],
            'publisher'   => ['@id' => $hotelId],
            'creditText'  => BUILDER_CREDIT,
            'breadcrumb'  => [
                '@type'           => 'BreadcrumbList',
                'itemListElement' => ska_seo_breadcrumbs($path, $meta['title'] ?? SITE_NAME, $canonical),
            ],
        ],
    ];

    $payload = [
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ];

    if (!empty($meta['schema'])) {
        $extra = $meta['schema'];
        $items = isset($extra['@type']) ? [$extra] : $extra;
        foreach ($items as $item) {
            unset($item['@context']);
            $payload['@graph'][] = $item;
        }
    }

    echo '<script type="application/ld+json">' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}

function ska_seo_breadcrumbs(string $path, string $title, string $canonical): array
{
    $home = [
        '@type'    => 'ListItem',
        'position' => 1,
        'name'     => 'Home',
        'item'     => SITE_URL,
    ];
    if ($path === '') {
        return [$home];
    }
    return [
        $home,
        [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => $title,
            'item'     => $canonical,
        ],
    ];
}

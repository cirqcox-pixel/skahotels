<?php
/**
 * Opens HTML document — set $pageMeta and optional $pageStyles before include.
 */
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/../config/cms.php';
$pageStyles = $pageStyles ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php ska_render_seo($pageMeta ?? []); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="assets/css/nav.css">
<?php foreach ($pageStyles as $css): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
<?php endforeach; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? 'has-landing-nav') ?>">
<?php if (empty($hideLandingNav)): ?>
<?php include __DIR__ . '/navbar-landing.php'; ?>
<?php endif; ?>

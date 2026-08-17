<?php
/**
 * Admin layout — opening half (head, sidebar, topbar, main content shell).
 *
 * Set before including:
 *   $pageTitle      (required)  — topbar title & <title> suffix
 *   $activePage     (required)  — sidebar highlight key
 *   $pageBreadcrumb (optional)  — subtitle under title
 *   $topbarAction   (optional)  — ['label','href','icon']
 *   $extraCss       (optional)  — array of extra stylesheet URLs
 *   $contentClass   (optional)  — extra classes on .ska-content (e.g. 'ska-content--narrow')
 *   $layoutTitle    (optional)  — full document title override
 */

$pageTitle      = $pageTitle      ?? 'Dashboard';
$activePage     = $activePage     ?? 'dashboard';
$pageBreadcrumb = $pageBreadcrumb ?? '';
$topbarAction   = $topbarAction   ?? null;
$extraCss       = $extraCss       ?? [];
$contentClass   = $contentClass   ?? '';
$docTitle       = $layoutTitle    ?? ('SKA Admin — ' . $pageTitle);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($docTitle) ?></title>
  <link rel="icon" href="../assets/images/favicon.png" type="image/png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/admin.css">
<?php foreach ($extraCss as $cssHref): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars($cssHref) ?>">
<?php endforeach; ?>
</head>
<body>

<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/topbar.php'; ?>

<main class="ska-main">
  <div class="ska-content<?= $contentClass ? ' ' . htmlspecialchars(trim($contentClass)) : '' ?>">

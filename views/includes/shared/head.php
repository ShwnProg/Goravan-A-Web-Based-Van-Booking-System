<?php
/**
 * shared/head.php
 * Included inside <head> on every admin layout.
 * CDN order matters: CSS first, then preconnects.
 */
?>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Leaflet CSS (used on map pages; harmless to load globally) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<!-- App CSS (load order base  layout page) ── -->
<link rel="stylesheet" href="../../assets/css/auth.css">
<link rel="stylesheet" href="../../assets/css/base.css">
<link rel="stylesheet" href="../../assets/css/dashboard.css">
<link rel="stylesheet" href="../../assets/css/vans.css">
<link rel="stylesheet" href="../../assets/css/drivers.css">
<link rel="stylesheet" href="../../assets/css/routes.css">
<link rel="stylesheet" href="../../assets/css/schedules.css">

<!-- <?php if ($page_css): ?>
<link rel="stylesheet" href="<?= $page_css ?>">
<?php endif; ?> -->


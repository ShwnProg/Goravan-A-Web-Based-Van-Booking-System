<!-- Fonts -->
<script>
    (function () {
        if (localStorage.getItem('admin_theme') === 'dark') {
            document.documentElement.classList.add('dark-init');
            document.body.classList.add('admin-dark-mode-active');
        }
    })();
</script>
<style>
    html.dark-init,
    html.dark-init body {
        background: #374151 !important;
    }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
   <link rel="icon" href="../../images/logo_white.png" type="image/png">
<!-- App CSS -->
<link rel="stylesheet" href="../../assets/css/base.css">
<link rel="stylesheet" href="../../assets/css/admin-layout.css">
<link rel="stylesheet" href="../../assets/css/admin-common.css">
<link rel="stylesheet" href="../../assets/css/vans.css">
<link rel="stylesheet" href="../../assets/css/drivers.css">
<link rel="stylesheet" href="../../assets/css/routes.css">
<link rel="stylesheet" href="../../assets/css/schedules.css">
<link rel="stylesheet" href="../../assets/css/dashboard.css">
<link rel="stylesheet" href="../../assets/css/bookings.css">
<link rel="stylesheet" href="../../assets/css/users.css">
<link rel="stylesheet" href="../../assets/css/payments.css">
<link rel="stylesheet" href="../../assets/css/auth.css">
<link rel="stylesheet" href="../../assets/css/style.css">
<!-- <link rel="stylesheet" href="../../assets/css/users.css"> -->


<!-- Dark mode system  must load last so it overrides everything above -->
<link rel="stylesheet" href="../../assets/css/profile.css">

<!-- Page-specific CSS -->
<!-- <?php if (!empty($page_css)): ?>
    <link rel="stylesheet" href="<?= $page_css ?>">
<?php endif; ?> -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
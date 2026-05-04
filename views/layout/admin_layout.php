<?php
require_once '../../autoload.php';

if (empty($_SESSION['is_login'])) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(ucfirst(strtolower($title ?? 'GoraVan'))) ?></title>

    <?php include '../includes/shared/head.php'; ?>

    <?php if (!empty($page_css)): ?>
        <link rel="stylesheet" href="<?= $page_css ?>">
    <?php endif; ?>
<script>
        (function () {
            if (localStorage.getItem('admin_theme') === 'dark') {
                document.documentElement.classList.add('dark-init');
                document.body.classList.add('admin-dark-mode-active');
            }
        })();
    </script>

</head>

<body>

    <!-- Flash messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({ title: 'Success', text: <?= json_encode($_SESSION['success']) ?>, icon: 'success' });
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <?php $firstError = is_array($_SESSION['error']) ? $_SESSION['error'][0] : $_SESSION['error']; ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({ title: 'Error', text: <?= json_encode($firstError) ?>, icon: 'error' });
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- ── Sidebar ────────────────────────────── -->
    <?php include '../includes/admin/sidebar.php'; ?>

    <!-- ── Main ──────────────────────────────── -->
    <main class="main-content">
        <?php include '../includes/admin/topbar.php'; ?>
        <section class="page-content" id="page-content">
            <?= $content ?>
        </section>
    </main>

    <!-- ── Core scripts ───────────────────────── -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../../assets/js/nav.js"></script>

    <!-- ── Page-specific script ───────────────── -->
    <?php if (!empty($page_js)): ?>
        <script src="<?= $page_js ?>"></script>
    <?php endif; ?>

    <script>
        // AUTO-INIT SETTINGS PAGE
        document.addEventListener('DOMContentLoaded', function () {
            if (window.initSettingsPage && document.getElementById('page-content')) {
                window.initSettingsPage();
            }
        });
    </script>

</body>

</html>
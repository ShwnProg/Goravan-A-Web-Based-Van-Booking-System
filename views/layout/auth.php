<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'GoraVan') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>

<body>
    <!-- SUCCESS ALERT -->
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                title: "Success",
                text: "<?= $_SESSION['success'] ?>",
                icon: "success"
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>


    <!-- ERROR ALERT -->
    <?php if (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                title: "Error",
                html: "<?= is_array($_SESSION['error']) ? implode('<br>', $_SESSION['error']) : $_SESSION['error'] ?>",
                icon: "error"
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <main class="auth-page">

        <!-- Left Panel -->
        <div class="left-panel">
            <div class="left-panel__brand">
                <a href="../../index.php"><img src="/images/logo_white.png" alt="GoraVan logo" class="brand-logo"></a>
                <span class="brand-name">Gora<span>Van</span></span>
            </div>

            <div class="left-panel__content">
                <h1><?= $left_headline ?? 'Your seat is<br><em>waiting.</em>' ?></h1>
                <p><?= htmlspecialchars($left_desc ?? '') ?></p>
            </div>

            <!-- FEATURES -->
            <?php if (!empty($left_features)): ?>
                <ul class="left-panel__features">
                    <?php foreach ($left_features as $feature): ?>
                        <li>
                            <span class="feature-icon"><i class="<?= htmlspecialchars($feature['icon']) ?>"></i></span>
                            <div>
                                <strong><?= htmlspecialchars($feature['title']) ?></strong>
                                <span><?= htmlspecialchars($feature['desc']) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <?= $content ?>
        </div>

    </main>
</body>

</html>
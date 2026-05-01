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
    <title><?= ucfirst(strtolower($title)) ?? 'GoraVan' ?></title>
    <?php include '../includes/shared/head.php'; ?>
</head>

<body>
    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                title: "Success",
                text: "<?= $_SESSION['success'] ?>",
                icon: "success",
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>

        <?php
        $errors = $_SESSION['error'];


        $firstError = is_array($errors) ? $errors[0] : $errors;
        ?>

        <script>
            Swal.fire({
                title: "Error",
                text: "<?= htmlspecialchars($firstError) ?>",
                icon: "error"
            });
        </script>

        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>


    <!-- SIDEBAR -->
    <?php include '../includes/admin/sidebar.php'; ?>
    <main class="main-content">
        <?php include '../includes/admin/topbar.php'; ?>
        <section class="page-content" id="page-content">
            <?= $content; ?>
        </section>
    </main>
    <script src="../../assets/js/nav.js"></script>
    <script src="../../assets/js/routes-js.js"></script>


</body>

</html>
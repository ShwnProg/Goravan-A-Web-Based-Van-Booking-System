<?php
require_once "../../autoload.php";
ob_start();
$title = "Login";
?>

<section class="auth-container">
    <h2>Welcome <span>back</span></h2>
    <span class = 'sub-header'>Sign in to your GoraVan account to continue.</span>
</section>

<div class="auth-input">
    <form id="loginForm">
        <?= csrf_field() ?>

        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="example@gmail.com" required>
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
        </div>

        <a href="forgot_password.php" class="forgot-link">Forgot password?</a>

        <button type="submit" class="btn-login">Log In</button>
    </form>

    <div class="auth-divider"></div>

    <p class="auth-footer">
        Don't have an account? <a href="register.php">Create one here</a>
    </p>
</div>

<?php
$content = ob_get_clean();
include "../layout/auth.php";
?>
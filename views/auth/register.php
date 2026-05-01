<?php
require_once '../../autoload.php';
ob_start();
$title = "Create Account";

$left_headline = "Join the<br><em>journey.</em>";
$left_desc = "Create your GoraVan account and unlock hassle free van booking across Southern Leyte.";
$left_features = [
    [
        'icon' => 'fas fa-user-check',
        'title' => 'Quick Sign-Up',
        'desc' => 'Register once and book any trip with just a few taps.',
    ],
    [
        'icon' => 'fas fa-tags',
        'title' => 'Exclusive Discounts',
        'desc' => 'Students, seniors, and PWDs enjoy discounted fares every ride.',
    ],
    [
        'icon' => 'fas fa-shield-halved',
        'title' => 'Secure & Verified',
        'desc' => 'Your data is protected and your booking is always confirmed.',
    ],
];

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>

<section class="auth-container">
    <h2>Create an <span>Account</span></h2>
    <span class='sub-header'>Fill in the details below to register your GoraVan account.</span>
</section>

<div class="auth-input">
    <form action="../../controllers/users/RegisterController.php" method="POST" id="registerForm"
        enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="input-group">
            <label for="fullname">Full Name</label>
            <input type="text" name="fullname" id="fullname" value="<?= htmlspecialchars($old['fullname'] ?? '') ?>"
                placeholder="Enter your full name" Required>
        </div>

        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                placeholder="example@gmail.com" Required>
        </div>

        <div class="input-group">
            <label for="contact">Contact Number</label>
            <input type="text" name="contact" id="contact" value="<?= htmlspecialchars($old['contact'] ?? '') ?>"
                placeholder="1234-567-8912" Required>
        </div>
        <div class="input-group">
            <label for="birthdate">Birthdate</label>
            <input type="date" name="birthdate" id="birthdate" required>
        </div>
        <div class="password-group">
            <div class="pw-field">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Min. 8 characters" Required>
            </div>
            <div class="pw-field">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password"
                    Required>
            </div>
        </div>

        <div class="select-type">
            <label for="type">Passenger Type</label>
            <select name="type" id="type">
                <option value="">Select passenger type</option>

                <option value="regular" <?= ($old['type'] ?? '') == 'regular' ? 'selected' : '' ?>>Regular</option>

                <option value="student" <?= ($old['type'] ?? '') == 'student' ? 'selected' : '' ?>>Student</option>

                <option value="senior" <?= ($old['type'] ?? '') == 'senior' ? 'selected' : '' ?>>Senior Citizen</option>

                <option value="pwd" <?= ($old['type'] ?? '') == 'pwd' ? 'selected' : '' ?>>Person With Disability (PWD)
                </option>
            </select>
        </div>

        <div class="upload">
            <label for="verification">Upload Verification Document <em
                    style="font-style:normal;font-weight:400;color:#94a3b8;">(Required for discounted
                    booking)</em></label>
            <input type="file" name="verification" id="verification" accept=".jpg,.jpeg,.png,.pdf">
        </div>

        <button type="submit" class="btn-register">Create Account</button>
    </form>

    <div class="auth-divider"></div>

    <p class="auth-footer">
        Already have an account? <a href="login.php">Sign in here</a>
    </p>
</div>

<?php
$content = ob_get_clean();
include '../layout/auth.php';
?>
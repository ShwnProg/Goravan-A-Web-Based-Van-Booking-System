<?php
require_once '../../autoload.php';
if (!isset($_SESSION['is_login'])) { header('Location: ../auth/login.php'); exit; }

ob_start();
$title       = 'Profile';
$active_page = 'profile';
$page_css    = '../../assets/css/user-profile.css';
$page_js     = '../../assets/js/user-profile.js';

// Fetch user data
$userId = decrypt($_SESSION['id']);
$um     = new Users($conn);
$um->id = $userId;
$user   = $um->GetUserById();
?>

<!-- PAGE BODY -->
<div class="u-body">
    <!-- Profile Header Card -->
    <div class="u-prof-header">
        <div class="u-prof-avatar">
            <?= strtoupper(substr($user['firstname'] ?? 'U', 0, 1)) . strtoupper(substr($user['lastname'] ?? '', 0, 1)) ?>
        </div>
        <div>
            <h2 class="u-prof-name"><?= htmlspecialchars(ucfirst($user['firstname'] ?? '')) ?> <?= htmlspecialchars(ucfirst($user['lastname'] ?? '')) ?></h2>
            <p class="u-prof-email"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            <div class="u-prof-badge">
                <i class="fa-solid fa-check-circle"></i> Verified Passenger
            </div>
        </div>
    </div>

    <!-- Edit Profile Form -->
    <div class="u-sec">
        <div class="u-sec-head">
            <h2 class="u-sec-title">Edit Profile</h2>
        </div>
        <div class="u-form-card">
            <form action="../../controllers/users/ProfileController.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="u-form-row">
                    <div class="u-form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="first_name"
                               value="<?= htmlspecialchars($user['firstname'] ?? '') ?>" required>
                    </div>
                    <div class="u-form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name"
                               value="<?= htmlspecialchars($user['lastname'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="u-form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                </div>

                <div class="u-form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>"
                           placeholder="09XX XXX XXXX">
                </div>

                <button type="submit" class="u-btn u-btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Change Password Form -->
    <div class="u-sec">
        <div class="u-sec-head">
            <h2 class="u-sec-title">Change Password</h2>
        </div>
        <div class="u-form-card">
            <form action="../../controllers/users/ProfileController.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="u-form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="current_password" required>
                </div>

                <div class="u-form-row">
                    <div class="u-form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="new_password" required
                               minlength="8" placeholder="Minimum 8 characters">
                    </div>
                    <div class="u-form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required
                               minlength="8" placeholder="Re-enter new password">
                    </div>
                </div>

                <button type="submit" class="u-btn u-btn-primary">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Menu List -->
    <div class="u-sec">
        <div class="u-menu-list">
            <a href="#" class="u-menu-item">
                <i class="fa-solid fa-bell"></i>
                <span>Notification Preferences</span>
                <i class="fa-solid fa-chevron-right caret"></i>
            </a>
            <a href="#" class="u-menu-item">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Privacy & Security</span>
                <i class="fa-solid fa-chevron-right caret"></i>
            </a>
            <a href="#" class="u-menu-item">
                <i class="fa-solid fa-circle-question"></i>
                <span>Help & Support</span>
                <i class="fa-solid fa-chevron-right caret"></i>
            </a>
            <div class="u-menu-divider"></div>
            <a href="../../controllers/users/LogoutController.php" class="u-menu-item danger">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Sign Out</span>
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layout/user_layout.php';
?>
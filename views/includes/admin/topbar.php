<?php
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$admin = new Admin($conn);
$admin->id = decrypt($_SESSION['id']);
$info = $admin->Read();
$adminName = $info['fullname'] ?? 'Admin';
$initials = strtoupper(substr($adminName, 0, 1));
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="burger-btn" id="burger-btn" aria-label="Toggle sidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="topbar-title">
            <p class="page-title"><?= htmlspecialchars(strtoupper($title ?? 'Dashboard')) ?></p>
            <p class="topbar-greeting"><?= $greeting ?>, <?= htmlspecialchars($adminName) ?> 👋</p>
        </div>
    </div>

    <div class="topbar-right">
        <button class="topbar-icon-btn" id="notif-toggle" title="Notifications">
            <i class="fas fa-bell"></i>
            <span class="notif-dot" id="notif-dot"></span>
        </button>

        <!-- NOTIFICATION DROPDOWN -->
        <div class="notif-dropdown" id="notif-dropdown">
            <div class="notif-header">
                <p>Notifications</p>
                <span id="mark-all-read">Mark all as read</span>
            </div>
            <div class="notif-list" id="notif-list">
                <!-- hardcoded muna, replace with DB fetch later -->
                <div class="notif-item unread">
                    <div class="notif-icon booking">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="notif-body">
                        <p class="notif-text">New booking request from <b>Juan Dela Cruz</b></p>
                        <span class="notif-time">2 minutes ago</span>
                    </div>
                </div>
                <div class="notif-item unread">
                    <div class="notif-icon payment">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="notif-body">
                        <p class="notif-text">Payment confirmed for booking <b>#BK-0042</b></p>
                        <span class="notif-time">1 hour ago</span>
                    </div>
                </div>
                <div class="notif-item">
                    <div class="notif-icon trip">
                        <i class="fas fa-road"></i>
                    </div>
                    <div class="notif-body">
                        <p class="notif-text">Trip <b>#TR-0021</b> has been completed</p>
                        <span class="notif-time">3 hours ago</span>
                    </div>
                </div>
                <div class="notif-item">
                    <div class="notif-icon user">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="notif-body">
                        <p class="notif-text">New user registered: <b>Maria Santos</b></p>
                        <span class="notif-time">Yesterday</span>
                    </div>
                </div>
            </div>
            <div class="notif-footer">
                <a href="notifications.php">View all notifications</a>
            </div>
        </div>

        <!-- PROFILE DROPDOWN -->
        <div class="topbar-profile" id="profile-toggle">
            <div class="topbar-avatar"><?= $initials ?></div>
            <div class="topbar-profile-info">
                <span class="topbar-name"><?= htmlspecialchars($adminName) ?></span>
                <span class="topbar-role">Administrator</span>
            </div>
            <i class="fas fa-chevron-down topbar-caret" id="profile-caret"></i>

            <!-- DROPDOWN -->
            <div class="profile-dropdown-menu" id="profile-dropdown">
                <div class="dropdown-header">
                    <div class="dropdown-avatar"><?= $initials ?></div>
                    <div>
                        <p class="dropdown-name"><?= htmlspecialchars($adminName) ?></p>
                        <p class="dropdown-role">Administrator</p>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
                <a href="settings.php" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <div class="dropdown-divider"></div>
                <a href="../../controllers/users/LogoutController.php" class="dropdown-item dropdown-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</div>
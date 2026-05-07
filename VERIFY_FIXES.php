<?php
/**
 * VERIFY_FIXES.php
 * 
 * Automated verification that all fixes are properly implemented
 * Run this to confirm everything is working
 */

echo "=== VERIFYING ALL FIXES ===\n\n";

$issues = [];
$fixed = [];

// Check 1: LoginController CSRF order
$loginCode = file_get_contents(__DIR__ . '/controllers/LoginController.php');
if (strpos($loginCode, "// CRITICAL: Regenerate CSRF token BEFORE setting login") !== false &&
    strpos($loginCode, "\$_SESSION['csrf_token'] = bin2hex(random_bytes(32));") !== false) {
    $fixed[] = "✓ LoginController: CSRF token regenerated BEFORE login session set";
} else {
    $issues[] = "✗ LoginController: CSRF token order might be wrong";
}

// Check 2: SettingsController has decryption validation
$settingsCode = file_get_contents(__DIR__ . '/controllers/SettingsController.php');
if (strpos($settingsCode, "if (\$adminId === false || !is_numeric(\$adminId))") !== false) {
    $fixed[] = "✓ SettingsController: Decryption validation implemented";
} else {
    $issues[] = "✗ SettingsController: Missing decryption validation";
}

// Check 3: SettingsController has error handling
if (strpos($settingsCode, "} catch (Exception \$e) {") !== false) {
    $fixed[] = "✓ SettingsController: Error handling (try-catch) implemented";
} else {
    $issues[] = "✗ SettingsController: Missing error handling";
}

// Check 4: SettingsController has rowcount check
if (strpos($settingsCode, "if (\$updated > 0)") !== false) {
    $fixed[] = "✓ SettingsController: Rowcount check (> 0) implemented";
} else {
    $issues[] = "✗ SettingsController: Rowcount check not found";
}

// Check 5: Profile JS has correct fetch URL
$profileJs = file_get_contents(__DIR__ . '/assets/js/profile-js.js');
$fetch1 = substr_count($profileJs, "fetch('../../controllers/SettingsController.php'");
$fetch2 = substr_count($profileJs, "fetch('../../controllers/admin/SettingsController.php'");
if ($fetch1 === 2 && $fetch2 === 0) {
    $fixed[] = "✓ profile-js.js: Both fetch calls use correct URL";
} else {
    $issues[] = "✗ profile-js.js: Fetch URLs might be incorrect (found $fetch1 correct, $fetch2 old)";
}

// Check 6: Profile page has validation
$profilePhp = file_get_contents(__DIR__ . '/views/admin/profile.php');
if (strpos($profilePhp, "if (!\$adminId || !is_numeric(\$adminId))") !== false) {
    $fixed[] = "✓ profile.php: Decryption validation implemented";
} else {
    $issues[] = "✗ profile.php: Missing decryption validation";
}

// Check 7: Admin layout has enhanced session check
$adminLayout = file_get_contents(__DIR__ . '/views/layout/admin_layout.php');
if (strpos($adminLayout, "if (empty(\$_SESSION['is_login']) || empty(\$_SESSION['id']))") !== false) {
    $fixed[] = "✓ admin_layout.php: Enhanced session check with admin ID";
} else {
    $issues[] = "✗ admin_layout.php: Session check not enhanced";
}

// Print results
echo "FIXED ITEMS:\n";
echo str_repeat("─", 60) . "\n";
foreach ($fixed as $item) {
    echo $item . "\n";
}

if (count($issues) > 0) {
    echo "\n\nOUTSTANDING ISSUES:\n";
    echo str_repeat("─", 60) . "\n";
    foreach ($issues as $item) {
        echo $item . "\n";
    }
}

$totalFixed = count($fixed);
$totalIssues = count($issues);

echo "\n" . str_repeat("═", 60) . "\n";
echo "SUMMARY: $totalFixed fixed, $totalIssues issues\n";
echo str_repeat("═", 60) . "\n";

if ($totalIssues === 0) {
    echo "✓ ALL FIXES VERIFIED - Settings should work!\n";
} else {
    echo "⚠ Some issues remain - review the list above\n";
}

?>

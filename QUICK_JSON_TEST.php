<?php
/**
 * QUICK_JSON_TEST.php
 * 
 * Super simple test - just calls controller and shows raw response
 */

require_once 'autoload.php';

if (empty($_SESSION['is_login'])) {
    die('Not logged in');
}

header('Content-Type: text/plain');

echo "=== QUICK CONTROLLER TEST ===\n\n";

// Test 1: Check session
echo "1. SESSION STATE:\n";
var_dump($_SESSION);

echo "\n2. CSRF TOKEN:\n";
echo $_SESSION['csrf_token'] . "\n";

echo "\n3. ADMIN ID:\n";
$adminId = decrypt($_SESSION['id']);
echo "Decrypted ID: $adminId\n";
echo "Type: " . gettype($adminId) . "\n";
echo "Is numeric: " . (is_numeric($adminId) ? 'YES' : 'NO') . "\n";

echo "\n4. DATABASE QUERY:\n";
$stmt = $conn->prepare("SELECT user_id_pk, fullname, email, role FROM users WHERE user_id_pk = :id AND role = 'admin'");
$stmt->execute([':id' => (int)$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
var_dump($admin);

echo "\n5. TEST JSON RESPONSE:\n";
// Simulate what controller would do
$testResponse = [
    'success' => true,
    'message' => 'Test successful',
    'admin_id' => (int)$adminId,
    'admin_email' => $admin['email'] ?? null
];
echo json_encode($testResponse, JSON_PRETTY_PRINT) . "\n";

echo "\n6. NOW TEST ACTUAL CONTROLLER:\n";
echo "POST to /controllers/SettingsController.php with:\n";
$testPayload = [
    'action' => 'update_profile',
    'fullname' => 'Test Name',
    'email' => 'admin@gmail.com',
    'contact_number' => '09123456789',
    'csrf_token' => $_SESSION['csrf_token']
];
echo json_encode($testPayload, JSON_PRETTY_PRINT) . "\n";

?>

<?php
/**
 * TEST_SETTINGS_DEBUG.php
 * 
 * Debug script to test settings controller flow
 * Access at: http://localhost:8000/TEST_SETTINGS_DEBUG.php
 */

require_once 'autoload.php';

echo "<h1>Settings Controller Debug Test</h1>";
echo "<hr>";

// 1. Check session
echo "<h2>1. Session Check</h2>";
echo "<pre>";
var_dump([
    'is_login' => $_SESSION['is_login'] ?? false,
    'id_exists' => !empty($_SESSION['id']),
    'csrf_token_exists' => !empty($_SESSION['csrf_token']),
    'csrf_token' => substr($_SESSION['csrf_token'] ?? '', 0, 20) . '...',
]);
echo "</pre>";

// 2. Check decryption
echo "<h2>2. Decryption Check</h2>";
if (!empty($_SESSION['id'])) {
    $adminId = decrypt($_SESSION['id']);
    echo "<pre>";
    var_dump([
        'encrypted' => substr($_SESSION['id'], 0, 30) . '...',
        'decrypted' => $adminId,
        'valid_numeric' => is_numeric($adminId),
        'type' => gettype($adminId),
    ]);
    echo "</pre>";
    
    // 3. Check database query
    echo "<h2>3. Database Query Check</h2>";
    if (is_numeric($adminId)) {
        $stmt = $conn->prepare("SELECT user_id_pk, fullname, email, role FROM users WHERE user_id_pk = :id AND role = 'admin'");
        $stmt->execute([':id' => (int)$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        var_dump($admin);
        echo "</pre>";
    }
} else {
    echo "<p style='color: red;'>No admin session ID found. Please login first.</p>";
}

// 4. Test JSON response headers
echo "<h2>4. Testing JSON Endpoint</h2>";
echo "<p>Use the form below to test settings update:</p>";
?>

<form id="test-form" style="border: 1px solid #ccc; padding: 20px; margin: 20px 0;">
    <h3>Test Profile Update</h3>
    
    <div>
        <label>Full Name:</label><br>
        <input type="text" id="fullname" value="Test Admin" style="width: 100%; padding: 8px;">
    </div>
    
    <div style="margin-top: 10px;">
        <label>Email:</label><br>
        <input type="email" id="email" value="admin@test.com" style="width: 100%; padding: 8px;">
    </div>
    
    <div style="margin-top: 10px;">
        <label>Contact:</label><br>
        <input type="text" id="contact" value="09123456789" style="width: 100%; padding: 8px;">
    </div>
    
    <div style="margin-top: 20px;">
        <button type="button" onclick="testUpdate()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
            Test Update
        </button>
    </div>
    
    <div id="response" style="margin-top: 20px; padding: 10px; background: #f5f5f5; display: none;"></div>
</form>

<script>
function testUpdate() {
    const csrf = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    const data = {
        action: 'update_profile',
        fullname: document.getElementById('fullname').value,
        email: document.getElementById('email').value,
        contact_number: document.getElementById('contact').value,
        csrf_token: csrf
    };
    
    console.log('Sending data:', data);
    
    // Use correct path from root
    fetch('/controllers/SettingsController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => {
        console.log('Response status:', r.status);
        console.log('Response headers:', r.headers);
        return r.text();
    })
    .then(text => {
        console.log('Raw response:', text);
        const responseDiv = document.getElementById('response');
        responseDiv.style.display = 'block';
        responseDiv.innerHTML = '<strong>Response:</strong><pre>' + text + '</pre>';
        
        try {
            const json = JSON.parse(text);
            responseDiv.innerHTML += '<strong>Parsed JSON:</strong><pre>' + JSON.stringify(json, null, 2) + '</pre>';
        } catch (e) {
            responseDiv.innerHTML += '<strong style="color: red;">Failed to parse JSON</strong>';
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        const responseDiv = document.getElementById('response');
        responseDiv.style.display = 'block';
        responseDiv.innerHTML = '<strong style="color: red;">Fetch Error:</strong><pre>' + err.message + '</pre>';
    });
}
</script>

</body>
</html>

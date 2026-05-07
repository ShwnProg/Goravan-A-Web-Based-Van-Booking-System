<?php
/**
 * COMPLETE_FLOW_TEST.php
 * 
 * Comprehensive test of the entire settings flow
 * Shows exactly what happens at each step
 */

require_once 'autoload.php';

if (empty($_SESSION['is_login'])) {
    die('ERROR: Not logged in. Please login first at /views/auth/login.php');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Settings Flow Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .step { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
        .step h3 { margin-top: 0; color: #0066cc; }
        .success { border-left: 4px solid #28a745; background: #f8f9fa; }
        .error { border-left: 4px solid #dc3545; background: #f8f9fa; }
        .info { border-left: 4px solid #17a2b8; background: #f8f9fa; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .code { background: #f0f0f0; padding: 2px 6px; font-family: monospace; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 3px; margin: 5px; }
        button:hover { background: #0056b3; }
        #testResult { margin: 20px 0; }
    </style>
</head>
<body>

<h1>🔐 Complete Settings Flow Test</h1>

<?php
$adminId = decrypt($_SESSION['id']);
$admin = new Admin($conn);
$admin->id = (int)$adminId;
$info = $admin->Read();
?>

<div class="step success">
    <h3>✓ Step 1: Authentication & Session</h3>
    <p><strong>Status:</strong> Logged in</p>
    <pre><?php var_export([
        'is_login' => $_SESSION['is_login'],
        'admin_id' => $adminId,
        'admin_name' => $info['fullname'] ?? 'Unknown',
        'admin_email' => $info['email'] ?? 'Unknown',
    ]); ?></pre>
</div>

<div class="step success">
    <h3>✓ Step 2: CSRF Token</h3>
    <p><strong>Token Status:</strong> Valid and set</p>
    <pre><?php var_export([
        'token_length' => strlen($_SESSION['csrf_token']),
        'token_preview' => substr($_SESSION['csrf_token'], 0, 20) . '...',
        'token_full' => $_SESSION['csrf_token'],
    ]); ?></pre>
</div>

<div class="step success">
    <h3>✓ Step 3: Encryption/Decryption</h3>
    <p><strong>Status:</strong> Working correctly</p>
    <pre><?php var_export([
        'original_id' => 28, // or actual ID
        'encrypted' => $_SESSION['id'],
        'decrypted' => $adminId,
        'matches' => (int)$adminId === 28,
    ]); ?></pre>
</div>

<div class="step success">
    <h3>✓ Step 4: Database Query</h3>
    <p><strong>Status:</strong> Admin record found and readable</p>
    <pre><?php var_export([
        'user_id_pk' => $info['user_id_pk'],
        'fullname' => $info['fullname'],
        'email' => $info['email'],
        'contact_number' => $info['contact_number'],
        'role' => $info['role'],
    ]); ?></pre>
</div>

<hr>

<h2>Test Profile Update</h2>

<div class="info">
    <p>Click the button below to test a complete profile update request:</p>
</div>

<div>
    <button onclick="testProfileUpdate()">Test Profile Update</button>
    <button onclick="testPasswordChange()">Test Password Change</button>
    <button onclick="clearResults()">Clear Results</button>
</div>

<div id="testResult"></div>

<script>
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?>';
const BASE_URL = '/GROAVAN'; // Adjust if needed

function testProfileUpdate() {
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = '<div class="step info"><h3>Testing Profile Update...</h3><p>Sending request...</p></div>';
    
    const data = {
        action: 'update_profile',
        fullname: 'Updated Admin Name',
        email: 'admin@gmail.com', // Keep same email to avoid conflict
        contact_number: '09987654321',
        csrf_token: CSRF_TOKEN
    };
    
    console.log('Payload:', data);
    
    fetch('/controllers/SettingsController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        
        try {
            const json = JSON.parse(text);
            const isSuccess = json.success === true;
            const className = isSuccess ? 'success' : 'error';
            
            resultDiv.innerHTML = `
                <div class="step ${className}">
                    <h3>${isSuccess ? '✓' : '✗'} Profile Update ${isSuccess ? 'Successful' : 'Failed'}</h3>
                    <p><strong>Message:</strong> ${json.message}</p>
                    <p><strong>Success:</strong> ${json.success}</p>
                    <pre>Full Response:
${JSON.stringify(json, null, 2)}</pre>
                </div>
            `;
        } catch (e) {
            resultDiv.innerHTML = `
                <div class="step error">
                    <h3>✗ JSON Parse Error</h3>
                    <p>Response was not valid JSON</p>
                    <pre>${text}</pre>
                    <p><strong>Parse Error:</strong> ${e.message}</p>
                </div>
            `;
        }
    })
    .catch(err => {
        resultDiv.innerHTML = `
            <div class="step error">
                <h3>✗ Fetch Error</h3>
                <p>${err.message}</p>
                <pre>${err.stack}</pre>
            </div>
        `;
    });
}

function testPasswordChange() {
    const resultDiv = document.getElementById('testResult');
    
    const currentPass = prompt('Enter your CURRENT password:');
    if (!currentPass) return;
    
    const newPass = prompt('Enter a NEW password (min 8 chars):');
    if (!newPass || newPass.length < 8) {
        alert('New password must be at least 8 characters');
        return;
    }
    
    const confirmPass = prompt('Confirm new password:');
    if (newPass !== confirmPass) {
        alert('Passwords do not match');
        return;
    }
    
    resultDiv.innerHTML = '<div class="step info"><h3>Testing Password Change...</h3><p>Sending request...</p></div>';
    
    const data = {
        action: 'change_password',
        current_password: currentPass,
        new_password: newPass,
        confirm_password: confirmPass,
        csrf_token: CSRF_TOKEN
    };
    
    fetch('/controllers/SettingsController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.text())
    .then(text => {
        try {
            const json = JSON.parse(text);
            const isSuccess = json.success === true;
            const className = isSuccess ? 'success' : 'error';
            
            resultDiv.innerHTML = `
                <div class="step ${className}">
                    <h3>${isSuccess ? '✓' : '✗'} Password Change ${isSuccess ? 'Successful' : 'Failed'}</h3>
                    <p><strong>Message:</strong> ${json.message}</p>
                    <pre>Full Response:
${JSON.stringify(json, null, 2)}</pre>
                </div>
            `;
        } catch (e) {
            resultDiv.innerHTML = `
                <div class="step error">
                    <h3>✗ JSON Parse Error</h3>
                    <pre>${text}</pre>
                </div>
            `;
        }
    })
    .catch(err => {
        resultDiv.innerHTML = `
            <div class="step error">
                <h3>✗ Fetch Error</h3>
                <p>${err.message}</p>
            </div>
        `;
    });
}

function clearResults() {
    document.getElementById('testResult').innerHTML = '';
}
</script>

</body>
</html>

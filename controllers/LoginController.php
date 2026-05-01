<?php
require_once '../autoload.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check()) {
        $_SESSION['error'] = 'Invalid CSRF';
        header("Location: ../views/auth/login.php");
        exit;
    }
    $user = new Users($conn);

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $user->email = $email;
    $user->password = $password;

    // $result = $user->AuthenticateUser();

    $role = $user->GetRole();

    if ($role === 'user') {
        $result = $user->AuthenticateUser();

        if ($result === true) {
            $_SESSION['is_login'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: ../views/user/index.php");
            exit;
        } else {
            $_SESSION['error'] = $result;
        }
    } else {
        $admin = new Admin($conn);
        $admin->email = $email;
        $admin->password = $password;

        $result = $admin->AuthenticateAdmin();

        if ($result) {
            $_SESSION['is_login'] = true;
            $_SESSION['id'] = encrypt((string) $result);
            $_SESSION['success'] = 'Login Successfully';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: ../views/admin/index.php");
            exit;
        } else {
            $_SESSION['error'] = 'Invalid Credentials';
        }
    }

    header("Location: ../views/auth/login.php");
    exit;
}
?>
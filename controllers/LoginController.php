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

    // $result = $user->AuthenticateUser();

    $role = $user->GetRole();

    if ($role === 'user') {
        $user->password = $password;

        $result = $user->AuthenticateUser();

        if ($result['is_login']) {
            $_SESSION['id'] = encrypt((string) $result['id']);
            $_SESSION['is_login'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: ../views/users/index.php");
            exit;
        } else {
            $_SESSION['error'] = $result['error'];
        }

    } else if ($role === 'admin') {
        $admin = new Admin($conn);
        $admin->email = $email;
        $admin->password = $password;

        $result = $admin->AuthenticateAdmin();

        if ($result['is_login']) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['is_login'] = true;
            $_SESSION['id'] = encrypt((string) $result['id']);
            $_SESSION['success'] = 'Login Successfully';
            header("Location: ../views/admin/index.php");
            exit;
        } else {
            $_SESSION['error'] = $result['error'];
        }
    } else {
        $_SESSION['error'] = 'No account found with that email.';
    }
    // var_dump($role);
    // var_dump($result);



    header("Location: ../views/auth/login.php");
    exit;
}
?>
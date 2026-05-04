<?php
require_once '../../autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check()) {
        $_SESSION['error'] = 'Invalid CSRF';
        header("Location: ../../views/auth/register.php");
        exit;
    }

    $user = new Users($conn);
    $errors = [];
    $is_created = false;

    // INPUTSW
    $fullname = ucwords(trim($_POST['fullname'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $type = trim($_POST['type'] ?? '');

    // FILE UPLOAD 
    $filename = null;

    if (!empty($_FILES['verification']['name'])) {

        $file = $_FILES['verification'];

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        // safer + unique filename
        $filename = time() . "_" . bin2hex(random_bytes(5)) . "." . $ext;

        // optional: sanitize extension
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array(strtolower($ext), $allowed)) {
            $errors[] = "Invalid file type";
        }

        $target = "../../uploads/documents/" . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $errors[] = "File upload failed";
        }
    }
    // EMAIL
    $clean_email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $validate_email = filter_var($clean_email, FILTER_VALIDATE_EMAIL);

    if ($validate_email === false) {
        $errors[] = 'Please enter a valid email';
    }

    if ($user->IsDuplicateEmail($email)) {
        $errors[] = 'Email is already registered';
    }

    // CONTACT
    $contact = preg_replace('/[^0-9]/', '', $contact);

    if (strlen($contact) != 11) {
        $errors[] = "Please enter a valid 11-digit contact number";
    }

    // BIRTHDATE

    //CALCULATE AGE FROM BIRTHDATE
    $age = date('Y') - date('Y', strtotime($birthdate));

    if ($type == 'senior' && $age < 60) {
        $errors[] = "You must be 60+ to register as Senior";
    }
    if ($birthdate > date('Y-m-d')) {
        $errors[] = "Birthdate cannot be in the future";
    }
    if ($age <= 0) {
        $errors[] = "Invalid birthdate";
    }

    // PASSWORD
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    if ($confirm_password !== $password) {
        $errors[] = 'Password do not match';
    }

    // TYPE
    if (empty($type)) {
        $errors[] = 'Passenger type is required.';
    }

    // DOCUMENT CHECK
    if ($type !== 'regular' && empty($filename)) {
        $errors[] = 'Please upload the required document for non-regular passengers.';
    }

    if (!empty($errors)) {

        $_SESSION['error'] = $errors;

    } else {

        // USER INSERT
        $user->fullname = htmlspecialchars($fullname);
        $user->email = $email;
        $user->contact = $contact;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->birthdate = $birthdate;

        $user_id = $user->AddUser();

        if ($user_id) {

            // VERIFICATION INSERT
            if ($type !== 'regular') {

                $verification = new Verification($conn);
                $verification->user_id_fk = $user_id;
                $verification->type = $type;
                $verification->document = $filename;
                $verification->status = "pending";

                $result = $verification->AddDocuments();

                if ($result === true) {
                    $is_created = true;
                } else {
                    $_SESSION['error'] = 'Failed to save verification';
                }

            } else {
                $is_created = true;
            }

        } else {
            $_SESSION['error'] = 'Failed to create account';
        }
    }

    // SUCCESS
    if ($is_created) {
        $_SESSION['success'] = 'Account Created Successfully';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));


        header("Location: ../../views/auth/login.php");
        exit;
    }

    // OLD INPUTS
    $_SESSION['old'] = [
        'fullname' => $fullname,
        'email' => $email,
        'contact' => $contact,
        'type' => $type
    ];

    header("Location: ../../views/auth/register.php");
    exit;
}
?>
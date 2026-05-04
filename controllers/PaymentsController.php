<?php
require_once '../../autoload.php';

header('Content-Type: application/json');

if (empty($_SESSION['is_login'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? 'list';

/* ── LIST ─────────────────────────────────────── */
if ($action === 'list') {
    $payObj   = new Payments($conn);
    $payments = $payObj->GetAllPayments();

    $payments = array_map(function ($p) {
        $p['payment_id_pk'] = encrypt((string) $p['payment_id_pk']);
        return $p;
    }, $payments);

    echo json_encode(['success' => true, 'data' => $payments]);
    exit;
}

/* ── GET SINGLE ───────────────────────────────── */
if ($action === 'get') {
    $payment_id = (int) decrypt(trim($_GET['payment_id'] ?? ''));
    if (!$payment_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment ID.']);
        exit;
    }

    $payObj     = new Payments($conn);
    $payObj->id = $payment_id;
    $payment    = $payObj->GetPaymentByID();

    if (empty($payment)) {
        echo json_encode(['success' => false, 'message' => 'Payment not found.']);
        exit;
    }

    $payment['payment_id_pk'] = encrypt((string) $payment['payment_id_pk']);

    echo json_encode(['success' => true, 'data' => $payment]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
<?php
require_once '../../autoload.php';

header('Content-Type: application/json');

if (empty($_SESSION['is_login'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$action = $_GET['action'] ?? 'list';
$sessionUserId = (int) decrypt($_SESSION['id']);
$requestedUser = (int) decrypt(trim($_GET['user_id'] ?? ''));

if ($requestedUser && $requestedUser !== $sessionUserId) {
    echo json_encode(['success' => false, 'message' => 'You can only view your own payments.']);
    exit;
}

if ($action !== 'list') {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

try {
    $payObj = new Payments($conn);
    $payments = $payObj->GetPaymentsByUser($sessionUserId);

    $data = array_map(function ($row) {
        return [
            'payment_id' => encrypt((string) $row['payment_id_pk']),
            'booking_id' => encrypt((string) $row['book_id_pk']),
            'reference_code' => $row['reference_code'],
            'route_display' => $row['route_display'],
            'origin' => $row['origin'],
            'destination' => $row['destination'],
            'departure_date' => $row['departure_date'],
            'departure_time' => $row['departure_time'],
            'seats_count' => $row['seats_count'],
            'seat_numbers' => $row['seat_numbers'],
            'passenger_name' => $row['passenger_name'],
            'contact_number' => $row['contact_number'],
            'passenger_type' => $row['passenger_type'],
            'passengers' => $row['passengers'],
            'discount_rate' => $row['discount_rate'],
            'discount_amount' => $row['discount_amount'],
            'convenience_fee' => $row['convenience_fee'],
            'amount' => $row['amount'],
            'payment_method' => $row['payment_method'],
            'payment_reference' => $row['payment_reference'],
            'status' => $row['status'],
            'paid_at' => $row['paid_at'],
            'created_at' => $row['created_at'],
        ];
    }, $payments);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    error_log('[UserPaymentController] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to load payments.']);
}

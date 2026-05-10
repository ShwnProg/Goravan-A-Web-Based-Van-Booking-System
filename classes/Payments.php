<?php
class Payments
{
    private $conn;
    private $table = 'payments';

    public $id;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetAllPayments(): array
    {
        $this->SyncPaidPayments();

        $stmt = $this->conn->prepare("
            SELECT
                p.payment_id_pk,
                p.amount,
                p.payment_method,
                p.payment_reference,
                p.status,
                p.paid_at,
                p.created_at,
                p.notes,
                b.reference_code                                                         AS booking_ref,
                CONCAT(u.firstname, ' ', u.lastname)                                    AS user_name,
                u.email                                                                  AS user_email,
                u.contact_number                                                         AS user_phone,
                CONCAT(COALESCE(r.origin, 'N/A'), ' → ', COALESCE(r.destination, 'N/A')) AS route_display,
                s.departure_date
            FROM {$this->table} p
            LEFT JOIN bookings  b ON p.book_id_fk      = b.book_id_pk
            LEFT JOIN users     u ON b.user_id_fk       = u.user_id_pk
            LEFT JOIN schedules s ON b.schedule_id_fk   = s.schedule_id_pk
            LEFT JOIN routes    r ON s.route_id_fk      = r.route_id_pk
            ORDER BY p.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function GetPaymentByID(): array
    {
        if (!$this->id) return [];

        $stmt = $this->conn->prepare("
            SELECT
                p.payment_id_pk,
                p.amount,
                p.payment_method,
                p.payment_reference,
                p.status,
                p.paid_at,
                p.created_at,
                p.notes,
                b.reference_code                                                         AS booking_ref,
                CONCAT(u.firstname, ' ', u.lastname)                                    AS user_name,
                u.email                                                                  AS user_email,
                u.contact_number                                                         AS user_phone,
                CONCAT(COALESCE(r.origin, 'N/A'), ' → ', COALESCE(r.destination, 'N/A')) AS route_display,
                s.departure_date,
                s.departure_time
            FROM {$this->table} p
            LEFT JOIN bookings  b ON p.book_id_fk      = b.book_id_pk
            LEFT JOIN users     u ON b.user_id_fk       = u.user_id_pk
            LEFT JOIN schedules s ON b.schedule_id_fk   = s.schedule_id_pk
            LEFT JOIN routes    r ON s.route_id_fk      = r.route_id_pk
            WHERE p.payment_id_pk = :id
        ");
        $stmt->execute([':id' => $this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function GetPaymentsByUser(int $userId): array
    {
        $this->SyncPaidPayments();

        $stmt = $this->conn->prepare("
            SELECT
                p.payment_id_pk,
                p.amount,
                p.payment_method,
                p.payment_reference,
                p.status,
                p.paid_at,
                p.created_at,
                p.notes,
                b.reference_code,
                b.book_id_pk,
                CONCAT(r.origin, ' -> ', r.destination) AS route_display,
                r.origin,
                r.destination,
                s.departure_date,
                s.departure_time,
                COALESCE(bg.seats_count, 1) AS seats_count,
                COALESCE(bg.seat_numbers, seats.seat_number) AS seat_numbers
            FROM {$this->table} p
            INNER JOIN bookings b ON p.book_id_fk = b.book_id_pk
            INNER JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            INNER JOIN routes r ON s.route_id_fk = r.route_id_pk
            LEFT JOIN seats ON b.seat_id_fk = seats.seat_id_pk
            LEFT JOIN (
                SELECT
                    b2.reference_code,
                    COUNT(*) AS seats_count,
                    GROUP_CONCAT(seats2.seat_number ORDER BY seats2.seat_row ASC, seats2.seat_col ASC SEPARATOR ', ') AS seat_numbers
                FROM bookings b2
                LEFT JOIN seats seats2 ON b2.seat_id_fk = seats2.seat_id_pk
                WHERE b2.user_id_fk = :user_id_group
                GROUP BY b2.reference_code
            ) bg ON bg.reference_code = b.reference_code
            WHERE b.user_id_fk = :user_id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([
            ':user_id_group' => $userId,
            ':user_id' => $userId,
        ]);

        return array_map([$this, 'normalizeUserPayment'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function normalizeUserPayment(array $row): array
    {
        $notes = [];
        if (!empty($row['notes'])) {
            $decoded = json_decode($row['notes'], true);
            if (is_array($decoded)) {
                $notes = $decoded;
            }
        }

        return [
            'payment_id_pk' => (int) $row['payment_id_pk'],
            'book_id_pk' => (int) $row['book_id_pk'],
            'reference_code' => $row['reference_code'],
            'route_display' => $row['route_display'],
            'origin' => $row['origin'],
            'destination' => $row['destination'],
            'departure_date' => $row['departure_date'],
            'departure_time' => $row['departure_time'],
            'seats_count' => (int) ($notes['seats_count'] ?? $row['seats_count'] ?? 1),
            'seat_numbers' => $notes['seat_numbers'] ?? $row['seat_numbers'],
            'passenger_name' => $notes['passenger_name'] ?? '',
            'contact_number' => $notes['contact_number'] ?? '',
            'passenger_type' => $notes['passenger_type'] ?? 'regular',
            'passengers' => $notes['passengers'] ?? [],
            'discount_rate' => (float) ($notes['discount_rate'] ?? 0),
            'discount_amount' => (float) ($notes['discount_amount'] ?? 0),
            'convenience_fee' => (float) ($notes['convenience_fee'] ?? 0),
            'amount' => (float) $row['amount'],
            'payment_method' => $row['payment_method'],
            'payment_reference' => $row['payment_reference'],
            'status' => $row['status'],
            'paid_at' => $row['paid_at'],
            'created_at' => $row['created_at'],
        ];
    }

    private function SyncPaidPayments(): void
    {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET status = 'paid',
                paid_at = COALESCE(paid_at, created_at, NOW())
            WHERE status IN ('pending', 'unpaid')
        ");
        $stmt->execute();
    }

    public function UpdateStatus(string $status): array
    {
        if (!$this->id || !in_array($status, ['pending', 'paid', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Invalid payment status.'];
        }

        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                SELECT p.book_id_fk, b.reference_code
                FROM {$this->table} p
                LEFT JOIN bookings b ON p.book_id_fk = b.book_id_pk
                WHERE p.payment_id_pk = :id
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([':id' => $this->id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Payment not found.'];
            }

            $update = $this->conn->prepare("
                UPDATE {$this->table}
                SET status = :status,
                    paid_at = CASE
                        WHEN :paid_status = 'paid' THEN COALESCE(paid_at, NOW())
                        WHEN :clear_status = 'pending' THEN NULL
                        ELSE paid_at
                    END
                WHERE payment_id_pk = :id
            ");
            $update->execute([
                ':status' => $status,
                ':paid_status' => $status,
                ':clear_status' => $status,
                ':id' => $this->id,
            ]);

            if ($status === 'cancelled' && !empty($payment['reference_code'])) {
                $cancel = $this->conn->prepare("
                    UPDATE bookings
                    SET status = 'cancelled',
                        updated_at = NOW()
                    WHERE reference_code = :reference_code
                      AND status NOT IN ('completed', 'cancelled', 'rejected')
                ");
                $cancel->execute([':reference_code' => $payment['reference_code']]);
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

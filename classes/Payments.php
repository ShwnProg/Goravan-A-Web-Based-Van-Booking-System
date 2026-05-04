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
                u.fullname                                                               AS user_name,
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
                u.fullname                                                               AS user_name,
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
}
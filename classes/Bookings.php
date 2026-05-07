<?php
class Bookings
{
    private $conn = null;
    private $table = "bookings";

    public $id;
    public $user_id;
    public $schedule_id;
    public $seat_id;
    public $reference_code;
    public $status;
    public $payment_deadline;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ── READ ──────────────────────────────────────────────────────

    public function GetAllBookings(): array
    {
        $stmt = $this->conn->prepare("
            SELECT 
                b.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                u.email as user_email,
                u.contact_number as user_phone,
                CONCAT(r.origin, ' → ', r.destination) as route_display,
                s.departure_date,
                s.departure_time,
                s.trip_status as schedule_status,
                d.full_name as driver_name,
                v.plate_number as van_plate,
                v.model as van_model,
                v.capacity as van_capacity,
                seats.seat_number
            FROM {$this->table} b
            LEFT JOIN users u ON b.user_id_fk = u.user_id_pk
            LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            LEFT JOIN drivers d ON s.driver_id_fk = d.driver_id_pk
            LEFT JOIN vans v ON s.van_id_fk = v.van_id_pk
            LEFT JOIN seats ON b.seat_id_fk = seats.seat_id_pk
            ORDER BY b.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function GetBookingByID(): array
    {
        $stmt = $this->conn->prepare("
            SELECT 
                b.*,
                CONCAT(u.firstname, ' ', u.lastname) as user_name,
                u.email as user_email,
                u.contact_number as user_phone,
                CONCAT(r.origin, ' → ', r.destination) as route_display,
                r.fare as route_fare,
                s.departure_date,
                s.departure_time,
                s.trip_status as schedule_status,
                s.arrived_at,
                d.full_name as driver_name,
                v.plate_number as van_plate,
                v.model as van_model,
                v.capacity as van_capacity,
                seats.seat_number,
                seats.seat_row,
                seats.seat_col
            FROM {$this->table} b
            LEFT JOIN users u ON b.user_id_fk = u.user_id_pk
            LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            LEFT JOIN drivers d ON s.driver_id_fk = d.driver_id_pk
            LEFT JOIN vans v ON s.van_id_fk = v.van_id_pk
            LEFT JOIN seats ON b.seat_id_fk = seats.seat_id_pk
            WHERE b.book_id_pk = :id
        ");
        $stmt->execute([':id' => $this->id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ?: [];
    }

    public function IsSeatAlreadyBooked(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT book_id_pk FROM {$this->table}
            WHERE seat_id_fk = :seat_id
              AND schedule_id_fk = :schedule_id
              AND status NOT IN ('rejected', 'cancelled')
              AND book_id_pk != :id
        ");
        $stmt->execute([
            ':seat_id' => $this->seat_id,
            ':schedule_id' => $this->schedule_id,
            ':id' => $this->id ?: 0
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function IsReferenceCodeExist(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT book_id_pk FROM {$this->table}
            WHERE reference_code = :code
        ");
        $stmt->execute([':code' => $this->reference_code]);
        return (bool) $stmt->fetchColumn();
    }

    // CREATE 

    public function AddBooking(): array
    {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table}
                    (user_id_fk, schedule_id_fk, seat_id_fk, reference_code, status, payment_deadline)
                VALUES
                    (:user_id, :schedule_id, :seat_id, :ref_code, :status, :deadline)
            ");
            $stmt->execute([
                ':user_id' => $this->user_id,
                ':schedule_id' => $this->schedule_id,
                ':seat_id' => $this->seat_id,
                ':ref_code' => $this->reference_code,
                ':status' => $this->status,
                ':deadline' => $this->payment_deadline
            ]);

            return ['success' => true, 'id' => (int) $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── UPDATE STATUS ─────────────────────────────────────────────

    public function UpdateStatus(): array
    {
        try {
            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET status = :status, updated_at = NOW()
                WHERE book_id_pk = :id
            ");
            $stmt->execute([
                ':status' => $this->status,
                ':id' => $this->id
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── DELETE ────────────────────────────────────────────────────

    public function DeleteBooking(): array
    {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM {$this->table} WHERE book_id_pk = :id
            ");
            $stmt->execute([':id' => $this->id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── HELPER: Get all statuses ──────────────────────────────────

    public static function GetAllStatuses(): array
    {
        return ['pending', 'approved', 'rejected', 'cancelled'];
    }

    public static function GetStatusColor(string $status): string
    {
        $colors = [
            'pending' => '#f97316',
            'approved' => '#16a34a',
            'rejected' => '#ef4444',
            'cancelled' => '#6b7280'
        ];
        return $colors[$status] ?? '#9ca3af';
    }

    public static function IsPaymentExpired(string $deadline): bool
    {
        return strtotime($deadline) < time();
    }

    public function GetUpcomingTripByUser()
    {
        $stmt = $this->conn->prepare("
            SELECT 
                b.*,
                CONCAT(r.origin, ' → ', r.destination) as route_display,
                s.departure_date,
                s.departure_time,
                s.trip_status as schedule_status
            FROM {$this->table} b
            LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            WHERE b.user_id_fk = :user_id
              AND b.status = 'approved'
              AND CONCAT(s.departure_date, ' ', s.departure_time) > NOW()
            ORDER BY CONCAT(s.departure_date, ' ', s.departure_time) ASC
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function GetRecentBookingsByUser($limit = 3)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                b.*,
                CONCAT(r.origin, ' → ', r.destination) as route_display,
                s.departure_date,
                s.departure_time,
                s.trip_status as schedule_status
            FROM {$this->table} b
            LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            WHERE b.user_id_fk = :user_id
            ORDER BY b.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $this->id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function GetUserStats()
    {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN b.status = 'approved' AND CONCAT(s.departure_date, ' ', s.departure_time) > NOW() THEN 1 ELSE 0 END) as upcoming,
                SUM(CASE WHEN b.status = 'approved' AND CONCAT(s.departure_date, ' ', s.departure_time) <= NOW() THEN 1 ELSE 0 END) as completed
            FROM {$this->table} b
            LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            WHERE b.user_id_fk = :user_id
        ");
        $stmt->execute([':user_id' => $this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function GetBookingsByUserFiltered()
    {
        $query = "
            SELECT 
                b.*,
                CONCAT(r.origin, ' → ', r.destination) as route_display,
                s.departure_date,
                s.departure_time,
                s.trip_status as schedule_status
            FROM {$this->table} b
            LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            WHERE b.user_id_fk = :user_id
        ";

        if ($this->status === 'upcoming') {
            $query .= " AND b.status = 'approved' AND CONCAT(s.departure_date, ' ', s.departure_time) > NOW()";
        } elseif ($this->status === 'completed') {
            $query .= " AND b.status = 'approved' AND CONCAT(s.departure_date, ' ', s.departure_time) <= NOW()";
        } elseif ($this->status === 'cancelled') {
            $query .= " AND b.status = 'cancelled'";
        }

        $query .= " ORDER BY b.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $this->id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
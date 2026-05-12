<?php
class Dashboard
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ── BOOKING SUMMARY ───────────────────────────────────────────────────────
    public function GetBookingSummary(): array
    {
        $stmt = $this->conn->query("
            SELECT
                COUNT(*)                                                AS total_bookings,
                SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END)  AS pending,
                SUM(CASE WHEN status = 'approved'  THEN 1 ELSE 0 END)  AS approved,
                SUM(CASE WHEN status = 'rejected'  THEN 1 ELSE 0 END)  AS rejected,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END)  AS cancelled
            FROM bookings
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ── SCHEDULE SUMMARY ──────────────────────────────────────────────────────
    public function GetScheduleSummary(): array
    {
        $stmt = $this->conn->query("
            SELECT
                COUNT(*)                                                     AS total_schedules,
                SUM(CASE WHEN trip_status != 'cancelled' THEN 1 ELSE 0 END)  AS active_schedules,
                SUM(CASE WHEN trip_status = 'arrived'    THEN 1 ELSE 0 END)  AS completed_trips
            FROM schedules
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ── TOTAL USERS ───────────────────────────────────────────────────────────
    public function GetTotalUsers(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    // ── SEATS BOOKED (approved bookings only) ────────────────────────────────
    public function GetSeatsBooked(): int
    {
        return (int) $this->conn->query("
            SELECT COUNT(*) FROM bookings WHERE status = 'approved'
        ")->fetchColumn();
    }

    // ── BOOKINGS BY STATUS (for pie/doughnut chart) ───────────────────────────
    public function GetBookingsByStatus(): array
    {
        $stmt = $this->conn->query("
            SELECT status, COUNT(*) AS total
            FROM bookings
            GROUP BY status
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ── DAILY BOOKINGS — last 7 days (for bar chart) ─────────────────────────
    public function GetDailyBookings(): array
    {
        $stmt = $this->conn->query("
            SELECT DATE(created_at) AS date, COUNT(*) AS total
            FROM bookings
            WHERE created_at >= CURDATE() - INTERVAL 6 DAY
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ── RECENT BOOKINGS — last 5 ─────────────────────────────────────────────
    public function GetRecentBookings(): array
    {
        $stmt = $this->conn->query("
            SELECT
                b.reference_code,
                b.status,
                b.created_at,
                u.firstname                               AS passenger,
                CONCAT(r.origin, ' → ', r.destination)  AS route_display
            FROM bookings b
            LEFT JOIN users     u ON b.user_id_fk     = u.user_id_pk
            LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
            LEFT JOIN routes    r ON s.route_id_fk    = r.route_id_pk
            ORDER BY b.created_at DESC
            LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function GetRecentActivity(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));

        $stmt = $this->conn->prepare("
            SELECT *
            FROM (
                SELECT
                    'booking' AS type,
                    CONCAT('Booking ', b.reference_code, ' is ', b.status) AS title,
                    CONCAT(COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, ''))), ''), 'Passenger'), ' - ', COALESCE(CONCAT(r.origin, ' -> ', r.destination), 'Route unavailable')) AS detail,
                    b.updated_at AS event_time,
                    CASE b.status
                        WHEN 'pending' THEN '#F97316'
                        WHEN 'approved' THEN '#16a34a'
                        WHEN 'rejected' THEN '#ef4444'
                        WHEN 'cancelled' THEN '#9ca3af'
                        WHEN 'completed' THEN '#2563eb'
                        ELSE '#64748b'
                    END AS color
                FROM bookings b
                LEFT JOIN users u ON b.user_id_fk = u.user_id_pk
                LEFT JOIN schedules s ON b.schedule_id_fk = s.schedule_id_pk
                LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk

                UNION ALL

                SELECT
                    'schedule' AS type,
                    CONCAT('Schedule marked ', s.trip_status) AS title,
                    CONCAT(COALESCE(r.origin, 'Origin'), ' -> ', COALESCE(r.destination, 'Destination')) AS detail,
                    COALESCE(s.updated_at, s.created_at) AS event_time,
                    CASE s.trip_status
                        WHEN 'boarding' THEN '#F97316'
                        WHEN 'departed' THEN '#2563eb'
                        WHEN 'arrived' THEN '#16a34a'
                        WHEN 'cancelled' THEN '#9ca3af'
                        ELSE '#64748b'
                    END AS color
                FROM schedules s
                LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk

                UNION ALL

                SELECT
                    'payment' AS type,
                    CONCAT('Payment ', p.status) AS title,
                    CONCAT(COALESCE(b.reference_code, 'Booking'), ' - ', COALESCE(p.payment_method, 'payment method')) AS detail,
                    COALESCE(p.paid_at, p.created_at) AS event_time,
                    CASE p.status
                        WHEN 'pending' THEN '#F97316'
                        WHEN 'paid' THEN '#16a34a'
                        WHEN 'rejected' THEN '#ef4444'
                        WHEN 'refund_requested' THEN '#2563eb'
                        WHEN 'refunded' THEN '#64748b'
                        ELSE '#64748b'
                    END AS color
                FROM payments p
                LEFT JOIN bookings b ON p.book_id_fk = b.book_id_pk

                UNION ALL

                SELECT
                    'user' AS type,
                    'New passenger registered' AS title,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, ''))), ''), 'Passenger') AS detail,
                    u.created_at AS event_time,
                    '#378ADD' AS color
                FROM users u
                WHERE u.role = 'user'
            ) activity
            WHERE event_time IS NOT NULL
            ORDER BY event_time DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(function ($row) {
            return [
                'type' => $row['type'],
                'title' => $row['title'],
                'detail' => $row['detail'],
                'time' => $this->FormatRelativeTime($row['event_time']),
                'timestamp' => $row['event_time'],
                'color' => $row['color'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    private function FormatRelativeTime(?string $time): string
    {
        if (!$time) {
            return 'Unknown time';
        }

        $timestamp = strtotime($time);
        if (!$timestamp) {
            return 'Unknown time';
        }

        $diff = max(0, time() - $timestamp);
        if ($diff < 60) {
            return 'Just now';
        }
        if ($diff < 3600) {
            $minutes = (int) floor($diff / 60);
            return $minutes . ' min ago';
        }
        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
        }

        return date('M d, g:i A', $timestamp);
    }

    public function GetTotalPending()
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM verification_documents
                 WHERE status = 'pending'"
            );
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>

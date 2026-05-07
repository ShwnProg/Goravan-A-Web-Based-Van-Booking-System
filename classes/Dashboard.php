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
}
?>
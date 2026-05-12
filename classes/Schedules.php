<?php
class Schedules
{
    private $conn = null;
    private $table = "schedules";

    public $id;
    public $route_id;
    public $driver_id;
    public $van_id;
    public $departure_date;
    public $departure_time;
    public $estimated_arrival_at;
    public $trip_status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetAllSchedules(): array
    {
        $this->ApplyAutomaticArrivals();

        $stmt = $this->conn->prepare("
            SELECT 
                s.*,
                CONCAT(r.origin, ' → ', r.destination) as route_display,
                d.full_name as driver_name,
                d.license_number as driver_license,
                v.plate_number as van_plate,
                v.model as van_model,
                v.capacity as van_capacity,
                (
                    SELECT COUNT(*)
                    FROM bookings b
                    WHERE b.schedule_id_fk = s.schedule_id_pk
                      AND b.status = 'pending'
                ) as pending_bookings_count
            FROM {$this->table} s
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            LEFT JOIN drivers d ON s.driver_id_fk = d.driver_id_pk
            LEFT JOIN vans v ON s.van_id_fk = v.van_id_pk
            ORDER BY
                CASE s.trip_status
                    WHEN 'boarding' THEN 0
                    WHEN 'departed' THEN 1
                    WHEN 'arrived' THEN 2
                    WHEN 'cancelled' THEN 3
                    ELSE 4
                END,
                s.created_at DESC
        ");
        $stmt->execute();
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $seatStmt = $this->conn->prepare("
            SELECT * FROM seats ORDER BY van_id_fk, seat_row ASC, seat_col ASC
        ");
        $seatStmt->execute();
        $allSeats = $seatStmt->fetchAll(PDO::FETCH_ASSOC);
        $groupedSeats = [];
        foreach ($allSeats as $seat) {
            $groupedSeats[$seat['van_id_fk']][] = $seat;
        }

        foreach ($schedules as &$sch) {
            $sch['van_seats'] = $groupedSeats[$sch['van_id_fk']] ?? [];
        }

        return $this->AttachStopsToSchedules($schedules);
    }

    public function GetScheduleByID(): array
    {
        $stmt = $this->conn->prepare("
            SELECT 
                s.*,
                r.origin, r.destination, r.fare as route_fare,
                d.full_name, d.license_number, d.contact_number,
                v.plate_number, v.model, v.capacity
            FROM {$this->table} s
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            LEFT JOIN drivers d ON s.driver_id_fk = d.driver_id_pk
            LEFT JOIN vans v ON s.van_id_fk = v.van_id_pk
            WHERE s.schedule_id_pk = :id
        ");
        $stmt->execute([':id' => $this->id]);
        $sch = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sch)
            return [];

        $seatStmt = $this->conn->prepare("
            SELECT * FROM seats WHERE van_id_fk = :van_id ORDER BY seat_row, seat_col
        ");
        $seatStmt->execute([':van_id' => $sch['van_id_fk']]);
        $sch['van_seats'] = $seatStmt->fetchAll(PDO::FETCH_ASSOC);

        return [$sch];
    }

    public function HasVanConflict(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT schedule_id_pk FROM {$this->table}
            WHERE van_id_fk = :van_id
              AND departure_date = :date
              AND departure_time = :time
              AND schedule_id_pk != :id
        ");
        $stmt->execute([
            ':van_id' => $this->van_id,
            ':date' => $this->departure_date,
            ':time' => $this->departure_time,
            ':id' => $this->id ?: 0
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function HasDriverConflict(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT schedule_id_pk FROM {$this->table}
            WHERE driver_id_fk = :driver_id
              AND departure_date = :date
              AND departure_time = :time
              AND schedule_id_pk != :id
        ");
        $stmt->execute([
            ':driver_id' => $this->driver_id,
            ':date' => $this->departure_date,
            ':time' => $this->departure_time,
            ':id' => $this->id ?: 0
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function AddSchedule(): array
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table}
                    (route_id_fk, driver_id_fk, van_id_fk, departure_date, departure_time, estimated_arrival_at, trip_status)
                VALUES
                    (:route_id, :driver_id, :van_id, :date, :time, :eta, :status)
            ");
            $stmt->execute([
                ':route_id' => $this->route_id,
                ':driver_id' => $this->driver_id,
                ':van_id' => $this->van_id,
                ':date' => $this->departure_date,
                ':time' => $this->departure_time,
                ':eta' => $this->estimated_arrival_at,
                ':status' => $this->trip_status
            ]);

            $this->conn->commit();
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function EditSchedule(): array
    {
        try {
            $currentStatus = $this->getCurrentStatus();
            if (!$currentStatus) {
                return ['success' => false, 'message' => 'Schedule not found.'];
            }

            if ($this->trip_status !== $currentStatus && $this->HasPendingBookings()) {
                return [
                    'success' => false,
                    'message' => 'Resolve pending bookings before changing this schedule status.'
                ];
            }

            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET route_id_fk  = :route_id,
                    driver_id_fk = :driver_id,
                    van_id_fk    = :van_id,
                    departure_date = :date,
                    departure_time = :time,
                    estimated_arrival_at = :eta,
                    trip_status  = :status,
                    arrived_at = CASE
                        WHEN :status2 = 'arrived' AND :current_status <> 'arrived' THEN NOW()
                        WHEN :status3 = 'arrived' THEN arrived_at
                        ELSE NULL
                    END,
                    updated_at   = NOW()
                WHERE schedule_id_pk = :id
            ");
            $stmt->execute([
                ':id' => $this->id,
                ':route_id' => $this->route_id,
                ':driver_id' => $this->driver_id,
                ':van_id' => $this->van_id,
                ':date' => $this->departure_date,
                ':time' => $this->departure_time,
                ':eta' => $this->estimated_arrival_at,
                ':status' => $this->trip_status,
                ':status2' => $this->trip_status,
                ':status3' => $this->trip_status,
                ':current_status' => $currentStatus
            ]);

            if ($this->trip_status === 'cancelled') {
                $this->CancelBookingsForSchedule((int) $this->id);
            } elseif ($this->trip_status === 'arrived') {
                $this->CompleteBookingsForSchedule((int) $this->id);
            }

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function DeleteSchedule(): array
    {
        try {
            if (!$this->id) {
                return ['success' => false, 'message' => 'Invalid schedule ID.'];
            }

            $bookingCount = $this->CountBookingsForSchedule((int) $this->id);
            if ($bookingCount > 0) {
                return [
                    'success' => false,
                    'message' => 'This schedule has booking history, so it cannot be deleted. Keep it cancelled to preserve records.'
                ];
            }

            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE schedule_id_pk = :id");
            $stmt->execute([':id' => $this->id]);

            return [
                'success' => $stmt->rowCount() > 0,
                'message' => $stmt->rowCount() > 0 ? 'Schedule deleted successfully.' : 'Schedule not found.'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Unable to delete this schedule because it is still linked to other records.',
                'error' => $e->getMessage()
            ];
        }
    }

    public function canUpdateStatus(string $newStatus): bool
    {
        if (!$this->id)
            return false;

        $current = $this->getCurrentStatus();
        if (!$current)
            return false;

        if ($newStatus !== $current && $this->HasPendingBookings()) {
            return false;
        }

        $transitions = [
            'boarding' => ['departed', 'arrived', 'cancelled'],
            'departed' => ['arrived', 'cancelled'],
            'arrived' => [],
            'cancelled' => []
        ];

        return in_array($newStatus, $transitions[$current] ?? []);
    }

    private function getCurrentStatus(): ?string
    {
        $stmt = $this->conn->prepare("
            SELECT trip_status FROM {$this->table} WHERE schedule_id_pk = :id
        ");
        $stmt->execute([':id' => $this->id]);
        return $stmt->fetchColumn() ?: null;
    }

    public function HasPendingBookings(): bool
    {
        if (!$this->id) {
            return false;
        }

        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE schedule_id_fk = :schedule_id
              AND status = 'pending'
        ");
        $stmt->execute([':schedule_id' => $this->id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function CountBookingsForSchedule(int $scheduleId): int
    {
        if (!$scheduleId) {
            return 0;
        }

        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE schedule_id_fk = :schedule_id
        ");
        $stmt->execute([':schedule_id' => $scheduleId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Updates trip_status and clears arrived_at unless the trip is arrived.
     */
    public function UpdateStatus(): array
    {
        try {
            $currentStatus = $this->getCurrentStatus();
            if (!$currentStatus) {
                return ['success' => false, 'message' => 'Schedule not found.'];
            }

            if ($this->trip_status !== $currentStatus && $this->HasPendingBookings()) {
                return [
                    'success' => false,
                    'message' => 'Resolve pending bookings before changing this schedule status.'
                ];
            }

            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET trip_status = :status,
                    arrived_at  = CASE
                        WHEN :status2 = 'arrived' AND :current_status <> 'arrived' THEN NOW()
                        WHEN :status3 = 'arrived' THEN arrived_at
                        ELSE NULL
                    END,
                    updated_at  = NOW()
                WHERE schedule_id_pk = :id
            ");
            $stmt->execute([
                ':status' => $this->trip_status,
                ':status2' => $this->trip_status,   // PDO doesn't allow reusing named params
                ':status3' => $this->trip_status,
                ':current_status' => $currentStatus,
                ':id' => $this->id
            ]);
            if ($this->trip_status === 'arrived') {
                $this->CompleteBookingsForSchedule((int) $this->id);
            } elseif ($this->trip_status === 'cancelled') {
                $this->CancelBookingsForSchedule((int) $this->id);
            }
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function ApplyAutomaticArrivals(): void
    {
        try {
            $this->conn->beginTransaction();

            $this->conn->exec("
                UPDATE bookings b
                INNER JOIN {$this->table} s ON b.schedule_id_fk = s.schedule_id_pk
                SET b.status = 'cancelled',
                    b.updated_at = NOW()
                WHERE s.trip_status = 'cancelled'
                  AND b.status NOT IN ('rejected', 'cancelled', 'completed')
            ");

            $this->conn->commit();
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log('[Schedules::ApplyAutomaticArrivals] ' . $e->getMessage());
        }
    }

    private function CompleteBookingsForSchedule(int $scheduleId): void
    {
        if (!$scheduleId) {
            return;
        }

        $stmt = $this->conn->prepare("
            UPDATE bookings
            SET status = 'completed',
                updated_at = NOW()
            WHERE schedule_id_fk = :schedule_id
              AND status = 'approved'
        ");
        $stmt->execute([':schedule_id' => $scheduleId]);
    }

    private function CancelBookingsForSchedule(int $scheduleId): void
    {
        if (!$scheduleId) {
            return;
        }

        $stmt = $this->conn->prepare("
            UPDATE bookings
            SET status = 'cancelled',
                updated_at = NOW()
            WHERE schedule_id_fk = :schedule_id
              AND status NOT IN ('rejected', 'cancelled', 'completed')
        ");
        $stmt->execute([':schedule_id' => $scheduleId]);
    }
    public function GetAvailableSchedules(array $filters = [])
    {
        $this->ApplyAutomaticArrivals();

        $where = [
            "s.trip_status = 'boarding'",
            "r.is_active = 1",
            "s.departure_date >= CURDATE()"
        ];
        $params = [];

        if (!empty($filters['from'])) {
            $where[] = 'r.origin = :origin';
            $params[':origin'] = trim($filters['from']);
        }

        if (!empty($filters['to'])) {
            $where[] = 'r.destination = :destination';
            $params[':destination'] = trim($filters['to']);
        }

        if (!empty($filters['date'])) {
            $where[] = 's.departure_date = :departure_date';
            $params[':departure_date'] = trim($filters['date']);
        }

        $stmt = $this->conn->prepare("
            SELECT 
                s.schedule_id_pk,
                s.route_id_fk,
                s.van_id_fk,
                s.departure_date,
                s.departure_time,
                s.estimated_arrival_at,
                s.arrived_at,
                r.origin, r.destination, r.fare as route_fare,
                d.full_name, d.license_number, d.contact_number,
                v.plate_number, v.model, v.capacity,
                COUNT(DISTINCT seats.seat_id_pk) AS total_seats,
                COUNT(DISTINCT CASE
                    WHEN b.book_id_pk IS NULL THEN seats.seat_id_pk
                    ELSE NULL
                END) AS available_seats
            FROM {$this->table} s
            INNER JOIN routes r ON s.route_id_fk = r.route_id_pk
            INNER JOIN drivers d ON s.driver_id_fk = d.driver_id_pk
            INNER JOIN vans v ON s.van_id_fk = v.van_id_pk
            LEFT JOIN seats ON seats.van_id_fk = v.van_id_pk
            LEFT JOIN bookings b
                ON b.schedule_id_fk = s.schedule_id_pk
               AND b.seat_id_fk = seats.seat_id_pk
               AND b.status NOT IN ('rejected', 'cancelled')
            WHERE " . implode(' AND ', $where) . "
            GROUP BY s.schedule_id_pk
            ORDER BY s.departure_date ASC, s.departure_time ASC
        ");
        $stmt->execute($params);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->AttachStopsToSchedules($schedules);
    }

    public function AttachStopsToSchedules(array $schedules): array
    {
        if (empty($schedules)) {
            return [];
        }

        $routeIds = array_values(array_unique(array_map(fn($s) => (int) $s['route_id_fk'], $schedules)));
        $stopsByRoute = $this->GetStopsByRouteIds($routeIds);

        foreach ($schedules as &$schedule) {
            $schedule['stops'] = $stopsByRoute[(int) $schedule['route_id_fk']] ?? [];
        }

        return $schedules;
    }

    public function GetStopsByRouteIds(array $routeIds): array
    {
        $routeIds = array_values(array_unique(array_filter(array_map('intval', $routeIds))));
        if (empty($routeIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($routeIds), '?'));
        $stmt = $this->conn->prepare("
            SELECT route_id_fk, stop_name
            FROM route_stops
            WHERE route_id_fk IN ($placeholders)
            ORDER BY route_id_fk ASC, stop_order ASC
        ");
        $stmt->execute($routeIds);

        $grouped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $stop) {
            $grouped[(int) $stop['route_id_fk']][] = $stop['stop_name'];
        }

        return $grouped;
    }

    public function GetSeatAvailability(int $scheduleId): array
    {
        $this->ApplyAutomaticArrivals();

        $stmt = $this->conn->prepare("
            SELECT
                s.schedule_id_pk,
                s.route_id_fk,
                s.van_id_fk,
                s.departure_date,
                s.departure_time,
                s.estimated_arrival_at,
                s.arrived_at,
                r.origin,
                r.destination,
                r.fare,
                v.model AS van_model,
                v.plate_number AS van_plate,
                v.capacity AS van_capacity
            FROM {$this->table} s
            INNER JOIN routes r ON s.route_id_fk = r.route_id_pk
            INNER JOIN vans v ON s.van_id_fk = v.van_id_pk
            WHERE s.schedule_id_pk = :schedule_id
              AND s.trip_status = 'boarding'
              AND r.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':schedule_id' => $scheduleId]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$schedule) {
            return [];
        }

        $stopsByRoute = $this->GetStopsByRouteIds([(int) $schedule['route_id_fk']]);
        $schedule['stops'] = $stopsByRoute[(int) $schedule['route_id_fk']] ?? [];

        $bookedStmt = $this->conn->prepare("
            SELECT seat_id_fk
            FROM bookings
            WHERE schedule_id_fk = :schedule_id
              AND status NOT IN ('rejected', 'cancelled')
        ");
        $bookedStmt->execute([':schedule_id' => $scheduleId]);
        $bookedIds = array_flip(array_map('intval', $bookedStmt->fetchAll(PDO::FETCH_COLUMN)));

        $seatStmt = $this->conn->prepare("
            SELECT seat_id_pk, seat_number, seat_row, seat_col
            FROM seats
            WHERE van_id_fk = :van_id
            ORDER BY seat_row ASC, seat_col ASC
        ");
        $seatStmt->execute([':van_id' => $schedule['van_id_fk']]);

        $seats = array_map(function ($seat) use ($bookedIds) {
            $seatId = (int) $seat['seat_id_pk'];
            return [
                'seat_id_pk' => $seatId,
                'seat_number' => $seat['seat_number'],
                'seat_row' => (int) $seat['seat_row'],
                'seat_col' => (int) $seat['seat_col'],
                'is_booked' => isset($bookedIds[$seatId]),
            ];
        }, $seatStmt->fetchAll(PDO::FETCH_ASSOC));

        return [
            'schedule' => $schedule,
            'seats' => $seats,
        ];
    }
}
?>

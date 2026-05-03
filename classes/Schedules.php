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
    public $trip_status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function GetAllSchedules(): array
    {
        $stmt = $this->conn->prepare("
            SELECT 
                s.*,
                CONCAT(r.origin, ' → ', r.destination) as route_display,
                d.full_name as driver_name,
                d.license_number as driver_license,
                v.plate_number as van_plate,
                v.model as van_model,
                v.capacity as van_capacity
            FROM {$this->table} s
            LEFT JOIN routes r ON s.route_id_fk = r.route_id_pk
            LEFT JOIN drivers d ON s.driver_id_fk = d.driver_id_pk
            LEFT JOIN vans v ON s.van_id_fk = v.van_id_pk
            ORDER BY s.created_at DESC
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

        return $schedules;
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

        if (!$sch) return [];

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
            ':date'   => $this->departure_date,
            ':time'   => $this->departure_time,
            ':id'     => $this->id ?: 0
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
            ':date'      => $this->departure_date,
            ':time'      => $this->departure_time,
            ':id'        => $this->id ?: 0
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function AddSchedule(): array
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table}
                    (route_id_fk, driver_id_fk, van_id_fk, departure_date, departure_time, trip_status)
                VALUES
                    (:route_id, :driver_id, :van_id, :date, :time, :status)
            ");
            $stmt->execute([
                ':route_id'  => $this->route_id,
                ':driver_id' => $this->driver_id,
                ':van_id'    => $this->van_id,
                ':date'      => $this->departure_date,
                ':time'      => $this->departure_time,
                ':status'    => $this->trip_status
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
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET route_id_fk  = :route_id,
                    driver_id_fk = :driver_id,
                    van_id_fk    = :van_id,
                    departure_date = :date,
                    departure_time = :time,
                    trip_status  = :status,
                    updated_at   = NOW()
                WHERE schedule_id_pk = :id
            ");
            $stmt->execute([
                ':id'        => $this->id,
                ':route_id'  => $this->route_id,
                ':driver_id' => $this->driver_id,
                ':van_id'    => $this->van_id,
                ':date'      => $this->departure_date,
                ':time'      => $this->departure_time,
                ':status'    => $this->trip_status
            ]);

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function DeleteSchedule(): array
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE schedule_id_pk = :id");
        $stmt->execute([':id' => $this->id]);
        return ['success' => true];
    }

    public function canUpdateStatus(string $newStatus): bool
    {
        if (!$this->id) return false;

        $current = $this->getCurrentStatus();
        if (!$current) return false;

        $transitions = [
            'boarding'  => ['departed', 'cancelled'],
            'departed'  => ['arrived',  'cancelled'],
            'arrived'   => [],
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

    /**
     * Updates trip_status and sets arrived_at = NOW() when status is 'arrived',
     * otherwise sets arrived_at = NULL (in case of a correction via edit).
     */
    public function UpdateStatus(): array
    {
        try {
            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET trip_status = :status,
                    arrived_at  = CASE WHEN :status2 = 'arrived' THEN NOW() ELSE arrived_at END,
                    updated_at  = NOW()
                WHERE schedule_id_pk = :id
            ");
            $stmt->execute([
                ':status'  => $this->trip_status,
                ':status2' => $this->trip_status,   // PDO doesn't allow reusing named params
                ':id'      => $this->id
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
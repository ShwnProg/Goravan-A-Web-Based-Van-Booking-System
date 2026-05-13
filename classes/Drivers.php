<?php
class Drivers
{
    private $conn = null;
    private $table = "drivers";

    public $id;
    public $full_name;
    public $license_number;
    public $contact_number;
    public $status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // READ 
    public function GetAllDrivers(): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            ORDER BY
                CASE WHEN status = 'active' THEN 0 ELSE 1 END,
                driver_id_pk DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function GetDriverByID(): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} WHERE driver_id_pk = :id
        ");
        $stmt->execute([':id' => $this->id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); 
    }

    public function IsLicenseExist(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT driver_id_pk FROM {$this->table}
            WHERE UPPER(license_number) = UPPER(:license_number)
        ");
        $stmt->execute([':license_number' => $this->license_number]);
        return (bool) $stmt->fetchColumn();
    }

    public function IsLicenseExistExcept(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT driver_id_pk FROM {$this->table}
            WHERE UPPER(license_number) = UPPER(:license_number)
              AND driver_id_pk != :id
        ");
        $stmt->execute([
            ':license_number' => $this->license_number,
            ':id' => $this->id,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    // CREATE 
    public function AddDriver(): array
    {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table} (full_name, license_number, contact_number, status)
                VALUES (:full_name, :license_number, :contact_number, :status)
            ");
            $stmt->execute([
                ':full_name' => $this->full_name,
                ':license_number' => strtoupper($this->license_number),
                ':contact_number' => $this->contact_number,
                ':status' => $this->status,
            ]);

            return ['success' => true, 'id' => (int) $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            error_log('[Drivers::AddDriver] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to add driver. Please check the details and try again.'];
        }
    }

    // UPDATE
    public function EditDriver(): array
    {
        try {
            if (!$this->DriverExists((int) $this->id)) {
                return ['success' => false, 'message' => 'Driver not found.'];
            }

            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET full_name = :full_name,
                    license_number = :license_number,
                    contact_number = :contact_number,
                    status = :status
                WHERE driver_id_pk = :id
            ");
            $stmt->execute([
                ':full_name' => $this->full_name,
                ':license_number' => strtoupper($this->license_number),
                ':contact_number' => $this->contact_number,
                ':status' => $this->status,
                ':id' => $this->id,
            ]);

            return ['success' => true];
        } catch (PDOException $e) {
            error_log('[Drivers::EditDriver] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to update record. Please check the details and try again.'];
        }
    }

    // ── DELETE ────────────────────────────────────────────────
    public function DeleteDriver(): array
    {
        try {
            if (!$this->id) {
                return ['success' => false, 'message' => 'Invalid driver ID.'];
            }

            if ($this->CountAssignedSchedules((int) $this->id) > 0) {
                return [
                    'success' => false,
                    'message' => 'This record cannot be deleted because it is already linked to existing schedules or bookings. You may deactivate it instead.'
                ];
            }

            $stmt = $this->conn->prepare("
                DELETE FROM {$this->table} WHERE driver_id_pk = :id
            ");
            $stmt->execute([':id' => $this->id]);
            return [
                'success' => $stmt->rowCount() > 0,
                'message' => $stmt->rowCount() > 0 ? 'Driver deleted successfully.' : 'Driver not found.'
            ];
        } catch (PDOException $e) {
            error_log('[Drivers::DeleteDriver] ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'This record cannot be deleted because it is already linked to existing schedules or bookings. You may deactivate it instead.'
            ];
        }
    }

    // ── TOGGLE ────────────────────────────────────────────────
    public function ToggleDriver(): array
    {
        try {
            if (!$this->DriverExists((int) $this->id)) {
                return ['success' => false, 'message' => 'Driver not found.'];
            }

            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET status = :status
                WHERE driver_id_pk = :id
            ");
            $stmt->execute([
                ':status' => $this->status,
                ':id' => $this->id,
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log('[Drivers::ToggleDriver] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Unable to update driver status. Please try again.'];
        }
    }

    private function CountAssignedSchedules(int $driverId): int
    {
        if (!$driverId) {
            return 0;
        }

        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM schedules
            WHERE driver_id_fk = :id
        ");
        $stmt->execute([':id' => $driverId]);
        return (int) $stmt->fetchColumn();
    }

    private function DriverExists(int $driverId): bool
    {
        if (!$driverId) {
            return false;
        }

        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM {$this->table}
            WHERE driver_id_pk = :id
        ");
        $stmt->execute([':id' => $driverId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
?>

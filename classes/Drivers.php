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
            ORDER BY driver_id_pk DESC
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
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // UPDATE
    public function EditDriver(): array
    {
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
    }

    // ── DELETE ────────────────────────────────────────────────
    public function DeleteDriver(): array
    {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM {$this->table} WHERE driver_id_pk = :id
            ");
            $stmt->execute([':id' => $this->id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── TOGGLE ────────────────────────────────────────────────
    public function ToggleDriver(): array
    {
        try {
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
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
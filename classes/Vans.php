<?php
class Vans
{
    private $conn  = null;
    private $table = "vans";

    public $id;
    public $plate_number;
    public $model;
    public $capacity;
    public $status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ── READ ──────────────────────────────────────────────────

    public function GetAllVans(): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            ORDER BY van_id_pk DESC
        ");
        $stmt->execute();
        $vans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($vans)) return [];

        // Batch-load all seats grouped by van
        $allSeats = $this->conn->prepare("
            SELECT * FROM seats
            ORDER BY van_id_fk, seat_row ASC, seat_col ASC
        ");
        $allSeats->execute();
        $allSeats = $allSeats->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($allSeats as $seat) {
            $grouped[$seat['van_id_fk']][] = $seat;
        }

        foreach ($vans as &$v) {
            $v['seats'] = $grouped[$v['van_id_pk']] ?? [];
        }

        return $vans;
    }

    public function GetVanByID(): array
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table} WHERE van_id_pk = :id
        ");
        $stmt->execute([':id' => $this->id]);
        $vans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($vans)) return [];

        $seatStmt = $this->conn->prepare("
            SELECT * FROM seats
            WHERE van_id_fk = :id
            ORDER BY seat_row ASC, seat_col ASC
        ");
        $seatStmt->execute([':id' => $this->id]);
        $vans[0]['seats'] = $seatStmt->fetchAll(PDO::FETCH_ASSOC);

        return $vans;
    }

    public function IsPlateExist(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT van_id_pk FROM {$this->table}
            WHERE LOWER(plate_number) = LOWER(:plate_number)
        ");
        $stmt->execute([':plate_number' => $this->plate_number]);
        return (bool) $stmt->fetchColumn();
    }

    public function IsPlateExistExcept(): bool
    {
        $stmt = $this->conn->prepare("
            SELECT van_id_pk FROM {$this->table}
            WHERE LOWER(plate_number) = LOWER(:plate_number)
              AND van_id_pk != :id
        ");
        $stmt->execute([
            ':plate_number' => $this->plate_number,
            ':id'           => $this->id,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    // ── CREATE ────────────────────────────────────────────────

    public function AddVan(): array
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                INSERT INTO {$this->table} (plate_number, model, capacity, status)
                VALUES (:plate_number, :model, :capacity, :status)
            ");
            $stmt->execute([
                ':plate_number' => strtoupper($this->plate_number),
                ':model'        => $this->model,
                ':capacity'     => $this->capacity,
                ':status'       => $this->status,
            ]);

            $vanId = (int) $this->conn->lastInsertId();
            $this->_generateSeats($vanId, $this->capacity);

            $this->conn->commit();
            return ['success' => true, 'id' => $vanId];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── UPDATE ────────────────────────────────────────────────

    public function EditVan(): array
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET plate_number = :plate_number,
                    model        = :model,
                    capacity     = :capacity,
                    status       = :status
                WHERE van_id_pk  = :id
            ");
            $stmt->execute([
                ':plate_number' => strtoupper($this->plate_number),
                ':model'        => $this->model,
                ':capacity'     => $this->capacity,
                ':status'       => $this->status,
                ':id'           => $this->id,
            ]);

            // Regenerate seats whenever capacity changes
            $this->_deleteSeats($this->id);
            $this->_generateSeats($this->id, $this->capacity);

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── DELETE ────────────────────────────────────────────────

    public function DeleteVan(): array
    {
        try {
            $this->conn->beginTransaction();
            $this->_deleteSeats($this->id);

            $stmt = $this->conn->prepare("
                DELETE FROM {$this->table} WHERE van_id_pk = :id
            ");
            $stmt->execute([':id' => $this->id]);

            $this->conn->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── TOGGLE ────────────────────────────────────────────────

    public function ToggleVan(): array
    {
        try {
            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET status = :status
                WHERE van_id_pk = :id
            ");
            $stmt->execute([
                ':status' => $this->status,
                ':id'     => $this->id,
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ── PRIVATE HELPERS ───────────────────────────────────────

    /**
     * Auto-generate seats for a van.
     * 2 columns always; rows = ceil(capacity / 2).
     * Labels: A1, A2, B1, B2 … up to capacity count.
     */
    private function _generateSeats(int $vanId, int $capacity): void
    {
        if ($capacity <= 0) return;

        $cols    = 2;
        $rows    = (int) ceil($capacity / $cols);
        $letters = range('A', 'Z');
        $count   = 0;

        $stmt = $this->conn->prepare("
            INSERT INTO seats (seat_number, seat_row, seat_col, van_id_fk)
            VALUES (:seat_number, :seat_row, :seat_col, :van_id)
        ");

        for ($r = 0; $r < $rows; $r++) {
            for ($c = 1; $c <= $cols; $c++) {
                if ($count >= $capacity) break;
                $stmt->execute([
                    ':seat_number' => $letters[$r] . $c,
                    ':seat_row'    => $r + 1,
                    ':seat_col'    => $c,
                    ':van_id'      => $vanId,
                ]);
                $count++;
            }
        }
    }

    private function _deleteSeats(int $vanId): void
    {
        $stmt = $this->conn->prepare("DELETE FROM seats WHERE van_id_fk = :id");
        $stmt->execute([':id' => $vanId]);
    }
}
?>
<?php
class Verification
{
    private $conn = null;
    private $table = 'verification_documents';

    public $user_id_fk;
    public $type;
    public $document;
    public $status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ── Core: insert new verification record (used for all submission types)
    public function AddDocuments()
    {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO $this->table (user_id_fk, document_type, file_path, submitted_at, status)
                 VALUES (:id, :type, :path, :submitted_at, :status)"
            );
            $stmt->execute([
                ':id' => $this->user_id_fk,
                ':type' => $this->type,
                ':path' => $this->document,
                ':status' => $this->status,
                ':submitted_at' => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Returns the most recent verification record for the user.
     * Includes rejection_reason if the column exists; falls back gracefully.
     */
    public function GetVerficationStatus()
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT COALESCE(status, 'pending') AS status, document_type, rejection_reason
                 FROM $this->table
                 WHERE user_id_fk = :id
                 ORDER BY submitted_at DESC, document_id_pk DESC
                 LIMIT 1"
            );
            $stmt->execute([':id' => $this->user_id_fk]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            // Fallback: rejection_reason column may not exist yet
            try {
                $stmt2 = $this->conn->prepare(
                    "SELECT COALESCE(status, 'pending') AS status, document_type
                     FROM $this->table
                     WHERE user_id_fk = :id
                     ORDER BY submitted_at DESC, document_id_pk DESC
                     LIMIT 1"
                );
                $stmt2->execute([':id' => $this->user_id_fk]);
                $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                return $result2 ?: null;
            } catch (PDOException $e2) {
                return null;
            }
        }
    }

    /**
     * Returns true if the user currently has a pending verification.
     * Used by the controller to block duplicate submissions.
     */
    public function HasPendingVerification()
    {
        try {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) FROM $this->table
                 WHERE user_id_fk = :id AND (status = 'pending' OR status IS NULL)"
            );
            $stmt->execute([':id' => $this->user_id_fk]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
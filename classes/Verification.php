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

    public function AddDocuments()
    {

        try {
            $stmt = $this->conn->prepare("INSERT INTO $this->table(user_id_fk,document_type,file_path,submitted_at,status) 
                                          VALUES (:id,:type,:path,:submitted_at,status)");

            $stmt->execute([
                ':id' => $this->user_id_fk,
                ':type' => $this->type,
                ':path' => $this->document,
                ':status' => $this->status,
                ':submitted_at' => date('Y-m-d H:i:s')
            ]);

            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}
?>
<?php
class Users
{
    private $conn = null;
    private $table = 'users';
    public $id;
    public $fullname;
    public $email;
    public $contact;
    public $password;
    public $role = 'user';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function AddUser()
    {
        try {
            $stmt0 = $this->conn->prepare("INSERT INTO $this->table (fullname,email,contact_number,password,role,created_at)
                                           VALUES (:fullname,:email,:contact,:password,:role,:created_at)");
            $stmt0->execute([
                ':fullname' => $this->fullname,
                ':email' => $this->email,
                ':contact' => $this->contact,
                ':password' => $this->password,
                ':role' => $this->role,
                ':created_at' => date('Y-m-d H:i:s')
            ]);

            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
    public function IsDuplicateEmail($email)
    {
        try {
            $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM users 
            WHERE email = :email
        ");

            $stmt->execute([
                ':email' => $email
            ]);

            $count = $stmt->fetchColumn();

            return $count > 0;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}
?>
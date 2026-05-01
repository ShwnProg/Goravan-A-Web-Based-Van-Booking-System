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
    public $birthdate;
    public $role = 'user';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function AddUser()
    {
        try {
            $stmt0 = $this->conn->prepare("INSERT INTO $this->table (fullname,email,contact_number,password,role,created_at,birthdate)
                                           VALUES (:fullname,:email,:contact,:password,:role,:created_at,:birthdate)");
            $stmt0->execute([
                ':fullname' => $this->fullname,
                ':email' => $this->email,
                ':contact' => $this->contact,
                ':password' => $this->password,
                ':role' => $this->role,
                ':created_at' => date('Y-m-d H:i:s'),
                ':birthdate' => $this->birthdate
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
            FROM $this->table 
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

    public function AuthenticateUser()
    {
        try {
            $stmt = $this->conn->prepare("SELECT password FROM $this->table WHERE email = :email ");
            $stmt->execute([':email' => $this->email]);

            $user = $stmt->fetch();

            if ($user && password_verify($this->password, $user['password'])) {
                return true;
            }
            return 'Invalid Credentials';
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function GetRole()
    {
        try {
            $stmt = $this->conn->prepare("SELECT role from $this->table WHERE email = :email");

            $stmt->execute([':email' => $this->email]);

            $user = $stmt->fetch();
            return $user['role'];
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}
?>
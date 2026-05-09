<?php
class Users
{
    private $conn = null;
    private $table = 'users';
    public $id;
    public $first_name;
    public $last_name;
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
            $stmt0 = $this->conn->prepare("INSERT INTO $this->table (firstname,lastname,email,contact_number,password,role,created_at,birthdate)
                                           VALUES (:firstname,:lastname,:email,:contact,:password,:role,:created_at,:birthdate)");
            $stmt0->execute([
                ':firstname' => $this->first_name,
                ':lastname' => $this->last_name,
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
    public function GetUserById()
    {
        try {
            $stmt = $this->conn->prepare("SELECT user_id_pk, firstname, lastname, email, contact_number, birthdate,password FROM $this->table WHERE user_id_pk = :id");
            $stmt->execute([':id' => $this->id]);
            return $stmt->fetch();
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
            $stmt = $this->conn->prepare("SELECT user_id_pk,password FROM $this->table WHERE email = :email ");
            $stmt->execute([':email' => $this->email]);

            $user = $stmt->fetch();

            if ($user && password_verify($this->password, $user['password'])) {
                return ['is_login' => true, 'id' => $user['user_id_pk']];
            }
            return ['is_login' => false, 'id' => null, 'error' => 'Invalid credentials'];
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
    public function UpdateProfile()
    {
        try {
            $stmt = $this->conn->prepare("UPDATE $this->table SET firstname = :firstname, lastname = :lastname, email = :email, contact_number = :contact WHERE user_id_pk = :id");
            return $stmt->execute([
                ':firstname' => $this->first_name,
                ':lastname' => $this->last_name,
                ':email' => $this->email,
                ':contact' => $this->contact,
                ':id' => $this->id
            ]);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
    public function UpdatePassword()
    {
        try {
            $stmt = $this->conn->prepare("UPDATE $this->table SET password = :password WHERE user_id_pk = :id");
            return $stmt->execute([
                ':password' => $this->password,
                ':id' => $this->id
            ]);
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
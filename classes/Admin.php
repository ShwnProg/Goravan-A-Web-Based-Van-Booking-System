<?php
class Admin
{
    public $id;
    public $email;
    public $password;
    public $role = 'admin';
    protected $conn = null;
    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function AuthenticateAdmin()
    {
        try {
            $stmt = $this->conn->prepare("SELECT user_id_pk, password FROM users WHERE email = :email AND role = :role");
            $stmt->execute([
                ':email' => $this->email,
                ':role' => $this->role
            ]);

            $admin = $stmt->fetch();

            if ($admin && password_verify($this->password, $admin['password'])) {
                return ['is_login' => true, 'id' => $admin['user_id_pk']];
            }

            return ['is_login' => false, 'id' => null, 'error' => 'Invalid credentials'];

        } catch (PDOException $e) {
            return ['is_login' => false, 'id' => null, 'error' => $e->getMessage()];
        }
    }

    public function Read()
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id_pk = :id AND role = :role");
        $stmt->execute([
            ':id' => $this->id,
            ':role' => $this->role
        ]);

        return $stmt->fetch();
    }
}
?>
<?php

require_once __DIR__ . '/BaseDAO.php';

class UserDAO extends BaseDAO
{
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT id, username, email, admin, passwd_hash
                FROM users
                WHERE username = :username";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT id, username, email, admin
                FROM users
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function createUser(string $username, string $email, string $password, int $admin = 0): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (username, passwd_hash, email, admin)
                VALUES (:username, :passwd_hash, :email, :admin)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':username'    => $username,
            ':passwd_hash' => $hash,
            ':email'       => $email,
            ':admin'       => $admin,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
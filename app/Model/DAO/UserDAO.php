<?php

require_once __DIR__ . '/BaseDAO.php';

class UserDAO extends BaseDAO
{
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT id, username, email, admin, passwd_hash, logo
                FROM users
                WHERE username = :username";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT id, username, email, admin, passwd_hash, logo
                FROM users
                WHERE email = :email";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
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

    public function getAllUsers(): array
    {
        $sql = "SELECT id, username, email, points, admin
                FROM users
                ORDER BY username ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function deleteUserById(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    public function countUsers(string $search = ''): int
    {
        if ($search !== '') {
            $sql = "SELECT COUNT(*) AS total FROM users WHERE username LIKE :search";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':search' => '%' . $search . '%']);
        } else {
            $sql = "SELECT COUNT(*) AS total FROM users";
            $stmt = $this->db->query($sql);
        }
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getUsersPaginated(int $limit, int $offset, string $search = ''): array
    {
        if ($search !== '') {
            $sql = "SELECT id, username, email, points, admin
                    FROM users
                    WHERE username LIKE :search
                    ORDER BY username ASC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        } else {
            $sql = "SELECT id, username, email, points, admin
                    FROM users
                    ORDER BY username ASC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getUserById(int $id): ?array
    {
        $sql = "SELECT *
                FROM users
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function isUsernameTaken(string $username, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM users WHERE username = :username AND id <> :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':username' => $username,
            ':id'       => $excludeId,
        ]);
        return (bool)$stmt->fetch();
    }

    public function updateUserProfile(int $id, string $username, ?string $logo): bool
    {
        $sql = "UPDATE users
                SET username = :username,
                    logo     = :logo
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':username' => $username,
            ':logo'     => $logo,
            ':id'       => $id,
        ]);
    }

    public function updateUser(
        int $id,
        string $username,
        string $email,
        int $points,
        int $isAdmin
    ): bool {
        $sql = "UPDATE users
                SET username = :username,
                    email    = :email,
                    points   = :points,
                    admin    = :admin
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':points'   => $points,
            ':admin'    => $isAdmin,
            ':id'       => $id,
        ]);
    }

    public function addPoints(int $userId, int $points): bool
    {
        $sql = "UPDATE users
                SET points = points + :points
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':points' => $points,
            ':id' => $userId
        ]);
    }
}
<?php

require_once __DIR__ . '/BaseDAO.php';

class UserDAO extends BaseDAO
{
    public function findByUsername(string $username): ?array
    {
        // busco usuari pel seu username
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
        // busco usuari pel seu email
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
        // aquí faig el hash de la contrasenya abans de guardar-la
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

        // retorno l'id del nou usuari
        return (int) $this->db->lastInsertId();
    }

    public function getAllUsers(): array
    {
        // llista bàsica de tots els usuaris
        $sql = "SELECT id, username, email, points, admin
                FROM users
                ORDER BY username ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function deleteUserById(int $id): bool
    {
        // esborro un usuari per id
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    public function countUsers(string $search = ''): int
    {
        // compto usuaris, amb cerca opcional pel username
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
        // obtinc usuaris amb paginació i o sense filtre pel nom
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
        // usuari complet per id (serveix per perfil, admin, etc.)
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
        // comprovo si el username ja existeix, excloent un id concret (per edició)
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
        // actualitzo només username + logo per al perfil
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
        // update més complet, per l’admin (email, punts, rol)
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
        // suma punts al ranking de l’usuari (per les prediccions)
        $sql = "UPDATE users
                SET points = points + :points
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':points' => $points,
            ':id'     => $userId
        ]);
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $sql = "UPDATE users
                SET passwd_hash = :hash
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':hash' => $hash,
            ':id'   => $id,
        ]);
    }
}
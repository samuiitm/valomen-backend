<?php

require_once __DIR__ . '/BaseDAO.php';

class RememberTokenDAO extends BaseDAO
{
    public function createToken(int $userId, string $selector, string $hashedValidator, string $expiresAt): bool
    {
        // creo un nou registre de remember me per aquest usuari
        $sql = "INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at)
                VALUES (:user_id, :selector, :hashed_validator, :expires_at)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id'          => $userId,
            ':selector'         => $selector,
            ':hashed_validator' => $hashedValidator,
            ':expires_at'       => $expiresAt,
        ]);
    }

    public function findBySelector(string $selector): ?array
    {
        // busco un token pel seu selector (la part que va a la cookie)
        $sql = "SELECT * FROM remember_tokens WHERE selector = :selector LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':selector' => $selector]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deleteByUserId(int $userId): void
    {
        // esborro tots els tokens d’un usuari (per exemple quan fa login de nou)
        $sql = "DELETE FROM remember_tokens WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    public function deleteBySelector(string $selector): void
    {
        // esborro un token concret pel selector
        $sql = "DELETE FROM remember_tokens WHERE selector = :selector";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':selector' => $selector]);
    }

    public function deleteExpired(): void
    {
        // netejo els tokens que ja han caducat
        $sql = "DELETE FROM remember_tokens WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }
}
<?php

require_once __DIR__ . '/BaseDAO.php';

class PasswordResetTokenDAO extends BaseDAO
{
    public function createToken(int $userId, string $selector, string $hashedValidator, string $expiresAt): bool
    {
        // creo un nou token de reset per a l'usuari
        $sql = "INSERT INTO password_resets (user_id, selector, hashed_validator, expires_at)
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
        // busco un reset token pel selector
        $sql = "SELECT * FROM password_resets WHERE selector = :selector LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':selector' => $selector]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markAsUsed(int $id): bool
    {
        // marco el token com a utilitzat (així només val una vegada)
        $sql = "UPDATE password_resets SET used_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    public function deleteByUserId(int $userId): void
    {
        // esborro tokens antics quan genero un de nou
        $sql = "DELETE FROM password_resets WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    }

    public function deleteExpiredOrUsed(): void
    {
        // netejo tokens caducats o ja utilitzats
        $sql = "DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }
}
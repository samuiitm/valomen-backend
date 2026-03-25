<?php

require_once __DIR__ . '/BaseDAO.php';

class LoginAttemptDAO extends BaseDAO
{
    private int $limitMinutes = 15;

    public function getAttemptsByIp(string $ip): int
    {
        $sql = "SELECT attempts, last_attempt_at
                FROM login_attempts
                WHERE ip_address = :ip
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ip' => $ip]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return 0;
        }

        $lastAttempt = strtotime($row['last_attempt_at']);
        $limitTime   = time() - ($this->limitMinutes * 60);

        if ($lastAttempt < $limitTime) {
            return 0;
        }

        return (int)$row['attempts'];
    }

    public function registerFailedAttempt(string $ip): void
    {
        $sql = "SELECT id, attempts, last_attempt_at
                FROM login_attempts
                WHERE ip_address = :ip
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ip' => $ip]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $insert = "INSERT INTO login_attempts (ip_address, attempts, last_attempt_at)
                       VALUES (:ip, 1, NOW())";

            $stmt = $this->db->prepare($insert);
            $stmt->execute([':ip' => $ip]);
            return;
        }

        $lastAttempt = strtotime($row['last_attempt_at']);
        $limitTime   = time() - ($this->limitMinutes * 60);

        if ($lastAttempt < $limitTime) {
            $update = "UPDATE login_attempts
                       SET attempts = 1, last_attempt_at = NOW()
                       WHERE ip_address = :ip";
        } else {
            $update = "UPDATE login_attempts
                       SET attempts = attempts + 1, last_attempt_at = NOW()
                       WHERE ip_address = :ip";
        }

        $stmt = $this->db->prepare($update);
        $stmt->execute([':ip' => $ip]);
    }

    public function clearAttemptsByIp(string $ip): void
    {
        $sql = "DELETE FROM login_attempts WHERE ip_address = :ip";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ip' => $ip]);
    }

    public function deleteOldAttempts(): void
    {
        $sql = "DELETE FROM login_attempts
                WHERE last_attempt_at < DATE_SUB(NOW(), INTERVAL :minutes MINUTE)";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':minutes', $this->limitMinutes, PDO::PARAM_INT);
        $stmt->execute();
    }
}
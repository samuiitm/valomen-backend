<?php

class OAuthIdentityDAO
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByProviderUser(string $provider, string $providerUserId): ?array
    {
        $sql = "SELECT * FROM oauth_identities WHERE provider = :p AND provider_user_id = :pid LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':p' => $provider,
            ':pid' => $providerUserId,
        ]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createIdentity(int $userId, string $provider, string $providerUserId, ?string $email): void
    {
        $sql = "INSERT INTO oauth_identities (user_id, provider, provider_user_id, email)
                VALUES (:uid, :p, :pid, :email)";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':uid' => $userId,
            ':p' => $provider,
            ':pid' => $providerUserId,
            ':email' => $email,
        ]);
    }
}
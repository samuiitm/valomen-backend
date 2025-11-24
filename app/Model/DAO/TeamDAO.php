<?php

require_once __DIR__ . '/BaseDAO.php';

class TeamDAO extends BaseDAO
{
    public function createTeam(string $name, string $country): bool
    {
        $sql = "INSERT INTO teams (name, country) VALUES (:name, :country)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name'    => $name,
            ':country' => $country,
        ]);
    }


    public function getAllTeams(): array
    {
        $sql = "SELECT id, name, country
                FROM teams
                ORDER BY name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function deleteTeamById(int $id): bool
    {
        $sql = "DELETE FROM teams WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getTeamsByEvent(int $eventId): array
    {
        $sql = "SELECT t.*
                FROM teams t
                JOIN event_teams et ON t.id = et.team_id
                WHERE et.event_id = :event_id
                ORDER BY t.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll();
    }

    public function teamBelongsToEvent(int $teamId, int $eventId): bool
    {
        $sql = "SELECT 1
                FROM event_teams
                WHERE event_id = :event_id
                  AND team_id  = :team_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':event_id' => $eventId,
            ':team_id'  => $teamId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    public function countTeams(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM teams";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getTeamsPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT id, name, country
                FROM teams
                ORDER BY name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getTeamById(int $id): ?array
    {
        $sql = "SELECT id, name, country
                FROM teams
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateTeam(int $id, string $name, string $country): bool
    {
        $sql = "UPDATE teams
                SET name    = :name,
                    country = :country
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name'    => $name,
            ':country' => $country,
            ':id'      => $id,
        ]);
    }
}
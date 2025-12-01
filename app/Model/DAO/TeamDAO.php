<?php

require_once __DIR__ . '/BaseDAO.php';

class TeamDAO extends BaseDAO
{
    public function createTeam(string $name, string $country): bool
    {
        // creo un nou equip amb nom i país
        $sql = "INSERT INTO teams (name, country) VALUES (:name, :country)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name'    => $name,
            ':country' => $country,
        ]);
    }

    public function getAllTeams(): array
    {
        // tots els equips ordenats alfabèticament
        $sql = "SELECT id, name, country
                FROM teams
                ORDER BY name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function deleteTeamById(int $id): bool
    {
        // intento eliminar un equip per id
        $sql = "DELETE FROM teams WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            // si hi ha FK o alguna història, retorno false
            return false;
        }
    }

    public function getTeamsByEvent(int $eventId): array
    {
        // equips que estan associats a un event concret
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
        // comprovo si un equip forma part d’un event
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

    public function countTeams(string $search = ''): int
    {
        // compto equips, amb filtre pel nom si hi ha cerca
        if ($search !== '') {
            $sql = "SELECT COUNT(*) AS total FROM teams WHERE name LIKE :search";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':search' => '%' . $search . '%']);
        } else {
            $sql = "SELECT COUNT(*) AS total FROM teams";
            $stmt = $this->db->query($sql);
        }
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getTeamsPaginated(int $limit, int $offset, string $search = ''): array
    {
        // obtinc equips amb paginació i opcionalment cerca pel nom
        if ($search !== '') {
            $sql = "SELECT id, name, country
                    FROM teams
                    WHERE name LIKE :search
                    ORDER BY name ASC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        } else {
            $sql = "SELECT id, name, country
                    FROM teams
                    ORDER BY name ASC
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getTeamById(int $id): ?array
    {
        // un equip concret per id
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
        // faig update del nom i país d’un equip
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
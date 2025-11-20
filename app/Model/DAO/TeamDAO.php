<?php

require_once __DIR__ . '/BaseDAO.php';

class TeamDAO extends BaseDAO
{
    public function getTeamsByEvent(int $eventId): array
    {
        $sql = "SELECT t.*
                FROM teams t
                JOIN event_teams et ON et.team_id = t.id
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
                WHERE event_id = :event_id AND team_id = :team_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':event_id' => $eventId,
            ':team_id'  => $teamId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
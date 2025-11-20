<?php

require_once __DIR__ . '/BaseDAO.php';

class EventDAO extends BaseDAO
{

    public function getAllEventsForSelect(): array
    {
        $sql = "SELECT id, name, status
                FROM events
                WHERE LOWER(status) <> 'completed'
                ORDER BY start_date DESC, name ASC";
                

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getOngoingEvents(): array
    {
        $sql = "SELECT 
                    e.id,
                    e.name,
                    e.start_date,
                    e.end_date,
                    e.status,
                    e.prize,
                    e.region,
                    e.logo
                FROM events e
                WHERE e.status = 'Ongoing'
                ORDER BY e.start_date ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getUpcomingEvents(): array
    {
        $sql = "SELECT 
                    e.id,
                    e.name,
                    e.start_date,
                    e.end_date,
                    e.status,
                    e.prize,
                    e.region,
                    e.logo
                FROM events e
                WHERE e.status = 'Upcoming'
                ORDER BY e.start_date ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getCompletedEvents(): array
    {
        $sql = "SELECT 
                    e.id,
                    e.name,
                    e.start_date,
                    e.end_date,
                    e.status,
                    e.prize,
                    e.region,
                    e.logo
                FROM events e
                WHERE e.status = 'Completed'
                ORDER BY e.start_date DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function countCurrentEvents(): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM events
                WHERE LOWER(status) IN ('upcoming','ongoing')";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getCurrentEventsPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT e.*
                FROM events e
                WHERE LOWER(e.status) IN ('upcoming','ongoing')
                ORDER BY
                    CASE
                        WHEN LOWER(e.status) = 'ongoing' THEN 1
                        ELSE 2
                    END,
                    e.start_date ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countCompletedEvents(): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM events
                WHERE LOWER(status) = 'completed'";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return (int)($row['total' ?? 0] ?? 0);
    }

    public function getCompletedEventsPaginated(int $limit, int $offset): array
    {
        $sql = "SELECT e.*
                FROM events e
                WHERE LOWER(e.status) = 'completed'
                ORDER BY e.start_date DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

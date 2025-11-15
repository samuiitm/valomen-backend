<?php

require_once __DIR__ . '/BaseDAO.php';

class EventDAO extends BaseDAO
{
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
}

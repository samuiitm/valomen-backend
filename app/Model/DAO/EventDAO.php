<?php

require_once __DIR__ . '/BaseDAO.php';

class EventDAO extends BaseDAO
{
    public function getAllEventsForSelect(): array
    {
        // agafo tots els events només amb id i nom per omplir selects
        $sql = "SELECT id, name
                FROM events
                ORDER BY start_date DESC, name ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getEventById(int $id): ?array
    {
        // busco un event concret per id
        $sql = "SELECT *
                FROM events
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        // si no trobo res, retorno null
        return $row ?: null;
    }

    public function createEvent(
        string $name,
        string $startDate,
        ?string $endDate,
        ?string $status,
        ?int $prize,
        string $region,
        string $logo,
        ?int $postAuthor
    ): int {
        // inserto un event nou a la taula events
        $sql = "INSERT INTO events (
                    name,
                    start_date,
                    end_date,
                    status,
                    prize,
                    region,
                    logo,
                    post_author
                ) VALUES (
                    :name,
                    :start_date,
                    :end_date,
                    :status,
                    :prize,
                    :region,
                    :logo,
                    :post_author
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name'        => $name,
            ':start_date'  => $startDate,
            ':end_date'    => $endDate,
            ':status'      => $status,
            ':prize'       => $prize,
            ':region'      => $region,
            ':logo'        => $logo,
            ':post_author' => $postAuthor,
        ]);

        // retorno l'últim id inserit
        return (int)$this->db->lastInsertId();
    }

    public function updateEvent(
        int $id,
        string $name,
        string $startDate,
        ?string $endDate,
        ?string $status,
        ?int $prize,
        string $region,
        string $logo
    ): bool {
        // faig un update de totes les dades de l'event
        $sql = "UPDATE events
                SET name       = :name,
                    start_date = :start_date,
                    end_date   = :end_date,
                    status     = :status,
                    prize      = :prize,
                    region     = :region,
                    logo       = :logo
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name'       => $name,
            ':start_date' => $startDate,
            ':end_date'   => $endDate,
            ':status'     => $status,
            ':prize'      => $prize,
            ':region'     => $region,
            ':logo'       => $logo,
            ':id'         => $id,
        ]);
    }

    public function deleteEventById(int $id): bool
    {
        // elimino un event per id
        $sql = "DELETE FROM events WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    public function getTeamIdsForEvent(int $eventId): array
    {
        // agafo tots els team_id que estan associats a l'event
        $sql = "SELECT team_id
                FROM event_teams
                WHERE event_id = :event_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':event_id' => $eventId]);
        $rows = $stmt->fetchAll();

        // retorno només els ids en un array d'enters
        return array_map(fn($row) => (int)$row['team_id'], $rows);
    }

    public function setEventTeams(int $eventId, array $teamIds): void
    {
        // faig la operació en una transacció per si falla alguna cosa
        $this->db->beginTransaction();

        // primer esborro tots els equips d'aquest event
        $sqlDelete = "DELETE FROM event_teams WHERE event_id = :event_id";
        $stmtDel   = $this->db->prepare($sqlDelete);
        $stmtDel->execute([':event_id' => $eventId]);

        // i després torno a inserir els que m'han passat
        if (!empty($teamIds)) {
            $sqlIns = "INSERT INTO event_teams (event_id, team_id)
                       VALUES (:event_id, :team_id)";
            $stmtIns = $this->db->prepare($sqlIns);

            foreach ($teamIds as $teamId) {
                $stmtIns->execute([
                    ':event_id' => $eventId,
                    ':team_id'  => (int)$teamId,
                ]);
            }
        }

        $this->db->commit();
    }

    public function getOngoingEvents(): array
    {
        // tots els events que estan en estat Ongoing
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
        // tots els events en estat Upcoming
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
        // tots els events ja completats
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

    public function countCurrentEvents(?string $search = null): int
    {
        // compte d'events (Upcoming + Ongoing), amb o sense cerca
        if ($search !== null && $search !== '') {
            $sql = "SELECT COUNT(*) AS total
                    FROM events
                    WHERE LOWER(status) IN ('upcoming','ongoing')
                    AND name LIKE :search";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':search' => '%' . $search . '%',
            ]);
            $row = $stmt->fetch();
            return (int)($row['total'] ?? 0);
        }

        $sql = "SELECT COUNT(*) AS total
                FROM events
                WHERE LOWER(status) IN ('upcoming','ongoing')";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getCurrentEventsPaginated(
        int $limit,
        int $offset,
        string $order,
        ?string $search = null
    ): array {
        // ordre per data, però posant els Ongoing primer
        if ($order === 'date_desc') {
            $orderBy = "CASE WHEN LOWER(e.status) = 'ongoing' THEN 1 ELSE 2 END,
                        e.start_date DESC,
                        e.id DESC";
        } else {
            $orderBy = "CASE WHEN LOWER(e.status) = 'ongoing' THEN 1 ELSE 2 END,
                        e.start_date ASC,
                        e.id ASC";
        }

        $sql = "SELECT e.*
                FROM events e
                WHERE LOWER(e.status) IN ('upcoming','ongoing')";

        $params = [];

        // si hi ha cerca, afegeixo el LIKE
        if ($search !== null && $search !== '') {
            $sql .= " AND e.name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countCompletedEvents(?string $search = null): int
    {
        // compta events completats, amb filtre de cerca opcional
        if ($search !== null && $search !== '') {
            $sql = "SELECT COUNT(*) AS total
                    FROM events
                    WHERE LOWER(status) = 'completed'
                    AND name LIKE :search";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':search' => '%' . $search . '%',
            ]);
            $row = $stmt->fetch();
            return (int)($row['total'] ?? 0);
        }

        $sql = "SELECT COUNT(*) AS total
                FROM events
                WHERE LOWER(status) = 'completed'";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    public function getCompletedEventsPaginated(
        int $limit,
        int $offset,
        string $order,
        ?string $search = null
    ): array {
        // ordre per data pels completats
        if ($order === 'date_desc') {
            $orderBy = 'e.start_date DESC, e.id DESC';
        } else {
            $orderBy = 'e.start_date ASC, e.id ASC';
        }

        $sql = "SELECT e.*
                FROM events e
                WHERE LOWER(e.status) = 'completed'";

        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= " AND e.name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function searchCurrentEvents(string $term): array
    {
        // cerca d'events actuals (Upcoming/Ongoing) pel nom
        $like = '%' . $term . '%';

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
                WHERE LOWER(e.status) IN ('upcoming','ongoing')
                  AND e.name LIKE :search
                ORDER BY
                    CASE
                        WHEN LOWER(e.status) = 'ongoing' THEN 1
                        ELSE 2
                    END,
                    e.start_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':search' => $like]);
        return $stmt->fetchAll();
    }

    public function searchCompletedEvents(string $term): array
    {
        // cerca d'events completats pel nom
        $like = '%' . $term . '%';

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
                WHERE LOWER(e.status) = 'completed'
                  AND e.name LIKE :search
                ORDER BY e.start_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':search' => $like]);
        return $stmt->fetchAll();
    }
}
<?php

require_once __DIR__ . '/BaseDAO.php';

class MatchDAO extends BaseDAO
{
  public function updateMatchStatuses(): void
  {
    $now = new DateTime('2025-11-13 13:00:00');

    $sql = "SELECT id, date, hour, score_team_1, score_team_2, status FROM matches";
    $stmt = $this->db->query($sql);
    $matches = $stmt->fetchAll();

    $update = $this->db->prepare("UPDATE matches SET status = :status WHERE id = :id");

    foreach ($matches as $match) {
      if ($match['score_team_1'] !== null && $match['score_team_2'] !== null) {
        $newStatus = 'completed';
      } else {
        $matchDateTime = new DateTime($match['date'] . ' ' . $match['hour']);
        if ($matchDateTime > $now) {
          $newStatus = 'upcoming';
        } else {
          $newStatus = 'live';
        }
      }

      if ($match['status'] !== $newStatus) {
        $update->execute([
          ':status' => $newStatus,
          ':id' => $match['id'],
        ]);
      }
    }
  }

  public function getUpcomingMatches(): array
  {
    $sql = "SELECT m.*, 
                      t1.name AS team_1_name, t1.country AS team_1_country,
                      t2.name AS team_2_name, t2.country AS team_2_country,
                      e.name AS event_name, e.logo AS event_logo
              FROM matches m
              JOIN teams t1 ON m.team_1 = t1.id
              LEFT JOIN teams t2 ON m.team_2 = t2.id
              JOIN events e ON m.event_id = e.id
              WHERE m.score_team_1 IS NULL
                AND m.score_team_2 IS NULL
              ORDER BY m.date ASC, m.hour ASC";

    $stmt = $this->db->query($sql);
    return $stmt->fetchAll();
  }

  public function getCompletedMatches(): array
  {
    $sql = "SELECT m.*, 
                      t1.name AS team_1_name, t1.country AS team_1_country,
                      t2.name AS team_2_name, t2.country AS team_2_country,
                      e.name AS event_name, e.logo AS event_logo
              FROM matches m
              JOIN teams t1 ON m.team_1 = t1.id
              LEFT JOIN teams t2 ON m.team_2 = t2.id
              JOIN events e ON m.event_id = e.id
              WHERE m.score_team_1 IS NOT NULL
                AND m.score_team_2 IS NOT NULL
              ORDER BY m.date DESC, m.hour DESC";

    $stmt = $this->db->query($sql);
    return $stmt->fetchAll();
  }

  public function getMatchById(int $id): ?array
  {
    $sql = "SELECT m.*,
                    t1.name AS team_1_name, t1.country AS team_1_country,
                    t2.name AS team_2_name, t2.country AS team_2_country,
                    e.name AS event_name, e.logo AS event_logo
              FROM matches m
              JOIN teams t1 ON m.team_1 = t1.id
              LEFT JOIN teams t2 ON m.team_2 = t2.id
              JOIN events e ON m.event_id = e.id
              WHERE m.id = :id";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $match = $stmt->fetch();
    return $match ?: null;
  }

  public function countUpcomingMatches(): int
  {
      $sql = "SELECT COUNT(*) AS total FROM matches
              WHERE status = 'Upcoming' || status = 'Live'";
      $stmt = $this->db->query($sql);
      $r = $stmt->fetch();
      return (int)($r['total'] ?? 0);
  }

  public function getUpcomingMatchesPaginated(int $limit, int $offset): array
  {
      $sql = "SELECT m.*,
                    t1.name AS team_1_name, t1.country AS team_1_country,
                    t2.name AS team_2_name, t2.country AS team_2_country, m.status,
                    e.name AS event_name, e.logo AS event_logo
              FROM matches m
              JOIN teams t1 ON m.team_1 = t1.id
              LEFT JOIN teams t2 ON m.team_2 = t2.id
              JOIN events e ON m.event_id = e.id
              WHERE m.status = 'Upcoming' OR m.status = 'Live'
              ORDER BY m.date ASC, m.hour ASC
              LIMIT :limit OFFSET :offset";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
      $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll();
  }

  public function countCompletedMatches(): int
  {
      $sql = "SELECT COUNT(*) AS total FROM matches
              WHERE status = 'Completed'";
      $stmt = $this->db->query($sql);
      $r = $stmt->fetch();
      return (int)($r['total'] ?? 0);
  }

  public function getCompletedMatchesPaginated(int $limit, int $offset): array
  {
      $sql = "SELECT m.*,
                    t1.name AS team_1_name, t1.country AS team_1_country,
                    t2.name AS team_2_name, t2.country AS team_2_country,
                    e.name AS event_name, e.logo AS event_logo
              FROM matches m
              JOIN teams t1 ON m.team_1 = t1.id
              LEFT JOIN teams t2 ON m.team_2 = t2.id
              JOIN events e ON m.event_id = e.id
              WHERE m.status = 'Completed'
              ORDER BY m.date DESC, m.hour DESC
              LIMIT :limit OFFSET :offset";
      $stmt = $this->db->prepare($sql);
      $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
      $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();
      return $stmt->fetchAll();
  }

  public function createMatch(
      int $team1Id,
      int $team2Id,
      ?int $scoreTeam1,
      ?int $scoreTeam2,
      string $date,
      string $hour,
      ?string $status,
      int $bestOf,
      string $eventStage,
      int $eventId,
      ?int $postAuthor
  ): bool {
      $sql = "INSERT INTO matches (
                  team_1,
                  team_2,
                  score_team_1,
                  score_team_2,
                  date,
                  hour,
                  status,
                  best_of,
                  event_stage,
                  event_id,
                  post_author
              ) VALUES (
                  :team_1,
                  :team_2,
                  :score_team_1,
                  :score_team_2,
                  :date,
                  :hour,
                  :status,
                  :best_of,
                  :event_stage,
                  :event_id,
                  :post_author
              )";

      $stmt = $this->db->prepare($sql);

      return $stmt->execute([
          ':team_1'       => $team1Id,
          ':team_2'       => $team2Id,
          ':score_team_1' => $scoreTeam1,
          ':score_team_2' => $scoreTeam2,
          ':date'         => $date,
          ':hour'         => $hour,
          ':status'       => $status,
          ':best_of'      => $bestOf,
          ':event_stage'  => $eventStage,
          ':event_id'     => $eventId,
          ':post_author'  => $postAuthor,
      ]);
  }
}
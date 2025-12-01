<?php

require_once __DIR__ . '/BaseDAO.php';

class PredictionDAO extends BaseDAO
{
    public function createPrediction(int $userId, int $matchId, int $score1, int $score2): bool
    {
        // inserto una predicció nova per un usuari i un partit
        $sql = "INSERT INTO predictions
                    (user_id, match_id, score_team_1_pred, score_team_2_pred)
                VALUES (:user_id, :match_id, :score1, :score2)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':user_id' => $userId,
            ':match_id' => $matchId,
            ':score1' => $score1,
            ':score2' => $score2,
        ]);
    }

    public function getPredictionForUserAndMatch(int $userId, int $matchId): ?array
    {
        // agafo una predicció concreta d'un usuari per un partit
        $sql = "SELECT * FROM predictions
                WHERE user_id = :user_id AND match_id = :match_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'  => $userId,
            ':match_id' => $matchId,
        ]);

        $prediction = $stmt->fetch();
        return $prediction ?: null;
    }

    public function getPredictionsByMatch(int $matchId): array
    {
        // totes les prediccions d'un partit
        $sql = "SELECT *
                FROM predictions
                WHERE match_id = :match_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':match_id' => $matchId]);
        return $stmt->fetchAll();
    }

    public function updatePredictionPoints(int $userId, int $matchId, int $points): bool
    {
        // guardo els punts que s'han donat a una predicció
        $sql = "UPDATE predictions
                SET points_awarded = :points
                WHERE user_id = :user_id AND match_id = :match_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':points' => $points,
            ':user_id' => $userId,
            ':match_id' => $matchId,
        ]);
    }

    public function getPredictionsByUser(int $userId): array
    {
        // totes les prediccions d'un usuari amb info del partit i equips
        $sql = "SELECT 
                    p.*,
                    m.date,
                    m.hour,
                    m.score_team_1 AS score_team_1_real,
                    m.score_team_2 AS score_team_2_real,
                    m.status AS match_status,
                    t1.name AS team_1_name,
                    t2.name AS team_2_name
                FROM predictions p
                JOIN matches m ON p.match_id = m.id
                JOIN teams t1 ON m.team_1 = t1.id
                LEFT JOIN teams t2 ON m.team_2 = t2.id
                WHERE p.user_id = :user_id
                ORDER BY m.date DESC, m.hour DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function userHasPrediction(int $userId, int $matchId): bool
    {
        // miro si aquest usuari ja té una predicció per aquest partit
        $sql = "SELECT 1 FROM predictions
                WHERE user_id = :user_id AND match_id = :match_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'  => $userId,
            ':match_id' => $matchId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function updatePrediction(int $userId, int $matchId, int $score1, int $score2): bool
    {
        // update del marcador predit
        $sql = "UPDATE predictions
                SET score_team_1_pred = :score1,
                    score_team_2_pred = :score2
                WHERE user_id = :user_id AND match_id = :match_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':score1'   => $score1,
            ':score2'   => $score2,
            ':user_id'  => $userId,
            ':match_id' => $matchId,
        ]);
    }

    public function deletePrediction(int $matchId, int $userId): bool
    {
        // elimino una predicció concreta
        $sql = "DELETE FROM predictions
                WHERE user_id = :user_id AND match_id = :match_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id'  => $userId,
            ':match_id' => $matchId,
        ]);
    }
}
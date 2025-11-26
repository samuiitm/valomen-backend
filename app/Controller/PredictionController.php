<?php

require_once __DIR__ . '/../Model/DAO/MatchDAO.php';
require_once __DIR__ . '/../Model/DAO/PredictionDAO.php';

class PredictionController
{
    private PDO $db;
    private MatchDAO $matchDao;
    private PredictionDAO $predictionDao;

    public function __construct(PDO $db)
    {
        $this->db            = $db;
        $this->matchDao      = new MatchDAO($db);
        $this->predictionDao = new PredictionDAO($db);
    }

    private function getMatchOrRedirect(int $matchId): array
    {
        $match = $this->matchDao->getMatchById($matchId);
        if (!$match) {
            header('Location: index.php?page=matches');
            exit;
        }
        return $match;
    }

    public function showForm(int $matchId, int $userId): array
    {
        $match              = $this->getMatchOrRedirect($matchId);
        $existingPrediction = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);

        return [
            'match'              => $match,
            'existingPrediction' => $existingPrediction,
            'errors'             => [
                'global' => '',
            ],
            'success'            => false,
        ];
    }

    public function savePrediction(int $matchId, int $userId): array
    {
        $match   = $this->getMatchOrRedirect($matchId);
        $errors  = ['global' => ''];
        $success = false;

        $status       = strtolower($match['status'] ?? '');
        $isUpcoming   = $status === 'upcoming';
        $team1Exists  = !empty($match['team_1']);
        $team2Exists  = !empty($match['team_2']);
        $teamsDefined = $team1Exists && $team2Exists;

        if (!$isUpcoming || !$teamsDefined) {
            $errors['global'] = 'Predictions for this match are closed.';
        } else {
            $score1 = isset($_POST['score_team_1']) ? (int)$_POST['score_team_1'] : -1;
            $score2 = isset($_POST['score_team_2']) ? (int)$_POST['score_team_2'] : -1;

            if ($score1 < 0 || $score2 < 0) {
                $errors['global'] = 'Invalid score submitted.';
            } else {
                $bestOf = (int)($match['best_of'] ?? 3);
                $max    = max($score1, $score2);
                $min    = min($score1, $score2);

                if ($bestOf === 1) {
                    if (!(($score1 === 1 && $score2 === 0) || ($score1 === 0 && $score2 === 1))) {
                        $errors['global'] = 'For BO1 the score must be 1-0 or 0-1.';
                    }
                } elseif ($bestOf === 3) {
                    if (!($max === 2 && $min >= 0 && $min <= 1)) {
                        $errors['global'] = 'For BO3 the winner must have 2 maps and the loser 0 or 1.';
                    }
                } elseif ($bestOf === 5) {
                    if (!($max === 3 && $min >= 0 && $min <= 2)) {
                        $errors['global'] = 'For BO5 the winner must have 3 maps and the loser 0, 1 or 2.';
                    }
                }
            }

            if ($errors['global'] === '') {
                $existing = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);

                if ($existing) {
                    $this->predictionDao->updatePrediction($userId, $matchId, $score1, $score2);
                } else {
                    $this->predictionDao->createPrediction($userId, $matchId, $score1, $score2);
                }

                $success = true;
            }
        }

        $existingPrediction = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);

        return [
            'match'              => $match,
            'existingPrediction' => $existingPrediction,
            'errors'             => $errors,
            'success'            => $success,
        ];
    }

    public function deletePrediction(int $matchId, int $userId): bool
    {
        $match = $this->getMatchOrRedirect($matchId);

        $status     = strtolower($match['status'] ?? '');
        $isUpcoming = $status === 'upcoming';

        if (!$isUpcoming) {
            return false;
        }

        $existing = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);
        if ($existing) {
            $this->predictionDao->deletePrediction($matchId, $userId);
            return true;
        }

        return false;
    }

    public function processMatchResult(array $match): void
    {
        $matchId = (int)$match['id'];
        $bo      = (int)$match['best_of'];
        $real1   = (int)$match['score_team_1'];
        $real2   = (int)$match['score_team_2'];

        require_once __DIR__ . '/../Model/DAO/PredictionDAO.php';
        require_once __DIR__ . '/../Model/DAO/UserDAO.php';

        $predictionDao = new PredictionDAO($this->db);
        $userDao       = new UserDAO($this->db);

        $predictions = $predictionDao->getPredictionsByMatch($matchId);

        foreach ($predictions as $p) {
            $userId = (int)$p['user_id'];
            $pred1  = (int)$p['score_team_1_pred'];
            $pred2  = (int)$p['score_team_2_pred'];

            $points = 0;

            $realWinner = $real1 > $real2 ? 1 : 2;
            $predWinner = $pred1 > $pred2 ? 1 : 2;

            if ($realWinner === $predWinner) {
                $points += 5;

                if ($real1 === $pred1 && $real2 === $pred2) {
                    if ($bo === 3) {
                        $points += 2;
                    } elseif ($bo === 5) {
                        $points += 5;
                    }
                }
            }

            $predictionDao->updatePredictionPoints($userId, $matchId, $points);

            if ($points > 0) {
                $userDao->addPoints($userId, $points);
            }
        }
    }

}
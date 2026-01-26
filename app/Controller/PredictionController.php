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
        // guardo la connexió per després
        $this->db            = $db;
        // inicialitzo els DAO que necessito aquí
        $this->matchDao      = new MatchDAO($db);
        $this->predictionDao = new PredictionDAO($db);
    }

    private function getMatchOrRedirect(int $matchId): array
    {
        $match = $this->matchDao->getMatchById($matchId);
        if (!$match) {
            redirect_to('matches');
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

    public function showPredictFormAction(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect_to('login');
            exit;
        }

        if (empty($_GET['match_id']) || !ctype_digit($_GET['match_id'])) {
            redirect_to('matches');
            exit;
        }

        $matchId = (int)$_GET['match_id'];
        $userId  = (int)$_SESSION['user_id'];

        $data = $this->showForm($matchId, $userId);

        $match              = $data['match'];
        $existingPrediction = $data['existingPrediction'];
        $errors             = $data['errors'];
        $success            = $data['success'];

        $pageTitle = 'Valomen.gg | Make prediction';
        $pageCss   = 'prediction_form.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/prediction_form.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function savePredictAction(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect_to('login');
            exit;
        }

        if (empty($_GET['match_id']) || !ctype_digit($_GET['match_id'])) {
            redirect_to('matches');
            exit;
        }

        $matchId = (int)$_GET['match_id'];
        $userId  = (int)$_SESSION['user_id'];

        $data = $this->savePrediction($matchId, $userId);

        $match              = $data['match'];
        $existingPrediction = $data['existingPrediction'];
        $errors             = $data['errors'];
        $success            = $data['success'];

        $pageTitle = 'Valomen.gg | Make prediction';
        $pageCss   = 'prediction_form.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/prediction_form.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function myPredictionsAction(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect_to('login');
            exit;
        }

        $predictionDao   = new PredictionDAO($this->db);
        $userPredictions = $predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);

        $predictionsByDate = [];
        foreach ($userPredictions as $prediction) {
            $predictionsByDate[$prediction['date']][] = $prediction;
        }

        $pageTitle = 'Valomen.gg | My Predictions';
        $pageCss   = 'my_predictions.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/my_predictions.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function deletePredictionAction(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect_to('login');
            exit;
        }

        $matchId = filter_input(
            INPUT_GET,
            'match_id',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 0, 'min_range' => 1]]
        );

        if ($matchId === 0) {
            redirect_to('my_predictions');
            exit;
        }

        $this->deletePrediction($matchId, (int)$_SESSION['user_id']);

        redirect_to('my_predictions');
        exit;
    }
}
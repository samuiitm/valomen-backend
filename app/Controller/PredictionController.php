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
        // busco el partit per id
        $match = $this->matchDao->getMatchById($matchId);
        if (!$match) {
            // si no existeix, envio a la pàgina de partits
            header('Location: index.php?page=matches');
            exit;
        }
        // retorno el partit perquè altres funcions el fan servir
        return $match;
    }

    public function showForm(int $matchId, int $userId): array
    {
        // comprovo que el partit existeix
        $match              = $this->getMatchOrRedirect($matchId);
        // miro si aquest usuari ja té una predicció feta
        $existingPrediction = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);

        // retorno tot el que la vista necessita
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
        // primer comprovo que el partit existeix
        $match   = $this->getMatchOrRedirect($matchId);
        $errors  = ['global' => ''];
        $success = false;

        // agafo l'status del partit i el poso en minúscules
        $status       = strtolower($match['status'] ?? '');
        $isUpcoming   = $status === 'upcoming';    // només deixo predir si és Upcoming
        $team1Exists  = !empty($match['team_1']);
        $team2Exists  = !empty($match['team_2']);
        $teamsDefined = $team1Exists && $team2Exists; // els dos equips han d'existir

        // si el partit no és Upcoming o falten equips, no deixo fer predicció
        if (!$isUpcoming || !$teamsDefined) {
            $errors['global'] = 'Predictions for this match are closed.';
        } else {
            // llegeixo el marcador que ha posat l'usuari
            $score1 = isset($_POST['score_team_1']) ? (int)$_POST['score_team_1'] : -1;
            $score2 = isset($_POST['score_team_2']) ? (int)$_POST['score_team_2'] : -1;

            // si posa valors negatius, és invàlid
            if ($score1 < 0 || $score2 < 0) {
                $errors['global'] = 'Invalid score submitted.';
            } else {
                // comprovo que el marcador té sentit segons el BO del partit
                $bestOf = (int)($match['best_of'] ?? 3);
                $max    = max($score1, $score2);
                $min    = min($score1, $score2);

                if ($bestOf === 1) {
                    // BO1 → només 1-0 o 0-1
                    if (!(($score1 === 1 && $score2 === 0) || ($score1 === 0 && $score2 === 1))) {
                        $errors['global'] = 'For BO1 the score must be 1-0 or 0-1.';
                    }
                } elseif ($bestOf === 3) {
                    // BO3 → guanyador amb 2 i perdedor amb 0 o 1
                    if (!($max === 2 && $min >= 0 && $min <= 1)) {
                        $errors['global'] = 'For BO3 the winner must have 2 maps and the loser 0 or 1.';
                    }
                } elseif ($bestOf === 5) {
                    // BO5 → guanyador amb 3 i perdedor amb 0, 1 o 2
                    if (!($max === 3 && $min >= 0 && $min <= 2)) {
                        $errors['global'] = 'For BO5 the winner must have 3 maps and the loser 0, 1 or 2.';
                    }
                }
            }

            // si no hi ha cap error global, guardo la predicció
            if ($errors['global'] === '') {
                // miro si ja hi havia una predicció prèvia d'aquest usuari
                $existing = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);

                if ($existing) {
                    // si existeix → faig update
                    $this->predictionDao->updatePrediction($userId, $matchId, $score1, $score2);
                } else {
                    // sinó → creo una predicció nova
                    $this->predictionDao->createPrediction($userId, $matchId, $score1, $score2);
                }

                $success = true;
            }
        }

        // torno a carregar la predicció (per si s'ha actualitzat)
        $existingPrediction = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);

        // retorno dades per la vista de prediccions
        return [
            'match'              => $match,
            'existingPrediction' => $existingPrediction,
            'errors'             => $errors,
            'success'            => $success,
        ];
    }

    public function deletePrediction(int $matchId, int $userId): bool
    {
        // primer comprovo que el partit existeix
        $match = $this->getMatchOrRedirect($matchId);

        // només deixo esborrar prediccions si el partit encara és Upcoming
        $status     = strtolower($match['status'] ?? '');
        $isUpcoming = $status === 'upcoming';

        if (!$isUpcoming) {
            return false;
        }

        // miro si hi ha una predicció d'aquest usuari per aquest partit
        $existing = $this->predictionDao->getPredictionForUserAndMatch($userId, $matchId);
        if ($existing) {
            // si existeix, la borro
            $this->predictionDao->deletePrediction($matchId, $userId);
            return true;
        }

        // si no hi havia res, retorno false igualment
        return false;
    }

    public function processMatchResult(array $match): void
    {
        // agafem les dades necessàries del partit ja completat
        $matchId = (int)$match['id'];
        $bo      = (int)$match['best_of'];
        $real1   = (int)$match['score_team_1'];
        $real2   = (int)$match['score_team_2'];

        // torno a carregar els DAO aquí (també els podria usar els de la classe)
        require_once __DIR__ . '/../Model/DAO/PredictionDAO.php';
        require_once __DIR__ . '/../Model/DAO/UserDAO.php';

        $predictionDao = new PredictionDAO($this->db);
        $userDao       = new UserDAO($this->db);

        // agafo totes les prediccions d'aquest partit
        $predictions = $predictionDao->getPredictionsByMatch($matchId);

        foreach ($predictions as $p) {
            // usuari que ha fet la predicció
            $userId = (int)$p['user_id'];
            // marcador que havia posat aquest usuari
            $pred1  = (int)$p['score_team_1_pred'];
            $pred2  = (int)$p['score_team_2_pred'];

            // comencem amb 0 punts
            $points = 0;

            // calculo guanyador real i guanyador de la predicció
            $realWinner = $real1 > $real2 ? 1 : 2;
            $predWinner = $pred1 > $pred2 ? 1 : 2;

            // si ha encertat el guanyador, dono 5 punts
            if ($realWinner === $predWinner) {
                $points += 5;

                // si també ha encertat el marcador exacte, dono punts extra
                if ($real1 === $pred1 && $real2 === $pred2) {
                    if ($bo === 3) {
                        $points += 2;  // BO3 dona menys extra
                    } elseif ($bo === 5) {
                        $points += 5;  // BO5 dona més extra
                    }
                }
            }

            // actualitzo els punts de la predicció
            $predictionDao->updatePredictionPoints($userId, $matchId, $points);

            // si ha guanyat punts, també els sumo al total de l'usuari
            if ($points > 0) {
                $userDao->addPoints($userId, $points);
            }
        }
    }

    public function showPredictFormAction(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: index.php?page=login');
            exit;
        }

        if (empty($_GET['match_id']) || !ctype_digit($_GET['match_id'])) {
            header('Location: index.php?page=matches');
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
            header('Location: index.php?page=login');
            exit;
        }

        if (empty($_GET['match_id']) || !ctype_digit($_GET['match_id'])) {
            header('Location: index.php?page=matches');
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
            header('Location: index.php?page=login');
            exit;
        }

        $predictionDao    = new PredictionDAO($this->db);
        $userPredictions  = $predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);

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
            header('Location: index.php?page=login');
            exit;
        }

        $matchId = filter_input(
            INPUT_GET,
            'match_id',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 0, 'min_range' => 1]]
        );

        if ($matchId === 0) {
            header('Location: index.php?page=my_predictions');
            exit;
        }

        $this->deletePrediction($matchId, (int)$_SESSION['user_id']);

        header('Location: index.php?page=my_predictions');
        exit;
    }

}
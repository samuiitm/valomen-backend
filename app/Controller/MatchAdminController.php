<?php

require_once __DIR__ . '/../Model/DAO/MatchDAO.php';
require_once __DIR__ . '/../Model/DAO/EventDAO.php';
require_once __DIR__ . '/../Model/DAO/TeamDAO.php';

class MatchAdminController
{
    private PDO $db;
    private MatchDAO $matchDao;
    private EventDAO $eventDao;
    private TeamDAO $teamDao;

    public function __construct(PDO $db)
    {
        // guardo la connexió a la bd
        $this->db       = $db;
        // inicialitzo els DAO que necessito
        $this->matchDao = new MatchDAO($db);
        $this->eventDao = new EventDAO($db);
        $this->teamDao  = new TeamDAO($db);
    }

    public function showCreateForm(?int $eventId = null, array $old = [], array $errors = []): void
    {
        // tots els events per al select
        $events = $this->eventDao->getAllEventsForSelect();
        // equips dependrà de l'event seleccionat
        $teams  = [];

        // si ja ve un event seleccionat, carrego els seus equips
        if ($eventId !== null) {
            $teams = $this->teamDao->getTeamsByEvent($eventId);
        }

        // valors per defecte del formulari si no tenim old
        if (empty($old)) {
            $old = [
                'event_id'      => $eventId,
                'team_1'        => '',
                'team_2'        => '',
                'date'          => '',
                'hour'          => '',
                'best_of'       => 3,
                'event_stage'   => '',
                'score_team_1'  => '',
                'score_team_2'  => '',
            ];
        }

        // errors inicials buits
        $defaultErrors = [
            'event_id'      => '',
            'team_1'        => '',
            'team_2'        => '',
            'date'          => '',
            'hour'          => '',
            'best_of'       => '',
            'event_stage'   => '',
            'score_team_1'  => '',
            'score_team_2'  => '',
            'global'        => '',
        ];

        // ajunto errors per defecte amb els que vinguin
        $errors = array_merge($defaultErrors, $errors);

        // event que sortirà marcat al formulari
        $selectedEventId = $eventId;

        // carrego la vista de crear partit
        require __DIR__ . '/../View/match_create.view.php';
    }

    public function createFromPost(): void
    {
        // conveverteixo event_id a enter
        $eventId    = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;

        // els equips poden ser null si no s'han escollit
        $team1Id = isset($_POST['team_1']) && $_POST['team_1'] !== ''
            ? (int) $_POST['team_1']
            : null;
        $team2Id = isset($_POST['team_2']) && $_POST['team_2'] !== ''
            ? (int) $_POST['team_2']
            : null;

        // camps de data i hora, els netejo amb trim
        $date       = trim($_POST['date'] ?? '');
        $hour       = trim($_POST['hour'] ?? '');
        // best_of per defecte és 3 si no ve res
        $bestOf     = isset($_POST['best_of']) ? (int) $_POST['best_of'] : 3;
        $eventStage = trim($_POST['event_stage'] ?? '');

        // guardo els valors bruts del marcador per tornar-los a mostrar
        $score1Raw = trim($_POST['score_team_1'] ?? '');
        $score2Raw = trim($_POST['score_team_2'] ?? '');

        // si estan buits els deixo a null, sinó a enter
        $scoreTeam1 = $score1Raw === '' ? null : (int) $score1Raw;
        $scoreTeam2 = $score2Raw === '' ? null : (int) $score2Raw;

        // array d'errors inicial
        $errors = [
            'event_id'       => '',
            'team_1'         => '',
            'team_2'         => '',
            'date'           => '',
            'hour'           => '',
            'best_of'        => '',
            'event_stage'    => '',
            'score_team_1'   => '',
            'score_team_2'   => '',
            'global'         => '',
        ];

        // validacions bàsiques
        if ($eventId <= 0) {
            $errors['event_id'] = 'Event is required.';
        }

        if ($team1Id !== null && $team1Id < 0) {
            $errors['team_1'] = 'Invalid team.';
        }

        if ($team2Id !== null && $team2Id < 0) {
            $errors['team_2'] = 'Invalid team.';
        }

        // no deixo que els dos equips siguin el mateix
        if ($team1Id !== null && $team2Id !== null && $team1Id === $team2Id) {
            $errors['team_2'] = 'Teams must be different.';
        }

        if ($date === '') {
            $errors['date'] = 'Date is required.';
        }

        if ($hour === '') {
            $errors['hour'] = 'Hour is required.';
        }

        // només deixo BO1, BO3 o BO5
        if (!in_array($bestOf, [1, 3, 5], true)) {
            $errors['best_of'] = 'Best of must be 1, 3 or 5.';
        }

        if ($eventStage === '') {
            $errors['event_stage'] = 'Stage is required.';
        }

        // comprovo que els equips pertanyin a l'event escollit
        if ($eventId > 0 && $team1Id !== null && !$this->teamDao->teamBelongsToEvent($team1Id, $eventId)) {
            $errors['team_1'] = 'Team 1 does not belong to this event.';
        }

        if ($eventId > 0 && $team2Id !== null && !$this->teamDao->teamBelongsToEvent($team2Id, $eventId)) {
            $errors['team_2'] = 'Team 2 does not belong to this event.';
        }

        $status = null;

        // càlcul de l'estat del partit segons la data i l'hora
        if ($date !== '' && $hour !== '') {
            try {
                // aquí faig servir una data "fixa" per simular l'ara
                $now           = new DateTimeImmutable('2025-11-13 13:00:00');
                $matchDateTime = new DateTimeImmutable($date . ' ' . $hour);

                $diffSeconds = $matchDateTime->getTimestamp() - $now->getTimestamp();

                if ($diffSeconds > 0) {
                    $status = 'Upcoming';
                } elseif ($diffSeconds >= -3 * 3600) {
                    $status = 'Live';
                } else {
                    $status = 'Completed';
                }
            } catch (Exception $e) {
                $status = 'Upcoming';
            }
        } else {
            $status = 'Upcoming';
        }

        // validació de marcador segons l'estat i el BO
        if ($status === 'Completed') {
            if ($scoreTeam1 === null || $scoreTeam2 === null) {
                $errors['score_team_1'] = 'Score is required for completed matches.';
                $errors['score_team_2'] = 'Score is required for completed matches.';
            } else {
                if ($scoreTeam1 < 0 || $scoreTeam2 < 0) {
                    $errors['global'] = 'Score cannot be negative.';
                } else {
                    $max = max($scoreTeam1, $scoreTeam2);
                    $min = min($scoreTeam1, $scoreTeam2);

                    if ($bestOf === 1) {
                        if (!(($scoreTeam1 === 1 && $scoreTeam2 === 0) || ($scoreTeam1 === 0 && $scoreTeam2 === 1))) {
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
            }
        } else {
            if ($scoreTeam1 !== null || $scoreTeam2 !== null) {
                $errors['global'] = 'You can only set the score for completed matches.';
            }
            $scoreTeam1 = null;
            $scoreTeam2 = null;
        }

        // miro si hi ha algun error
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        // old per omplir el formulari si falla
        $old = [
            'event_id'      => $eventId,
            'team_1'        => $team1Id,
            'team_2'        => $team2Id,
            'date'          => $date,
            'hour'          => $hour,
            'best_of'       => $bestOf,
            'event_stage'   => $eventStage,
            'score_team_1'  => $score1Raw,
            'score_team_2'  => $score2Raw,
        ];

        if ($hasErrors) {
            $this->showCreateForm($eventId > 0 ? $eventId : null, $old, $errors);
            return;
        }

        $this->matchDao->createMatch(
            $team1Id,
            $team2Id,
            $scoreTeam1,
            $scoreTeam2,
            $date,
            $hour,
            $status,
            $bestOf,
            $eventStage,
            $eventId,
            $_SESSION['user_id'] ?? null
        );

        // redireigeixo a la pàgina de partits
        redirect_to('matches');
        exit;
    }

    public function showEditForm(int $matchId, array $old = [], array $errors = []): void
    {
        $match = $this->matchDao->getMatchById($matchId);
        if (!$match) {
            echo '<p>Match not found.</p>';
            return;
        }

        $events = $this->eventDao->getAllEventsForSelect();

        $eventId = !empty($old['event_id']) ? (int)$old['event_id'] : (int)$match['event_id'];
        $teams   = $this->teamDao->getTeamsByEvent($eventId);

        if (empty($old)) {
            $old = [
                'event_id'      => $match['event_id'],
                'team_1'        => $match['team_1'],
                'team_2'        => $match['team_2'],
                'date'          => $match['date'],
                'hour'          => $match['hour'],
                'best_of'       => $match['best_of'] ?? 3,
                'event_stage'   => $match['event_stage'],
                'score_team_1'  => $match['score_team_1'],
                'score_team_2'  => $match['score_team_2'],
            ];
        }

        $defaultErrors = [
            'event_id'      => '',
            'team_1'        => '',
            'team_2'        => '',
            'date'          => '',
            'hour'          => '',
            'best_of'       => '',
            'event_stage'   => '',
            'score_team_1'  => '',
            'score_team_2'  => '',
            'global'        => '',
        ];
        $errors = array_merge($defaultErrors, $errors);

        $selectedEventId = $eventId;

        require __DIR__ . '/../View/match_edit.view.php';
    }

    public function updateFromPost(int $matchId): void
    {
        require_once __DIR__ . '/PredictionController.php';

        $match = $this->matchDao->getMatchById($matchId);
        if (!$match) {
            redirect_to('matches');
            exit;
        }

        $eventId    = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
        $team1Id = isset($_POST['team_1']) && $_POST['team_1'] !== ''
            ? (int) $_POST['team_1']
            : null;
        $team2Id = isset($_POST['team_2']) && $_POST['team_2'] !== ''
            ? (int) $_POST['team_2']
            : null;
        $date       = trim($_POST['date'] ?? '');
        $hour       = trim($_POST['hour'] ?? '');
        $bestOf     = isset($_POST['best_of']) ? (int) $_POST['best_of'] : 3;
        $eventStage = trim($_POST['event_stage'] ?? '');

        $score1Raw  = trim($_POST['score_team_1'] ?? '');
        $score2Raw  = trim($_POST['score_team_2'] ?? '');

        $scoreTeam1 = $score1Raw === '' ? null : (int) $score1Raw;
        $scoreTeam2 = $score2Raw === '' ? null : (int) $score2Raw;

        $old = [
            'event_id'      => $eventId,
            'team_1'        => $team1Id,
            'team_2'        => $team2Id,
            'date'          => $date,
            'hour'          => $hour,
            'best_of'       => $bestOf,
            'event_stage'   => $eventStage,
            'score_team_1'  => $score1Raw,
            'score_team_2'  => $score2Raw,
        ];

        if (isset($_POST['refresh_teams'])) {
            $this->showEditForm($matchId, $old, []);
            return;
        }

        $errors = [
            'event_id'      => '',
            'team_1'        => '',
            'team_2'        => '',
            'date'          => '',
            'hour'          => '',
            'best_of'       => '',
            'event_stage'   => '',
            'score_team_1'  => '',
            'score_team_2'  => '',
            'global'        => '',
        ];

        if ($eventId <= 0) {
            $errors['event_id'] = 'Event is required.';
        }

        if ($team1Id !== null && $team1Id < 0) {
            $errors['team_1'] = 'Invalid team.';
        }

        if ($team2Id !== null && $team2Id < 0) {
            $errors['team_2'] = 'Invalid team.';
        }

        if ($team1Id !== null && $team2Id !== null && $team1Id === $team2Id) {
            $errors['team_2'] = 'Teams must be different.';
        }

        if ($date === '') {
            $errors['date'] = 'Date is required.';
        }

        if ($hour === '') {
            $errors['hour'] = 'Hour is required.';
        }

        if (!in_array($bestOf, [1, 3, 5], true)) {
            $errors['best_of'] = 'Best of must be 1, 3 or 5.';
        }

        if ($eventStage === '') {
            $errors['event_stage'] = 'Stage is required.';
        }

        if ($eventId > 0 && $team1Id !== null && !$this->teamDao->teamBelongsToEvent($team1Id, $eventId)) {
            $errors['team_1'] = 'Team 1 does not belong to this event.';
        }

        if ($eventId > 0 && $team2Id !== null && !$this->teamDao->teamBelongsToEvent($team2Id, $eventId)) {
            $errors['team_2'] = 'Team 2 does not belong to this event.';
        }

        $status = null;

        if ($date !== '' && $hour !== '') {
            try {
                $now           = new DateTime('2025-11-13 13:00:00');
                $matchDateTime = new DateTime($date . ' ' . $hour);
                $diffSeconds   = $matchDateTime->getTimestamp() - $now->getTimestamp();

                if ($diffSeconds > 0) {
                    $status = 'Upcoming';
                } elseif ($diffSeconds >= -3 * 3600) {
                    $status = 'Live';
                } else {
                    $status = 'Completed';
                }
            } catch (Exception $e) {
                $status = 'Upcoming';
            }
        } else {
            $status = 'Upcoming';
        }

        if ($status === 'Completed') {
            if ($scoreTeam1 === null || $scoreTeam2 === null) {
                $errors['score_team_1'] = 'Score is required for completed matches.';
                $errors['score_team_2'] = 'Score is required for completed matches.';
            } else if ($team1Id === null || $team2Id === null) {
                $errors['team_2'] = 'Teams are required for completed matches.';
            } else {
                if ($scoreTeam1 < 0 || $scoreTeam2 < 0) {
                    $errors['global'] = 'Score cannot be negative.';
                } else {
                    $max = max($scoreTeam1, $scoreTeam2);
                    $min = min($scoreTeam1, $scoreTeam2);

                    if ($bestOf === 1) {
                        if (!(($scoreTeam1 === 1 && $scoreTeam2 === 0)
                            || ($scoreTeam1 === 0 && $scoreTeam2 === 1))) {
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
            }
        } else {
            if ($scoreTeam1 !== null || $scoreTeam2 !== null) {
                $errors['global'] = 'Score should be empty unless the match is completed.';
            }
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        if ($hasErrors) {
            $this->showEditForm($matchId, $old, $errors);
            return;
        }

        $this->matchDao->updateMatch(
            $matchId,
            $team1Id,
            $team2Id,
            $scoreTeam1,
            $scoreTeam2,
            $date,
            $hour,
            $status,
            $bestOf,
            $eventStage,
            $eventId
        );

        if ($status === 'Completed') {
            $updatedMatch = $this->matchDao->getMatchById($matchId);
            if ($updatedMatch) {
                $predictionController = new PredictionController($this->db);
                $predictionController->processMatchResult($updatedMatch);
            }
        }

        redirect_to('matches');
        exit;
    }

    public function deleteMatch(int $matchId): void
    {
        if ($matchId <= 0) {
            redirect_to('matches?view=schedule');
            exit;
        }

        $this->matchDao->deleteMatchById($matchId);

        $view = $_GET['view'] ?? 'schedule';
        if ($view !== 'results') {
            $view = 'schedule';
        }

        redirect_to('matches?view=' . urlencode($view));
        exit;
    }

    public function createFormAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            redirect_to('matches');
            exit;
        }

        $pageTitle = 'Valomen.gg | Create match';
        $pageCss   = 'form_generic.css';

        require __DIR__ . '/../View/partials/header.php';

        $eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : null;
        $this->showCreateForm($eventId);

        require __DIR__ . '/../View/partials/footer.php';
    }

    public function createPostAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            redirect_to('matches');
            exit;
        }

        $this->createFromPost();
    }

    public function editFormAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            redirect_to('matches');
            exit;
        }

        $matchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        $pageTitle = 'Valomen.gg | Edit match';
        $pageCss   = 'form_generic.css';

        require __DIR__ . '/../View/partials/header.php';

        if ($matchId <= 0) {
            echo '<p>Invalid match.</p>';
        } else {
            $this->showEditForm($matchId);
        }

        require __DIR__ . '/../View/partials/footer.php';
    }

    public function editPostAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            redirect_to('matches');
            exit;
        }

        $matchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($matchId <= 0) {
            redirect_to('matches');
            exit;
        }

        $this->updateFromPost($matchId);
    }

    public function deleteAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            redirect_to('matches?view=schedule');
            exit;
        }

        $matchId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $this->deleteMatch($matchId);
    }
}
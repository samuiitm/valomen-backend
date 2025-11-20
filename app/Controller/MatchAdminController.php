<?php

require_once __DIR__ . '/../Model/DAO/MatchDAO.php';
require_once __DIR__ . '/../Model/DAO/EventDAO.php';
require_once __DIR__ . '/../Model/DAO/TeamDAO.php';

class MatchAdminController
{
    private MatchDAO $matchDao;
    private EventDAO $eventDao;
    private TeamDAO $teamDao;

    public function __construct(PDO $db)
    {
        $this->matchDao = new MatchDAO($db);
        $this->eventDao = new EventDAO($db);
        $this->teamDao  = new TeamDAO($db);
    }

    public function showCreateForm(?int $eventId = null, array $old = [], array $errors = []): void
    {
        $events = $this->eventDao->getAllEventsForSelect();
        $teams  = [];

        if ($eventId !== null) {
            $teams = $this->teamDao->getTeamsByEvent($eventId);
        }

        if (empty($old)) {
            $old = [
                'event_id'    => $eventId,
                'team_1'      => '',
                'team_2'      => '',
                'date'        => '',
                'hour'        => '',
                'best_of'     => 3,
                'event_stage' => '',
            ];
        }

        $defaultErrors = [
            'event_id'    => '',
            'team_1'      => '',
            'team_2'      => '',
            'date'        => '',
            'hour'        => '',
            'best_of'     => '',
            'event_stage' => '',
            'global'      => '',
        ];

        $errors = array_merge($defaultErrors, $errors);

        $selectedEventId = $eventId;

        require __DIR__ . '/../View/match_create.view.php';
    }

    public function createFromPost(): void
    {
        $eventId    = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
        $team1Id    = isset($_POST['team_1']) ? (int) $_POST['team_1'] : 0;
        $team2Id    = isset($_POST['team_2']) && $_POST['team_2'] !== '' ? (int) $_POST['team_2'] : 0;
        $date       = trim($_POST['date'] ?? '');
        $hour       = trim($_POST['hour'] ?? '');
        $bestOf     = isset($_POST['best_of']) ? (int) $_POST['best_of'] : 3;
        $eventStage = trim($_POST['event_stage'] ?? '');

        $errors = [
            'event_id'    => '',
            'team_1'      => '',
            'team_2'      => '',
            'date'        => '',
            'hour'        => '',
            'date_time'        => '',
            'best_of'     => '',
            'event_stage' => '',
            'global'      => '',
        ];

        if ($eventId <= 0) {
            $errors['event_id'] = 'Event is required.';
        }

        if ($team1Id <= 0) {
            $errors['team_1'] = 'Team 1 is required.';
        }

        if ($team2Id <= 0) {
            $errors['team_2'] = 'Team 2 is required.';
        }

        if ($team1Id > 0 && $team2Id > 0 && $team1Id === $team2Id) {
            $errors['team_2'] = 'Teams must be different.';
        }

        if ($date === '') {
            $errors['date'] = 'Date is required.';
        }

        if ($hour === '') {
            $errors['hour'] = 'Hour is required.';
        }

        $now = new DateTime('2025-11-13 14:00:00');
        $dateTimeMatch = new DateTime($date . " " . $hour);

        if ($dateTimeMatch < $now) {
            $errors['hour'] = 'You cannot add a finished match. Check the date and time.';
        }

        if (!in_array($bestOf, [1, 3, 5], true)) {
            $errors['best_of'] = 'Best of must be 1, 3 or 5.';
        }

        if ($eventStage === '') {
            $errors['event_stage'] = 'Stage is required.';
        }

        if ($eventId > 0 && $team1Id > 0 && !$this->teamDao->teamBelongsToEvent($team1Id, $eventId)) {
            $errors['team_1'] = 'Team 1 does not belong to this event.';
        }

        if ($eventId > 0 && $team2Id > 0 && !$this->teamDao->teamBelongsToEvent($team2Id, $eventId)) {
            $errors['team_2'] = 'Team 2 does not belong to this event.';
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        $old = [
            'event_id'    => $eventId,
            'team_1'      => $team1Id,
            'team_2'      => $team2Id,
            'date'        => $date,
            'hour'        => $hour,
            'best_of'     => $bestOf,
            'event_stage' => $eventStage,
        ];

        if ($hasErrors) {
            $this->showCreateForm($eventId > 0 ? $eventId : null, $old, $errors);
            return;
        }

        $status = null;

        $this->matchDao->createMatch(
            $team1Id,
            $team2Id,
            null,
            null,
            $date,
            $hour,
            $status,
            $bestOf,
            $eventStage,
            $eventId,
            $_SESSION['user_id'] ?? null
        );

        header('Location: index.php?page=matches');
        exit;
    }
}
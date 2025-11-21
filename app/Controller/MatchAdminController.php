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

        $score1Raw = trim($_POST['score_team_1'] ?? '');
        $score2Raw = trim($_POST['score_team_2'] ?? '');

        $scoreTeam1 = $score1Raw === '' ? null : (int) $score1Raw;
        $scoreTeam2 = $score2Raw === '' ? null : (int) $score2Raw;

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

        $status = null;

        if ($date !== '' && $hour !== '') {
            try {
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

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

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

        header('Location: index.php?page=matches');
        exit;
    }
}
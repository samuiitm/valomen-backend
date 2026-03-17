<?php

class ApiController
{
    private PDO $db;
    private MatchDAO $matchDao;
    private EventDAO $eventDao;
    private TeamDAO $teamDao;

    public function __construct(PDO $db)
    {
        $this->db       = $db;
        $this->matchDao = new MatchDAO($db);
        $this->eventDao = new EventDAO($db);
        $this->teamDao  = new TeamDAO($db);
    }

    public function matchesAction(): void
    {
        $status = strtolower(trim($_GET['status'] ?? 'all'));
        $order  = $_GET['order'] ?? 'date_asc';

        if (!in_array($order, ['date_asc', 'date_desc'], true)) {
            $order = 'date_asc';
        }

        // actualitzo estats abans de retornar dades
        $this->matchDao->updateMatchStatuses();

        if ($status === 'completed') {
            $total   = $this->matchDao->countCompletedMatches();
            $limit   = max(1, $total);
            $matches = $this->matchDao->getCompletedMatchesPaginated($limit, 0, $order);

            $this->sendJson([
                'status' => 'success',
                'type'   => 'completed',
                'total'  => count($matches),
                'data'   => $matches,
            ]);
        }

        if ($status === 'upcoming' || $status === 'live') {
            $total   = $this->matchDao->countUpcomingMatches();
            $limit   = max(1, $total);
            $matches = $this->matchDao->getUpcomingMatchesPaginated($limit, 0, $order);

            $this->sendJson([
                'status' => 'success',
                'type'   => 'upcoming',
                'total'  => count($matches),
                'data'   => $matches,
            ]);
        }

        $totalUpcoming  = $this->matchDao->countUpcomingMatches();
        $totalCompleted = $this->matchDao->countCompletedMatches();

        $upcomingMatches  = $this->matchDao->getUpcomingMatchesPaginated(max(1, $totalUpcoming), 0, $order);
        $completedMatches = $this->matchDao->getCompletedMatchesPaginated(max(1, $totalCompleted), 0, $order);

        $this->sendJson([
            'status' => 'success',
            'type'   => 'all',
            'data'   => [
                'upcoming'  => $upcomingMatches,
                'completed' => $completedMatches,
            ],
        ]);
    }

    public function eventsAction(): void
    {
        $status = strtolower(trim($_GET['status'] ?? 'all'));

        if ($status === 'ongoing') {
            $events = $this->eventDao->getOngoingEvents();

            $this->sendJson([
                'status' => 'success',
                'type'   => 'ongoing',
                'total'  => count($events),
                'data'   => $events,
            ]);
        }

        if ($status === 'upcoming') {
            $events = $this->eventDao->getUpcomingEvents();

            $this->sendJson([
                'status' => 'success',
                'type'   => 'upcoming',
                'total'  => count($events),
                'data'   => $events,
            ]);
        }

        if ($status === 'completed') {
            $events = $this->eventDao->getCompletedEvents();

            $this->sendJson([
                'status' => 'success',
                'type'   => 'completed',
                'total'  => count($events),
                'data'   => $events,
            ]);
        }

        $this->sendJson([
            'status' => 'success',
            'type'   => 'all',
            'data'   => [
                'ongoing'   => $this->eventDao->getOngoingEvents(),
                'upcoming'  => $this->eventDao->getUpcomingEvents(),
                'completed' => $this->eventDao->getCompletedEvents(),
            ],
        ]);
    }

    public function teamsAction(): void
    {
        $teams = $this->teamDao->getAllTeams();

        $this->sendJson([
            'status' => 'success',
            'total'  => count($teams),
            'data'   => $teams,
        ]);
    }

    private function sendJson(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
<?php

class MatchesController
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

    public function index(): void
    {
        $view = $_GET['view'] ?? 'schedule';
        if ($view !== 'results') {
            $view = 'schedule';
        }

        $orderMatches = $_GET['order'] ?? 'date_asc';
        $validOrders  = ['date_asc', 'date_desc'];
        if (!in_array($orderMatches, $validOrders, true)) {
            $orderMatches = 'date_asc';
        }

        $searchMatches = trim($_GET['search'] ?? '');

        $this->matchDao->updateMatchStatuses();

        $perPage = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        if ($view === 'results') {
            if ($searchMatches !== '') {
                $completedMatches = $this->matchDao->searchCompletedMatches($searchMatches, $orderMatches);

                $completedByDate = [];
                foreach ($completedMatches as $m) {
                    $completedByDate[$m['date']][] = $m;
                }

                $total        = count($completedMatches);
                $totalPages   = 1;
                $currentPage  = 1;
                $totalPagesMb = 1;
                $startPage    = 1;
                $endPage      = 1;
            } else {
                $total       = $this->matchDao->countCompletedMatches();
                $totalPages  = max(1, (int)ceil($total / $perPage));
                $p           = filter_input(
                    INPUT_GET,
                    'p',
                    FILTER_VALIDATE_INT,
                    ['options' => ['default' => 1, 'min_range' => 1]]
                );
                $p      = min($p, $totalPages);
                $p      = max(1, $p);
                $offset = ($p - 1) * $perPage;

                $completedMatches = $this->matchDao->getCompletedMatchesPaginated($perPage, $offset, $orderMatches);

                $completedByDate = [];
                foreach ($completedMatches as $m) {
                    $completedByDate[$m['date']][] = $m;
                }

                $startPage    = max(1, $p - 2);
                $endPage      = min($totalPages, $p + 4);
                $currentPage  = $p;
                $totalPagesMb = $totalPages;
            }

            $upcomingByDate = [];
        } else {
            if ($searchMatches !== '') {
                $upcomingMatches = $this->matchDao->searchUpcomingMatches($searchMatches, $orderMatches);

                $upcomingByDate = [];
                foreach ($upcomingMatches as $m) {
                    $upcomingByDate[$m['date']][] = $m;
                }

                $total        = count($upcomingMatches);
                $totalPages   = 1;
                $currentPage  = 1;
                $totalPagesMb = 1;
                $startPage    = 1;
                $endPage      = 1;
            } else {
                $total       = $this->matchDao->countUpcomingMatches();
                $totalPages  = max(1, (int)ceil($total / $perPage));
                $p           = filter_input(
                    INPUT_GET,
                    'p',
                    FILTER_VALIDATE_INT,
                    ['options' => ['default' => 1, 'min_range' => 1]]
                );
                $p      = min($p, $totalPages);
                $p      = max(1, $p);
                $offset = ($p - 1) * $perPage;

                $upcomingMatches = $this->matchDao->getUpcomingMatchesPaginated($perPage, $offset, $orderMatches);

                $upcomingByDate = [];
                foreach ($upcomingMatches as $m) {
                    $upcomingByDate[$m['date']][] = $m;
                }

                $startPage    = max(1, $p - 2);
                $endPage      = min($totalPages, $p + 4);
                $currentPage  = $p;
                $totalPagesMb = $totalPages;
            }

            $completedByDate = [];
        }

        $userPredictedMatchIds = [];
        if (!empty($_SESSION['user_id'])) {
            $userPredictions = $this->predictionDao->getPredictionsByUser((int)$_SESSION['user_id']);
            foreach ($userPredictions as $pRow) {
                $userPredictedMatchIds[(int)$pRow['match_id']] = true;
            }
        }

        if (!function_exists('build_matches_url')) {
            function build_matches_url(
                int $p,
                int $perPage,
                string $view,
                string $order,
                string $search
            ): string {
                $p       = max(1, $p);
                $perPage = max(1, $perPage);

                $params = [
                    'view'    => $view,
                    'p'       => $p,
                    'perPage' => $perPage,
                    'order'   => $order,
                ];

                if ($search !== '') {
                    $params['search'] = $search;
                }

                return 'matches?' . http_build_query($params);
            }
        }

        $pageTitle = 'Valomen.gg | Matches';
        $pageCss   = 'matches.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/matches.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }
}
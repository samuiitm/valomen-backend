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
        // carrego totes les dades de la pàgina
        $data = $this->loadMatchesPageData();
        extract($data);

        $pageTitle = 'Valomen.gg | Matches';
        $pageCss   = 'matches.css';
        $pageJs    = 'matches_ajax.js';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/matches.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function fragmentAction(): void
    {
        // aquesta ruta només retorna el contingut intern per AJAX
        $data = $this->loadMatchesPageData();
        extract($data);

        require __DIR__ . '/../View/partials/matches_content.view.php';
    }

    private function loadMatchesPageData(): array
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

        // actualitzo l'estat dels partits abans de mostrar-los
        $this->matchDao->updateMatchStatuses();

        $perPage = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        $upcomingByDate   = [];
        $completedByDate  = [];
        $totalPagesMb     = 1;
        $currentPage      = 1;
        $startPage        = 1;
        $endPage          = 1;

        if ($view === 'results') {
            if ($searchMatches !== '') {
                $completedMatches = $this->matchDao->searchCompletedMatches($searchMatches, $orderMatches);

                foreach ($completedMatches as $match) {
                    $completedByDate[$match['date']][] = $match;
                }
            } else {
                $total = $this->matchDao->countCompletedMatches();
                $totalPages = max(1, (int) ceil($total / $perPage));

                $currentPage = filter_input(
                    INPUT_GET,
                    'p',
                    FILTER_VALIDATE_INT,
                    ['options' => ['default' => 1, 'min_range' => 1]]
                );

                $currentPage = min($currentPage, $totalPages);
                $currentPage = max(1, $currentPage);
                $offset = ($currentPage - 1) * $perPage;

                $completedMatches = $this->matchDao->getCompletedMatchesPaginated($perPage, $offset, $orderMatches);

                foreach ($completedMatches as $match) {
                    $completedByDate[$match['date']][] = $match;
                }

                $startPage    = max(1, $currentPage - 2);
                $endPage      = min($totalPages, $currentPage + 4);
                $totalPagesMb = $totalPages;
            }
        } else {
            if ($searchMatches !== '') {
                $upcomingMatches = $this->matchDao->searchUpcomingMatches($searchMatches, $orderMatches);

                foreach ($upcomingMatches as $match) {
                    $upcomingByDate[$match['date']][] = $match;
                }
            } else {
                $total = $this->matchDao->countUpcomingMatches();
                $totalPages = max(1, (int) ceil($total / $perPage));

                $currentPage = filter_input(
                    INPUT_GET,
                    'p',
                    FILTER_VALIDATE_INT,
                    ['options' => ['default' => 1, 'min_range' => 1]]
                );

                $currentPage = min($currentPage, $totalPages);
                $currentPage = max(1, $currentPage);
                $offset = ($currentPage - 1) * $perPage;

                $upcomingMatches = $this->matchDao->getUpcomingMatchesPaginated($perPage, $offset, $orderMatches);

                foreach ($upcomingMatches as $match) {
                    $upcomingByDate[$match['date']][] = $match;
                }

                $startPage    = max(1, $currentPage - 2);
                $endPage      = min($totalPages, $currentPage + 4);
                $totalPagesMb = $totalPages;
            }
        }

        // miro quins partits ja ha predit l'usuari
        $userPredictedMatchIds = [];
        if (!empty($_SESSION['user_id'])) {
            $userPredictions = $this->predictionDao->getPredictionsByUser((int) $_SESSION['user_id']);

            foreach ($userPredictions as $prediction) {
                $userPredictedMatchIds[(int) $prediction['match_id']] = true;
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
                $p = max(1, $p);
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

                return url('matches') . '?' . http_build_query($params);
            }
        }

        return [
            'view'                  => $view,
            'orderMatches'          => $orderMatches,
            'searchMatches'         => $searchMatches,
            'perPage'               => $perPage,
            'upcomingByDate'        => $upcomingByDate,
            'completedByDate'       => $completedByDate,
            'currentPage'           => $currentPage,
            'startPage'             => $startPage,
            'endPage'               => $endPage,
            'totalPagesMb'          => $totalPagesMb,
            'userPredictedMatchIds' => $userPredictedMatchIds,
        ];
    }
}
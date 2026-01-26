<?php

class AdminPageController
{
    private PDO $db;
    private TeamDAO $teamDao;
    private UserDAO $userDao;

    public function __construct(PDO $db)
    {
        $this->db      = $db;
        $this->teamDao = new TeamDAO($db);
        $this->userDao = new UserDAO($db);
    }

    public function index(): void
    {
        if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
            redirect_to('');
            exit;
        }

        $section = $_GET['section'] ?? 'users';
        if ($section !== 'users' && $section !== 'teams') {
            $section = 'users';
        }

        $searchAdmin = trim($_GET['search'] ?? '');

        $perPageAdmin = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 10, 'min_range' => 1]]
        );

        $pAdmin = filter_input(
            INPUT_GET,
            'p',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 1, 'min_range' => 1]]
        );

        if ($section === 'users') {
            $total = $this->userDao->countUsers($searchAdmin);
        } else {
            $total = $this->teamDao->countTeams($searchAdmin);
        }

        $totalPagesAdmin = max(1, (int)ceil($total / $perPageAdmin));
        $pAdmin = min($pAdmin, $totalPagesAdmin);
        $pAdmin = max(1, $pAdmin);

        $offset = ($pAdmin - 1) * $perPageAdmin;

        if ($section === 'users') {
            $users = $this->userDao->getUsersPaginated($perPageAdmin, $offset, $searchAdmin);
            $teams = [];
        } else {
            $teams = $this->teamDao->getTeamsPaginated($perPageAdmin, $offset, $searchAdmin);
            $users = [];
        }

        $startPageAdmin = max(1, $pAdmin - 2);
        $endPageAdmin   = min($totalPagesAdmin, $pAdmin + 4);

        $currentPageAdmin  = $pAdmin;
        $totalPagesAdminMb = $totalPagesAdmin;

        if (!function_exists('build_admin_url')) {
            if (!function_exists('build_admin_url')) {
                function build_admin_url(string $section, int $p, int $perPage, string $search = ''): string {
                    $p       = max(1, $p);
                    $perPage = max(1, $perPage);
                    $section = $section === 'teams' ? 'teams' : 'users';

                    $params = [
                        'section' => $section,
                        'p'       => $p,
                        'perPage' => $perPage,
                    ];

                    if ($search !== '') {
                        $params['search'] = $search;
                    }

                    return url('admin') . '?' . http_build_query($params);
                }
            }
        }

        $pageTitle = 'Valomen.gg | Admin panel';
        $pageCss   = 'admin.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/admin.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }
}
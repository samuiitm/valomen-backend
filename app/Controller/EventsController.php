<?php

class EventsController
{
    private PDO $db;
    private EventDAO $eventDao;

    public function __construct(PDO $db)
    {
        $this->db       = $db;
        $this->eventDao = new EventDAO($db);
    }

    public function index(): void
    {
        $orderEvents = $_GET['order'] ?? 'date_asc';
        $validOrder  = ['date_asc','date_desc'];
        if (!in_array($orderEvents, $validOrder, true)) {
            $orderEvents = 'date_asc';
        }

        $perPageEvents = filter_input(
            INPUT_GET,
            'perPage',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 5, 'min_range' => 1]]
        );

        $searchEvents = trim($_GET['search'] ?? '');

        $totalCurrent   = $this->eventDao->countCurrentEvents($searchEvents);
        $totalCompleted = $this->eventDao->countCompletedEvents($searchEvents);

        if ($searchEvents !== '') {
            $pagesCurrent   = 1;
            $pagesCompleted = 1;
        } else {
            $pagesCurrent   = max(1, (int)ceil($totalCurrent   / $perPageEvents));
            $pagesCompleted = max(1, (int)ceil($totalCompleted / $perPageEvents));
        }

        $totalPagesEvents = max($pagesCurrent, $pagesCompleted);

        $pEvents = filter_input(
            INPUT_GET,
            'p',
            FILTER_VALIDATE_INT,
            ['options' => ['default' => 1, 'min_range' => 1]]
        );
        if ($pEvents < 1) {
            $pEvents = 1;
        }
        if ($pEvents > $totalPagesEvents) {
            $pEvents = $totalPagesEvents;
        }

        if ($searchEvents !== '') {
            $offsetCurrent   = 0;
            $offsetCompleted = 0;

            $limitCurrent    = $totalCurrent   > 0 ? $totalCurrent   : 1;
            $limitCompleted  = $totalCompleted > 0 ? $totalCompleted : 1;
        } else {
            $offsetCurrent   = ($pEvents - 1) * $perPageEvents;
            $offsetCompleted = ($pEvents - 1) * $perPageEvents;

            $limitCurrent    = $perPageEvents;
            $limitCompleted  = $perPageEvents;
        }

        $currentEvents = $this->eventDao->getCurrentEventsPaginated(
            $limitCurrent,
            $offsetCurrent,
            $orderEvents,
            $searchEvents !== '' ? $searchEvents : null
        );

        $completedEvents = $this->eventDao->getCompletedEventsPaginated(
            $limitCompleted,
            $offsetCompleted,
            $orderEvents,
            $searchEvents !== '' ? $searchEvents : null
        );

        $ongoingEvents  = [];
        $upcomingEvents = [];

        foreach ($currentEvents as $ev) {
            $status = strtolower((string)$ev['status']);
            if ($status === 'ongoing') {
                $ongoingEvents[] = $ev;
            } else {
                $upcomingEvents[] = $ev;
            }
        }

        $currentPageEvents  = $pEvents;
        $totalPagesEventsMb = $totalPagesEvents;

        $startPageEvents = max(1, $currentPageEvents - 2);
        $endPageEvents   = min($totalPagesEventsMb, $currentPageEvents + 4);

        if (!function_exists('build_events_url')) {
            function build_events_url(int $p, int $perPage, string $order): string {
                $p       = max(1, $p);
                $perPage = max(1, $perPage);

                $validOrder  = ['date_asc','date_desc'];
                if (!in_array($order, $validOrder, true)) {
                    $order = 'date_asc';
                }

                // IMPORTANT: usem url('events') per no dependre de rutes absolutes
                $params = [
                    'p'       => $p,
                    'perPage' => $perPage,
                    'order'   => $order,
                ];

                return url('events') . '?' . http_build_query($params);
            }
        }

        $pageTitle = 'Valomen.gg | Events';
        $pageCss   = 'events.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/events.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }
}
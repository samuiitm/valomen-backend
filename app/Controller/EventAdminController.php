<?php

require_once __DIR__ . '/../Model/DAO/EventDAO.php';
require_once __DIR__ . '/../Model/DAO/TeamDAO.php';

class EventAdminController
{
    private EventDAO $eventDao;
    private TeamDAO $teamDao;

    public function __construct(PDO $db)
    {
        $this->eventDao = new EventDAO($db);
        $this->teamDao  = new TeamDAO($db);
    }

    private function getDefaultOld(): array
    {
        return [
            'name'       => '',
            'start_date' => '',
            'end_date'   => '',
            'prize'      => '',
            'region'     => '',
            'logo'       => '',
            'teams'      => [],
        ];
    }

    private function getDefaultErrors(): array
    {
        return [
            'name'       => '',
            'start_date' => '',
            'end_date'   => '',
            'prize'      => '',
            'region'     => '',
            'logo'       => '',
            'teams'      => '',
            'global'     => '',
        ];
    }

    private function sanitizeTeamsFromPost(): array
    {
        $teams = $_POST['teams'] ?? [];
        if (!is_array($teams)) {
            return [];
        }

        $clean = [];
        foreach ($teams as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $clean[] = $id;
            }
        }

        return array_values(array_unique($clean));
    }

    private function calculateEventStatus(string $startDate, ?string $endDate): string
    {
        $now   = new DateTime('2025-11-13');
        $start = new DateTime($startDate);
        $end   = $endDate !== null ? new DateTime($endDate) : null;

        if ($end !== null && $end < $now) {
            return 'Completed';
        }

        if ($start > $now) {
            return 'Upcoming';
        }

        return 'Ongoing';
    }

    public function showCreateForm(array $old = [], array $errors = []): void
    {
        $allTeams = $this->teamDao->getAllTeams();

        if (empty($old)) {
            $old = $this->getDefaultOld();
        }

        $errors = array_merge($this->getDefaultErrors(), $errors);

        require __DIR__ . '/../View/event_create.view.php';
    }

    public function createFromPost(): void
    {
        $name       = trim($_POST['name'] ?? '');
        $startDate  = trim($_POST['start_date'] ?? '');
        $endDateRaw = trim($_POST['end_date'] ?? '');
        $prizeRaw   = trim($_POST['prize'] ?? '');
        $region     = trim($_POST['region'] ?? '');
        $logo       = trim($_POST['logo'] ?? '');
        $teams      = $this->sanitizeTeamsFromPost();

        $errors = $this->getDefaultErrors();

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($startDate === '') {
            $errors['start_date'] = 'Start date is required.';
        }

        $endDate = $endDateRaw !== '' ? $endDateRaw : null;

        if ($startDate !== '' && $endDate !== null) {
            try {
                $start = new DateTime($startDate);
                $end   = new DateTime($endDate);

                if ($end < $start) {
                    $errors['end_date'] = 'End date cannot be before start date.';
                }
            } catch (Exception $e) {
                $errors['end_date'] = 'Invalid end date.';
            }
        }

        $prize = null;
        if ($prizeRaw !== '') {
            if (!ctype_digit($prizeRaw)) {
                $errors['prize'] = 'Prize must be a non-negative integer.';
            } else {
                $prize = (int)$prizeRaw;
            }
        }

        if ($region === '') {
            $errors['region'] = 'Region is required.';
        }

        if ($logo === '') {
            $errors['logo'] = 'Logo filename is required.';
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        $old = [
            'name'       => $name,
            'start_date' => $startDate,
            'end_date'   => $endDateRaw,
            'prize'      => $prizeRaw,
            'region'     => $region,
            'logo'       => $logo,
            'teams'      => $teams,
        ];

        if ($hasErrors) {
            $this->showCreateForm($old, $errors);
            return;
        }

        $status = $this->calculateEventStatus($startDate, $endDate);

        $postAuthorId = $_SESSION['user_id'] ?? null;

        $eventId = $this->eventDao->createEvent(
            $name,
            $startDate,
            $endDate,
            $status,
            $prize,
            $region,
            $logo,
            $postAuthorId
        );

        $this->eventDao->setEventTeams($eventId, $teams);

        header('Location: events');
        exit;
    }

    public function showEditForm(int $id, array $old = [], array $errors = []): void
    {
        $event = $this->eventDao->getEventById($id);
        if (!$event) {
            http_response_code(404);
            echo "Event not found.";
            return;
        }

        $allTeams        = $this->teamDao->getAllTeams();
        $selectedTeamIds = $this->eventDao->getTeamIdsForEvent($id);

        if (empty($old)) {
            $old = [
                'name'       => $event['name'],
                'start_date' => $event['start_date'],
                'end_date'   => $event['end_date'] ?? '',
                'prize'      => $event['prize'] !== null ? (string)$event['prize'] : '',
                'region'     => $event['region'],
                'logo'       => $event['logo'],
                'teams'      => $selectedTeamIds,
            ];
        }

        $errors = array_merge($this->getDefaultErrors(), $errors);

        require __DIR__ . '/../View/event_edit.view.php';
    }

    public function updateFromPost(int $id): void
    {
        $event = $this->eventDao->getEventById($id);
        if (!$event) {
            http_response_code(404);
            echo "Event not found.";
            return;
        }

        $name       = trim($_POST['name'] ?? '');
        $startDate  = trim($_POST['start_date'] ?? '');
        $endDateRaw = trim($_POST['end_date'] ?? '');
        $prizeRaw   = trim($_POST['prize'] ?? '');
        $region     = trim($_POST['region'] ?? '');
        $logo       = trim($_POST['logo'] ?? '');
        $teams      = $this->sanitizeTeamsFromPost();

        $errors = $this->getDefaultErrors();

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($startDate === '') {
            $errors['start_date'] = 'Start date is required.';
        }

        $endDate = $endDateRaw !== '' ? $endDateRaw : null;

        if ($startDate !== '' && $endDate !== null) {
            try {
                $start = new DateTime($startDate);
                $end   = new DateTime($endDate);

                if ($end < $start) {
                    $errors['end_date'] = 'End date cannot be before start date.';
                }
            } catch (Exception $e) {
                $errors['end_date'] = 'Invalid end date.';
            }
        }

        $prize = null;
        if ($prizeRaw !== '') {
            if (!ctype_digit($prizeRaw)) {
                $errors['prize'] = 'Prize must be a non-negative integer.';
            } else {
                $prize = (int)$prizeRaw;
            }
        }

        if ($region === '') {
            $errors['region'] = 'Region is required.';
        }

        if ($logo === '') {
            $errors['logo'] = 'Logo filename is required.';
        }

        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        $old = [
            'name'       => $name,
            'start_date' => $startDate,
            'end_date'   => $endDateRaw,
            'prize'      => $prizeRaw,
            'region'     => $region,
            'logo'       => $logo,
            'teams'      => $teams,
        ];

        if ($hasErrors) {
            $this->showEditForm($id, $old, $errors);
            return;
        }

        $status = $this->calculateEventStatus($startDate, $endDate);

        $this->eventDao->updateEvent(
            $id,
            $name,
            $startDate,
            $endDate,
            $status,
            $prize,
            $region,
            $logo
        );

        $this->eventDao->setEventTeams($id, $teams);

        header('Location: events');
        exit;
    }

    public function deleteEvent(int $id): void
    {
        try {
            $this->eventDao->deleteEventById($id);
            header('Location: events');
            exit;
        } catch (PDOException $e) {
            http_response_code(400);
            echo "Cannot delete event. It may have matches associated.";
        }
    }

    public function createFormAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: events');
            exit;
        }

        $pageTitle = 'Valomen.gg | Create event';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../View/partials/header.php';
        $this->showCreateForm();
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function createPostAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: events');
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
            header('Location: events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: events');
            exit;
        }

        $pageTitle = 'Valomen.gg | Edit event';
        $pageCss   = 'elements_admin.css';

        require __DIR__ . '/../View/partials/header.php';
        $this->showEditForm($id);
        require __DIR__ . '/../View/partials/footer.php';
    }

    public function editPostAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: events');
            exit;
        }

        $this->updateFromPost($id);
    }

    public function deleteAction(): void
    {
        if (
            empty($_SESSION['user_id']) ||
            empty($_SESSION['is_admin']) ||
            empty($_SESSION['edit_mode'])
        ) {
            header('Location: events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: events');
            exit;
        }

        $this->deleteEvent($id);
    }

}
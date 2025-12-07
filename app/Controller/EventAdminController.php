<?php

require_once __DIR__ . '/../Model/DAO/EventDAO.php';
require_once __DIR__ . '/../Model/DAO/TeamDAO.php';

class EventAdminController
{
    private EventDAO $eventDao;
    private TeamDAO $teamDao;

    public function __construct(PDO $db)
    {
        // inicialitzo els DAO amb la connexió a la bd
        $this->eventDao = new EventDAO($db);
        $this->teamDao  = new TeamDAO($db);
    }

    private function getDefaultOld(): array
    {
        // valors per defecte del formulari (quan encara no hem escrit res)
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
        // aquí inicialitzo tots els errors a buit
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
        // agafo els equips que venen del formulari
        $teams = $_POST['teams'] ?? [];
        if (!is_array($teams)) {
            // si no és un array, retorno buit
            return [];
        }

        $clean = [];
        foreach ($teams as $id) {
            // converteixo a enter per evitar coses rares
            $id = (int)$id;
            if ($id > 0) {
                $clean[] = $id;
            }
        }

        // elimino duplicats i reindexo
        return array_values(array_unique($clean));
    }

    private function calculateEventStatus(string $startDate, ?string $endDate): string
    {
        // faig servir una data "fixa" per simular avui
        $now   = new DateTime('2025-11-13');
        $start = new DateTime($startDate);
        $end   = $endDate !== null ? new DateTime($endDate) : null;

        // si ja ha passat la data de final → completat
        if ($end !== null && $end < $now) {
            return 'Completed';
        }

        // si l'event comença més endavant → encara per començar
        if ($start > $now) {
            return 'Upcoming';
        }

        // si no és ni una cosa ni l'altra → està en marxa
        return 'Ongoing';
    }

    public function showCreateForm(array $old = [], array $errors = []): void
    {
        // carrego tots els equips per poder-los seleccionar
        $allTeams = $this->teamDao->getAllTeams();

        // si no hi ha old, poso els valors per defecte
        if (empty($old)) {
            $old = $this->getDefaultOld();
        }

        // ajunto errors per defecte amb els que vinguin de la validació
        $errors = array_merge($this->getDefaultErrors(), $errors);

        // mostro la vista de crear event
        require __DIR__ . '/../View/event_create.view.php';
    }

    public function createFromPost(): void
    {
        // llegeixo els camps del formulari i els netejo una mica
        $name       = trim($_POST['name'] ?? '');
        $startDate  = trim($_POST['start_date'] ?? '');
        $endDateRaw = trim($_POST['end_date'] ?? '');
        $prizeRaw   = trim($_POST['prize'] ?? '');
        $region     = trim($_POST['region'] ?? '');
        $logo       = trim($_POST['logo'] ?? '');
        $teams      = $this->sanitizeTeamsFromPost(); // equips seleccionats

        // començo amb errors buits
        $errors = $this->getDefaultErrors();

        // validació del nom
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        // validació de la data d'inici
        if ($startDate === '') {
            $errors['start_date'] = 'Start date is required.';
        }

        // si hi ha data de fi, la guardo, sinó es queda a null
        $endDate = $endDateRaw !== '' ? $endDateRaw : null;

        // comprovo que la data de fi sigui després de la d'inici
        if ($startDate !== '' && $endDate !== null) {
            try {
                $start = new DateTime($startDate);
                $end   = new DateTime($endDate);

                if ($end < $start) {
                    $errors['end_date'] = 'End date cannot be before start date.';
                }
            } catch (Exception $e) {
                // si dóna error al crear el DateTime
                $errors['end_date'] = 'Invalid end date.';
            }
        }

        // tractament del premi (pot ser null)
        $prize = null;
        if ($prizeRaw !== '') {
            if (!ctype_digit($prizeRaw)) {
                // només deixo números positius (sense decimals)
                $errors['prize'] = 'Prize must be a non-negative integer.';
            } else {
                $prize = (int)$prizeRaw;
            }
        }

        // la regió és obligatòria
        if ($region === '') {
            $errors['region'] = 'Region is required.';
        }

        // el nom del logo també el demano
        if ($logo === '') {
            $errors['logo'] = 'Logo filename is required.';
        }

        // miro si hi ha algun error
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        // old per tornar a omplir el formulari si hi ha errors
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
            // si hi ha errors, torno a mostrar el formulari amb old + errors
            $this->showCreateForm($old, $errors);
            return;
        }

        // calculo l'estat de l'event segons les dates
        $status = $this->calculateEventStatus($startDate, $endDate);

        // guardo qui ha creat l'event (autor)
        $postAuthorId = $_SESSION['user_id'] ?? null;

        // creo l'event a la base de dades
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

        // relaciono l'event amb els equips seleccionats
        $this->eventDao->setEventTeams($eventId, $teams);

        // quan ja està creat, redirigeixo a la pàgina d'events
        header('Location: index.php?page=events');
        exit;
    }

    public function showEditForm(int $id, array $old = [], array $errors = []): void
    {
        // busco l'event amb aquest id
        $event = $this->eventDao->getEventById($id);
        if (!$event) {
            // si no el trobo, retorno un 404 molt simple
            http_response_code(404);
            echo "Event not found.";
            return;
        }

        // tots els equips per pintar el select
        $allTeams        = $this->teamDao->getAllTeams();
        // equips que ja té assignats aquest event
        $selectedTeamIds = $this->eventDao->getTeamIdsForEvent($id);

        // si no ve cap old, poso els valors actuals de l'event
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

        // barrejo errors per defecte amb els errors que puguin venir
        $errors = array_merge($this->getDefaultErrors(), $errors);

        // mostro la vista d'editar event
        require __DIR__ . '/../View/event_edit.view.php';
    }

    public function updateFromPost(int $id): void
    {
        // comprovo que l'event existeix
        $event = $this->eventDao->getEventById($id);
        if (!$event) {
            http_response_code(404);
            echo "Event not found.";
            return;
        }

        // llegim de nou els camps del formulari
        $name       = trim($_POST['name'] ?? '');
        $startDate  = trim($_POST['start_date'] ?? '');
        $endDateRaw = trim($_POST['end_date'] ?? '');
        $prizeRaw   = trim($_POST['prize'] ?? '');
        $region     = trim($_POST['region'] ?? '');
        $logo       = trim($_POST['logo'] ?? '');
        $teams      = $this->sanitizeTeamsFromPost();

        // errors per defecte
        $errors = $this->getDefaultErrors();

        // validacions igual que al create
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

        // miro si hi ha algun error
        $hasErrors = false;
        foreach ($errors as $e) {
            if ($e !== '') {
                $hasErrors = true;
                break;
            }
        }

        // old per tornar a pintar el formulari si falla
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
            // si hi ha errors, torno a la vista d'edició
            $this->showEditForm($id, $old, $errors);
            return;
        }

        // recalculo l'estat de l'event amb les noves dates
        $status = $this->calculateEventStatus($startDate, $endDate);

        // actualitzo l'event a la bd
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

        // actualitzo també els equips associats
        $this->eventDao->setEventTeams($id, $teams);

        // redirigeixo a la llista d'events
        header('Location: index.php?page=events');
        exit;
    }

    public function deleteEvent(int $id): void
    {
        try {
            // intento eliminar l'event per id
            $this->eventDao->deleteEventById($id);
            header('Location: index.php?page=events');
            exit;
        } catch (PDOException $e) {
            // si hi ha una fk o algun problema, mostro un missatge simple
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
            header('Location: index.php?page=events');
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
            header('Location: index.php?page=events');
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
            header('Location: index.php?page=events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: index.php?page=events');
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
            header('Location: index.php?page=events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: index.php?page=events');
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
            header('Location: index.php?page=events');
            exit;
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: index.php?page=events');
            exit;
        }

        $this->deleteEvent($id);
    }

}
<?php

class HomeController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        $pageTitle = 'Valomen.gg | Home';
        $pageCss   = 'main.css';

        require __DIR__ . '/../View/partials/header.php';
        require __DIR__ . '/../View/home.view.php';
        require __DIR__ . '/../View/partials/footer.php';
    }
}
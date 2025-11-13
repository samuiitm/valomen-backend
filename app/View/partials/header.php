<!-- app/View/partials/header.php -->
<?php
$pageTitle = $pageTitle ?? 'Valomen.gg';
$pageCss   = $pageCss   ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="assets/icons/valomen_logo.ico">


    <link rel="stylesheet" href="css/generic.css">
    <?php if ($pageCss): ?>
      <link rel="stylesheet" href="css/<?= htmlspecialchars($pageCss) ?>">
    <?php endif; ?>
</head>
<body>
<header>
    <nav>
        <div class="logo">
            <a href="index.php">
                <img src="assets/img/valomen_logo.webp" alt="Valomen.gg Logo">
                <span>Valomen.gg</span>
            </a>
        </div>
        <div class="nav-buttons">
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php?page=matches">Matches</a></li>
                <li><a href="index.php?page=events">Events</a></li>
            </ul>
            <a class="login-button" href="index.php?page=login">Log in</a>
        </div>
    </nav>
</header>
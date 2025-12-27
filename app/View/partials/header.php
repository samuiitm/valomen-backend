<!-- app/View/partials/header.php -->
<?php
$pageTitle = $pageTitle ?? 'Valomen.gg';
$pageCss   = $pageCss   ?? null;
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
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

    <link rel="icon" type="image/png" href="<?= $base ?>/assets/icons/valomen_logo.ico">

    <script defer src="<?= $base ?>/js/main.js"></script>
    <link rel="stylesheet" href="<?= $base ?>/css/generic.css">
    <?php if ($pageCss): ?>
      <link rel="stylesheet" href="<?= $base ?>/css/<?= htmlspecialchars($pageCss) ?>">
    <?php endif; ?>
</head>
<body class="<?= !empty($_SESSION['edit_mode']) ? 'edit-mode' : '' ?>">
<header>
    <nav>
        <div class="logo">
            <a href="<?= $base ?>/">
                <img src="<?= $base ?>/assets/img/valomen_logo.webp" alt="Valomen.gg Logo">
                <span>Valomen.gg</span>
            </a>
        </div>
        <div class="nav-buttons">
            <ul class="nav-links">
                <li><a href="<?= $base ?>/">Home</a></li>
                <li><a href="<?= $base ?>/matches">Matches</a></li>
                <li><a href="<?= $base ?>/events">Events</a></li>
            </ul>

            <?php if (!empty($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <?php
                        $avatarFilename = $_SESSION['user_logo'] ?? null;

                        if (!empty($avatarFilename)) {
                            $headerAvatarSrc = $base . '/assets/img/user-avatars/' . htmlspecialchars($avatarFilename);
                        } else {
                            $headerAvatarSrc = $base . '/assets/img/default-avatar.png';
                        }
                    ?>
                    <button id="userMenuBtn" class="user-avatar-btn">
                        <img src="<?= $headerAvatarSrc ?>" alt="User avatar">
                    </button>

                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-header">
                            <span class="user-name">
                                <?= htmlspecialchars($_SESSION['username']) ?>
                            </span>

                            <?php if (!empty($_SESSION['is_admin'])): ?>
                                <span class="user-role">Admin</span>
                            <?php endif; ?>
                        </div>

                        <!-- PROFILE -->
                        <a href="<?= $base ?>/profile" class="dropdown-item">Profile</a>

                        <!-- MY PREDICTIONS -->
                        <a href="<?= $base ?>/my_predictions" class="dropdown-item">My predictions</a>

                        <?php if (!empty($_SESSION['is_admin'])): ?>
                            <a href="<?= $base ?>/?action=toggle_edit_mode"
                               class="dropdown-item edit-toggle <?= !empty($_SESSION['edit_mode']) ? 'on' : 'off' ?>">
                                <span class="toggle-label">Edit mode</span>
                                <span class="toggle-pill">
                                    <span class="toggle-knob"></span>
                                </span>
                                <span class="toggle-state">
                                    <?= !empty($_SESSION['edit_mode']) ? 'ON' : 'OFF' ?>
                                </span>
                            </a>

                            <!-- ADMIN PANEL -->
                            <a href="<?= $base ?>/admin" class="dropdown-item">Admin panel</a>
                        <?php endif; ?>

                        <!-- LOGOUT -->
                        <a href="<?= $base ?>/logout" class="dropdown-item logout-item">Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- LOGIN -->
                <a class="login-button" href="<?= $base ?>/login">Log in</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
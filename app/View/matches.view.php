<?php $boolSchedule = false; ?>
<?php $boolResults = false; ?>

<main class="matches-page">
    <div id="matchesAjaxBox" data-fragment-url="<?= htmlspecialchars(url('matches_fragment')) ?>">
        <?php require __DIR__ . '/partials/matches_content.view.php'; ?>
    </div>
</main>
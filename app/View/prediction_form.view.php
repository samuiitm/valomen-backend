<main class="prediction-form-page">
    <section class="prediction-form-container">
        <h1>Make your prediction</h1>
        <p class="match-summary">
            <?= htmlspecialchars($match['team_1_name']) ?>
            vs
            <?= htmlspecialchars($match['team_2_name'] ?? 'TBD') ?>
            ·
            <?= function_exists('formatMatchDate') ? formatMatchDate($match['date']) : htmlspecialchars($match['date']) ?>
            ·
            <?= function_exists('formatMatchHour') ? formatMatchHour($match['hour']) : htmlspecialchars(substr($match['hour'], 0, 5)) ?>
        </p>

        <p class="info-message">
            Prediction feature is not available yet. This is a preview of the future form.
        </p>

        <a href="index.php?page=matches" class="back-link">Back to matches</a>
    </section>
</main>
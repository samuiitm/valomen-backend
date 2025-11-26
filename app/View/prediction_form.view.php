<?php
$team1 = htmlspecialchars($match['team_1_name']);
$team2 = htmlspecialchars($match['team_2_name'] ?? 'TBD');

$team1Exists = !empty($match['team_1']);
$team2Exists = !empty($match['team_2']);

$status      = strtolower($match['status'] ?? '');
$isUpcoming  = $status === 'upcoming';
$isLive      = $status === 'live';
$isCompleted = $status === 'completed';
?>

<main class="prediction-form-page">
    <section class="prediction-form-container">
        <h1>Make your prediction</h1>

        <p class="match-summary">
            <?= $team1 ?>
            vs
            <?= $team2 ?>
            ·
            <?= formatMatchDate($match['date']) ?>
            ·
            <?= formatMatchHour($match['hour']) ?>
            ·
            Best of <?= strtoupper($match['best_of']) ?>
        </p>

        <?php if (!empty($errors['global'])): ?>
            <p class="error-message"><?= htmlspecialchars($errors['global']) ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p class="success-message">Prediction saved successfully!</p>
        <?php endif; ?>

        <?php if (!$team1Exists || !$team2Exists): ?>

            <p class="info-message">
                This prediction will open once both teams are confirmed.
            </p>

        <?php elseif ($isLive || $isCompleted): ?>

            <p class="info-message">
                Predictions for this match are now closed.
            </p>

            <?php if (!empty($existingPrediction)): ?>
                <div class="locked-prediction">
                    <p>Your submitted prediction:</p>
                    <strong>
                        <?= (int)$existingPrediction['score_team_1_pred'] ?>
                        -
                        <?= (int)$existingPrediction['score_team_2_pred'] ?>
                    </strong>
                </div>
            <?php endif; ?>

        <?php else: ?>

            <form class="prediction-form" method="POST">
                <div class="teams-scores">
                    <div class="team-score-block">
                        <span class="team-name"><?= $team1 ?></span>
                        <input
                            type="number"
                            name="score_team_1"
                            min="0"
                            max="5"
                            value="<?= isset($existingPrediction['score_team_1_pred']) ? (int)$existingPrediction['score_team_1_pred'] : '' ?>"
                            required
                        >
                    </div>

                    <div class="team-score-block">
                        <span class="team-name"><?= $team2 ?></span>
                        <input
                            type="number"
                            name="score_team_2"
                            min="0"
                            max="5"
                            value="<?= isset($existingPrediction['score_team_2_pred']) ? (int)$existingPrediction['score_team_2_pred'] : '' ?>"
                            required
                        >
                    </div>
                </div>

                <button class="send-button" type="submit">Save prediction</button>
            </form>

        <?php endif; ?>

        <a href="index.php?page=matches" class="back-link">Back to matches</a>
    </section>
</main>
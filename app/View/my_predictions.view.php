<main class="matches-page my-predictions-page">
    <div class="matches-header">
        <span class="matches-title">My predictions</span>
        <p class="matches-subtitle">
            These are the matches you have predicted, grouped by date.
        </p>
    </div>

    <?php if (empty($predictionsByDate)): ?>
        <p class="no-predictions-message">
            You haven't made any predictions yet.
        </p>
    <?php else: ?>
        <?php foreach ($predictionsByDate as $date => $predictionsOfDay): ?>
            <div class="day-block">
                <div class="header-day">
                    <span class="date-match">
                        <?= function_exists('formatMatchDate')
                            ? formatMatchDate($date)
                            : htmlspecialchars($date) ?>
                    </span>
                    <div class="prediction-day-tag">
                        Your predictions
                    </div>
                </div>

                <div class="matches-day">
                    <?php foreach ($predictionsOfDay as $prediction): ?>
                        <?php
                            $hasResult = $prediction['score_team_1_real'] !== null && $prediction['score_team_2_real'] !== null;
                        ?>
                        <div class="match-block prediction-block">
                            <div class="match-info">
                                <span class="match-hour">
                                    <?= function_exists('formatMatchHour')
                                        ? formatMatchHour($prediction['hour'])
                                        : htmlspecialchars(substr($prediction['hour'], 0, 5)) ?>
                                </span>

                                <div class="teams">
                                    <div class="team">
                                        <span><?= htmlspecialchars($prediction['team_1_name']) ?></span>
                                    </div>
                                    <span class="vs">vs</span>
                                    <div class="team">
                                        <span><?= htmlspecialchars($prediction['team_2_name'] ?? 'TBD') ?></span>
                                    </div>
                                </div>
                                
                                <div class="scoreboard">
                                    <span class="badge-prediction">FINAL RESULT</span>
                                    <div class="prediction-scoreboard">
                                        <span>
                                            <?= $hasResult
                                                ? htmlspecialchars($prediction['score_team_1_real'])
                                                : '-' ?>
                                        </span>
                                        <span>
                                            <?= $hasResult
                                                ? htmlspecialchars($prediction['score_team_2_real'])
                                                : '-' ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="scoreboard">
                                    <span class="badge-prediction">PREDICTION</span>
                                    <div class="prediction-scoreboard">
                                        <span>
                                            <?= htmlspecialchars($prediction['score_team_1_pred']) ?>
                                        </span>
                                        <span>
                                            <?= htmlspecialchars($prediction['score_team_2_pred']) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="scoreboard">
                                    <span class="badge-prediction-pts">PTS EARNED</span>
                                    <div class="badge-points">
                                    <span><?= $prediction['points_awarded'] !== null
                                        ? '+' . htmlspecialchars($prediction['points_awarded']) . ' pts'
                                        : ($hasResult ? '0 pts' : '-') ?></span>
                                </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>
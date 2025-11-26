<main class="matches-page my-predictions-page">
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
                            $hasResult = $prediction['score_team_1_real'] !== null
                                      && $prediction['score_team_2_real'] !== null;

                            $statusRaw = $prediction['match_status'] ?? '';
                            $status    = strtolower($statusRaw);

                            $isLocked = ($status !== 'upcoming');

                            $lockReason = match ($status) {
                                'live'      => 'Prediction closed (match is LIVE)',
                                'completed' => 'Prediction closed (match completed)',
                                default     => 'Prediction unavailable',
                            };
                        ?>
                        <div class="match-block prediction-block">
                            <div class="match-info">
                                <span class="match-hour">
                                    <?= function_exists('formatMatchHour')
                                        ? formatMatchHour($prediction['hour'])
                                        : htmlspecialchars(substr($prediction['hour'], 0, 5)) ?>
                                </span>

                                <div class="matches">
                                    <div class="teams">
                                        <div class="team">
                                            <span><?= htmlspecialchars($prediction['team_1_name']) ?></span>
                                        </div>
                                        <span class="vs">vs</span>
                                        <div class="team">
                                            <span><?= htmlspecialchars($prediction['team_2_name'] ?? 'TBD') ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="scoreboard">
                                    <span class="badge-prediction">STATUS</span>
                                    <div class="container-status <?= $status ?>">
                                        <?php if ($status === 'completed' || $status === 'live'): ?>
                                            <span>CLOSED</span>
                                        <?php else: ?>
                                            <span>ACTIVE</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="scoreboard">
                                    <span class="badge-prediction">FINAL RESULT</span>
                                    <div class="prediction-scoreboard">
                                        <span><?= $hasResult ? htmlspecialchars($prediction['score_team_1_real']) : '-' ?></span>
                                        <span><?= $hasResult ? htmlspecialchars($prediction['score_team_2_real']) : '-' ?></span>
                                    </div>
                                </div>

                                <div class="scoreboard">
                                    <span class="badge-prediction">PREDICTION</span>
                                    <div class="prediction-scoreboard">
                                        <span><?= htmlspecialchars($prediction['score_team_1_pred']) ?></span>
                                        <span><?= htmlspecialchars($prediction['score_team_2_pred']) ?></span>
                                    </div>
                                </div>

                                <div class="prediction-manage-actions">
                                    <?php if ($isLocked): ?>
                                        <a class="prediction-manage-btn edit locked"
                                        title="<?= htmlspecialchars($lockReason) ?>">
                                            ✎
                                        </a>

                                        <a class="prediction-manage-btn delete locked"
                                        title="<?= htmlspecialchars($lockReason) ?>">
                                            🗑
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php?page=predict&match_id=<?= (int)$prediction['match_id'] ?>"
                                        class="prediction-manage-btn edit"
                                        title="Edit prediction">
                                            ✎
                                        </a>
                                        <a href="index.php?page=prediction_delete&match_id=<?= (int)$prediction['match_id'] ?>"
                                        class="prediction-manage-btn delete js-delete-prediction"
                                        title="Delete prediction">
                                            🗑
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="scoreboard">
                                    <span class="badge-prediction-pts">PTS EARNED</span>
                                    <div class="badge-points">
                                        <span>
                                            <?= $prediction['points_awarded'] !== null
                                                ? '+' . htmlspecialchars($prediction['points_awarded']) . ' pts'
                                                : ($hasResult ? '0 pts' : '-') ?>
                                        </span>
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

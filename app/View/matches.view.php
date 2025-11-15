<main class="matches-page">

    <div class="matches-header">
        <div class="matches-tabs">
            <a href="#" class="tab active">SCHEDULE</a>
            <a href="#" class="tab">RESULTS</a>
        </div>
    </div>

    <?php if (empty($upcomingByDate)): ?>
        <p>No upcoming matches.</p>
    <?php else: ?>

        <?php foreach ($upcomingByDate as $date => $matchesOfDay): ?>
            <div class="day-block">
                <div class="header-day">
                    <span class="date-match"><?= formatMatchDate($date) ?></span>
                </div>

                <div class="matches-day">
                    <?php foreach ($matchesOfDay as $match): ?>

                        <?php $statusInfo = getMatchStatusInfo($match['date'], $match['hour']); ?>

                        <div class="match-block">
                            <div class="match-info">
                                <span class="match-hour">
                                    <?= formatMatchHour($match['hour']) ?>
                                </span>

                                <div class="teams">
                                    <div class="team">
                                        <img src="assets/icons/badges/<?= htmlspecialchars($match['team_1_country']) ?>.png" alt="">
                                        <span><?= htmlspecialchars($match['team_1_name']) ?></span>
                                    </div>

                                    <?php if (!empty($match['team_2_name'])): ?>
                                        <div class="team">
                                            <img src="assets/icons/badges/<?= htmlspecialchars($match['team_2_country']) ?>.png" alt="">
                                            <span><?= htmlspecialchars($match['team_2_name']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="team">
                                            <img src="assets/icons/badges/tbd.png" alt="">
                                            <span>TBD</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="scoreboard">
                                    <span><?= $match['score_team_1'] ?? 0 ?></span>
                                    <span><?= $match['score_team_2'] ?? 0 ?></span>
                                </div>

                                <?php if ($statusInfo['cssClass'] === 'live'): ?>
                                    <div class="live">
                                        <span><?= $statusInfo['label'] ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="upcoming">
                                        <span><?= $statusInfo['label'] ?></span>
                                        <?php if (!empty($statusInfo['countdown'])): ?>
                                            <span class="upcoming-time"><?= $statusInfo['countdown'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <div class="tournament-info">
                                <div class="tournament-text">
                                    <span class="tournament-stage">
                                        <?= htmlspecialchars($match['event_stage']) ?>
                                    </span>
                                    <span><?= htmlspecialchars($match['event_name']) ?></span>
                                </div>
                                <img class="tournament-logo"
                                     src="assets/icons/events/<?= htmlspecialchars($match['event_logo']) ?>"
                                     alt="Tournament-icon">
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

</main>
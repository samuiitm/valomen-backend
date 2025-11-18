<?php $boolSchedule = false; ?>
<?php $boolResults = false; ?>

<main class="matches-page">

    <?php if ($view === 'schedule'): ?>
        <?php if (empty($upcomingByDate)): ?>
            <p>No upcoming matches.</p>
        <?php else: ?>

            <?php foreach ($upcomingByDate as $date => $matchesOfDay): ?>
                <div class="day-block">
                    <div class="header-day">
                        <span class="date-match"><?= formatMatchDate($date) ?></span>
                        <?php if (!$boolSchedule): ?>
                            <div class="matches-tabs">
                                <a href="index.php?page=matches&view=schedule" 
                                    class="tab <?= $view === 'schedule' ? 'active' : '' ?>">SCHEDULE</a>
                                <a href="index.php?page=matches&view=results" 
                                    class="tab <?= $view === 'results' ? 'active' : '' ?>">RESULTS</a>
                            </div>
                            <?php $boolSchedule = true; ?>
                        <?php endif; ?>
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
                                            <img src="<?= getFlagPath($match['team_1_country']) ?>" alt="Flag">
                                            <span><?= htmlspecialchars($match['team_1_name']) ?></span>
                                        </div>

                                        <div class="team">
                                            <img src="<?= getFlagPath($match['team_2_country'] ?? '') ?>" alt="Flag">
                                            <span><?= htmlspecialchars($match['team_2_name'] ?? 'TBD') ?></span>
                                        </div>
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
                                
                                <?php if (!empty($_SESSION['user_id'])
                                        && !empty($match['team_2_name'])
                                        && ($statusInfo['label'] !== 'LIVE')
                                        && empty($userPredictedMatchIds[(int)$match['id']] ?? null)): ?>
                                    <a href="index.php?page=predict&match_id=<?= (int)$match['id'] ?>"
                                    class="predict-button">
                                        Make prediction
                                    </a>
                                <?php elseif ($statusInfo['label'] === 'LIVE'): ?>
                                    <a
                                    class="predict-button closed">
                                        Prediction closed
                                    </a>
                                <?php elseif ($userPredictedMatchIds[(int)$match['id']] ?? null): ?>
                                    <a
                                    class="predict-button closed">
                                        Already predicted
                                    </a>
                                <?php elseif (empty($_SESSION['user_id'])): ?>
                                    <a href="index.php?page=login"
                                    class="predict-button">
                                        LOG IN TO PREDICT
                                    </a>
                                <?php else: ?>
                                    <a
                                    class="predict-button pending">
                                        Pending teams
                                    </a>
                                <?php endif; ?>

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
            <?php $boolSchedule = false; ?>
        <?php endif; ?>
    <?php else: ?>
        <?php if (empty($completedByDate)): ?>
            <p>No completed matches.</p>
        <?php else: ?>
            <?php foreach ($completedByDate as $date => $matchesOfDay): ?>
                <div class="day-block">
                    <div class="header-day">
                        <span class="date-match"><?= formatMatchDate($date) ?></span>
                        <?php if (!$boolResults): ?>
                            <div class="matches-tabs">
                                <a href="index.php?page=matches&view=schedule" 
                                    class="tab <?= $view === 'schedule' ? 'active' : '' ?>">SCHEDULE</a>
                                <a href="index.php?page=matches&view=results" 
                                    class="tab <?= $view === 'results' ? 'active' : '' ?>">RESULTS</a>
                            </div>
                            <?php $boolResults = true; ?>
                        <?php endif; ?>
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
                                            <img src="<?= getFlagPath($match['team_1_country']) ?>" alt="Flag">
                                            <span><?= htmlspecialchars($match['team_1_name']) ?></span>
                                        </div>

                                        <div class="team">
                                            <img src="<?= getFlagPath($match['team_2_country'] ?? '') ?>" alt="Flag">
                                            <span><?= htmlspecialchars($match['team_2_name'] ?? 'TBD') ?></span>
                                        </div>
                                    </div>

                                    <div class="scoreboard">
                                        <span><?= $match['score_team_1'] ?? 0 ?></span>
                                        <span><?= $match['score_team_2'] ?? 0 ?></span>
                                    </div>

                                    <div class="completed">
                                        <span>Completed</span>
                                        <span class="completed-time"> <?= getElapsedTime($match['date'], $match['hour']) ?></span>
                                    </div>
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
            <?php $boolResults = false; ?>
        <?php endif; ?>
    <?php endif; ?>
</main>
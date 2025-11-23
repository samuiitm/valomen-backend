<?php $boolSchedule = false; ?>
<?php $boolResults = false; ?>

<main class="matches-page">

    <?php if ($view === 'schedule'): ?>
        <?php if (empty($upcomingByDate)): ?>
            <div class="header-day">
                <span class="date-match">No upcoming matches</span>

                <?php if (!$boolSchedule): ?>
                    <div class="matches-header-right">
                        <div class="matches-tabs">
                            <a href="index.php?page=matches&view=schedule" 
                            class="tab <?= $view === 'schedule' ? 'active' : '' ?>">SCHEDULE</a>
                            <a href="index.php?page=matches&view=results" 
                            class="tab <?= $view === 'results' ? 'active' : '' ?>">RESULTS</a>
                        </div>

                        <form class="search-elements" id="matchesSearchForm" action="index.php" method="get">
                            <input type="hidden" name="page" value="matches">
                            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                            <input type="hidden" name="perPage" value="<?= htmlspecialchars((string)$perPage) ?>">
                            <input type="hidden" name="order" value="<?= htmlspecialchars($orderMatches) ?>">
                            <input type="text"
                                name="search"
                                id="searchInput"
                                placeholder="Search..."
                                class="searchInput"
                                value="<?= htmlspecialchars($searchMatches) ?>">
                        </form>

                        <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                            <a href="index.php?page=match_create" class="add-match-btn">
                                <span class="add-match-plus">+</span>
                                <span>Add match</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php $boolSchedule = true; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <?php foreach ($upcomingByDate as $date => $matchesOfDay): ?>
                <div class="day-block">
                    <div class="header-day">
                        <span class="date-match"><?= formatMatchDate($date) ?></span>

                        <?php if (!$boolSchedule): ?>
                            <div class="matches-header-right">
                                <div class="matches-tabs">
                                    <a href="index.php?page=matches&view=schedule" 
                                    class="tab <?= $view === 'schedule' ? 'active' : '' ?>">SCHEDULE</a>
                                    <a href="index.php?page=matches&view=results" 
                                    class="tab <?= $view === 'results' ? 'active' : '' ?>">RESULTS</a>
                                </div>

                                <form class="matches-search" id="matchesSearchForm" action="index.php" method="get">
                                    <input type="hidden" name="page" value="matches">
                                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                                    <input type="hidden" name="perPage" value="<?= htmlspecialchars((string)$perPage) ?>">
                                    <input type="hidden" name="order" value="<?= htmlspecialchars($orderMatches) ?>">
                                    <input type="text"
                                        name="search"
                                        id="searchInput"
                                        placeholder="Search..."
                                        class="searchInput"
                                        value="<?= htmlspecialchars($searchMatches) ?>">
                                </form>

                                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                                    <a href="index.php?page=match_create" class="add-match-btn">
                                        <span class="add-match-plus">+</span>
                                        <span>Add match</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php $boolSchedule = true; ?>
                        <?php endif; ?>
                    </div>

                    <div class="matches-day">
                        <?php foreach ($matchesOfDay as $match): ?>
                            <div class="match-block">
                                <div class="match-info">
                                    <span class="match-hour">
                                        <?= formatMatchHour($match['hour']) ?>
                                    </span>

                                    <div class="teams">
                                        <div class="team">
                                            <img src="<?= getFlagPath($match['team_1_country'] ?? '') ?>" alt="Flag">
                                            <span><?= htmlspecialchars($match['team_1_name'] ?? 'TBD') ?></span>
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

                                    <?php if ($match['status'] === 'Live'): ?>
                                        <div class="live">
                                            <span>LIVE</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="upcoming">
                                            <span>Upcoming</span>
                                            <span class="upcoming-time"><?= getMatchCountdown($match['date'], $match['hour']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($_SESSION['user_id'])
                                        && !empty($match['team_1_name'])
                                        && !empty($match['team_2_name'])
                                        && ($match['status'] !== 'Live')
                                        && empty($userPredictedMatchIds[(int)$match['id']] ?? null)): ?>
                                    <a href="index.php?page=predict&match_id=<?= (int)$match['id'] ?>"
                                    class="predict-button">
                                        Make prediction
                                    </a>
                                <?php elseif ($match['status'] === 'Live'): ?>
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

                                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                                    <div class="match-admin-actions">
                                        <a href="index.php?page=match_edit&id=<?= (int)$match['id'] ?>"
                                        class="match-admin-btn edit"
                                        title="Edit match">
                                            ✎
                                        </a>
                                       <a href="index.php?page=match_delete&id=<?= (int)$match['id'] ?>&view=schedule"
                                        class="match-admin-btn delete js-delete-match"
                                        data-match-label="<?= htmlspecialchars($match['team_1_name'] . ' vs ' . ($match['team_2_name'] ?? 'TBD')) ?>"
                                        title="Delete match">
                                            🗑
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php $boolSchedule = false; ?>
        <?php endif; ?>
    <?php else: ?>
        <?php if (empty($completedByDate)): ?>
            <div class="header-day">
                <span class="date-match">No completed matches</span>

                <?php if (!$boolSchedule): ?>
                    <div class="matches-header-right">
                        <div class="matches-tabs">
                            <a href="index.php?page=matches&view=schedule" 
                            class="tab <?= $view === 'schedule' ? 'active' : '' ?>">SCHEDULE</a>
                            <a href="index.php?page=matches&view=results" 
                            class="tab <?= $view === 'results' ? 'active' : '' ?>">RESULTS</a>
                        </div>

                        <form class="matches-search" id="matchesSearchForm" action="index.php" method="get">
                            <input type="hidden" name="page" value="matches">
                            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                            <input type="hidden" name="perPage" value="<?= htmlspecialchars((string)$perPage) ?>">
                            <input type="hidden" name="order" value="<?= htmlspecialchars($orderMatches) ?>">
                            <input type="text"
                                name="search"
                                id="searchInput"
                                placeholder="Search..."
                                class="searchInput"
                                value="<?= htmlspecialchars($searchMatches) ?>">
                        </form>

                        <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                            <a href="index.php?page=match_create" class="add-match-btn">
                                <span class="add-match-plus">+</span>
                                <span>Add match</span>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php $boolSchedule = true; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($completedByDate as $date => $matchesOfDay): ?>
                <div class="day-block">
                    <div class="header-day">
                        <span class="date-match"><?= formatMatchDate($date) ?></span>

                        <?php if (!$boolResults): ?>
                            <div class="matches-header-right">
                                <div class="matches-tabs">
                                    <a href="index.php?page=matches&view=schedule" 
                                    class="tab <?= $view === 'schedule' ? 'active' : '' ?>">SCHEDULE</a>
                                    <a href="index.php?page=matches&view=results" 
                                    class="tab <?= $view === 'results' ? 'active' : '' ?>">RESULTS</a>
                                </div>

                                <form class="matches-search" id="matchesSearchForm" action="index.php" method="get">
                                    <input type="hidden" name="page" value="matches">
                                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                                    <input type="hidden" name="perPage" value="<?= htmlspecialchars((string)$perPage) ?>">
                                    <input type="hidden" name="order" value="<?= htmlspecialchars($orderMatches) ?>">
                                    <input type="text"
                                        name="search"
                                        id="searchInput"
                                        placeholder="Search..."
                                        class="searchInput"
                                        value="<?= htmlspecialchars($searchMatches) ?>">
                                </form>

                                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                                    <a href="index.php?page=match_create" class="add-match-btn">
                                        <span class="add-match-plus">+</span>
                                        <span>Add match</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <?php $boolResults = true; ?>
                        <?php endif; ?>
                    </div>


                    <div class="matches-day">
                        <?php foreach ($matchesOfDay as $match): ?>
                            <div class="match-block">
                                <div class="match-info">
                                    <span class="match-hour">
                                        <?= formatMatchHour($match['hour']) ?>
                                    </span>

                                    <div class="teams">
                                        <div class="team">
                                            <img src="<?= getFlagPath($match['team_1_country'] ?? '') ?>" alt="Flag">
                                            <span><?= htmlspecialchars($match['team_1_name'] ?? 'TBD') ?></span>
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

                                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                                    <div class="match-admin-actions">
                                        <a href="index.php?page=match_edit&id=<?= (int)$match['id'] ?>"
                                        class="match-admin-btn edit"
                                        title="Edit match">
                                            ✎
                                        </a>
                                        <a href="index.php?page=match_delete&id=<?= (int)$match['id'] ?>&view=results"
                                        class="match-admin-btn delete js-delete-match"
                                        data-match-label="<?= htmlspecialchars($match['team_1_name'] . ' vs ' . ($match['team_2_name'] ?? 'TBD')) ?>"
                                        title="Delete match">
                                            🗑
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php $boolResults = false; ?>
        <?php endif; ?>
    <?php endif; ?>

    <nav class="pager">
         <div class="filters-pag">
            <label class="filter-label">
                <span>Items per page:</span>
                <select class="filter-select" onchange="location.href='index.php?page=matches&view=<?= htmlspecialchars($view) ?>&p=1&order=<?= htmlspecialchars($orderMatches) ?>&perPage=' + this.value;">
                    <?php foreach ([5,10,20,50] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $opt === (int)$perPage ? 'selected' : '' ?>>
                            <?= $opt ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-label">
                <span>Order:</span>
                <select class="filter-select" onchange="location.href='index.php?page=matches&view=<?= htmlspecialchars($view) ?>&p=1&perPage=<?= htmlspecialchars($perPage) ?>&order=' + this.value;">
                    <option value="date_asc" <?= $orderMatches === 'date_asc' ? 'selected' : '' ?>>Date ASC</option>
                    <option value="date_desc" <?= $orderMatches === 'date_desc' ? 'selected' : '' ?>>Date DESC</option>
                </select>
            </label>
        </div>

        <?php if ($totalPagesMb > 1 && $searchMatches === ''): ?>
            <nav class="pager">
                <a href="<?= build_matches_url(1, $perPage, $view, $orderMatches, $searchMatches) ?>"
                class="btn<?= $currentPage === 1 ? ' is-disabled' : '' ?>">« First</a>

                <a href="<?= build_matches_url(max(1, $currentPage - 1), $perPage, $view, $orderMatches, $searchMatches) ?>"
                class="btn<?= $currentPage === 1 ? ' is-disabled' : '' ?>">‹ Prev</a>

                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                    <?php if ($p === $currentPage): ?>
                        <span class="page current"><?= htmlspecialchars((string)$p) ?></span>
                    <?php else: ?>
                        <a href="<?= build_matches_url($p, $perPage, $view, $orderMatches, $searchMatches) ?>" class="page">
                            <?= htmlspecialchars((string)$p) ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <a href="<?= build_matches_url(min($totalPagesMb, $currentPage + 1), $perPage, $view, $orderMatches, $searchMatches) ?>"
                class="btn<?= $currentPage === $totalPagesMb ? ' is-disabled' : '' ?>">Next ›</a>

                <a href="<?= build_matches_url($totalPagesMb, $perPage, $view, $orderMatches, $searchMatches) ?>"
                class="btn<?= $currentPage === $totalPagesMb ? ' is-disabled' : '' ?>">Last »</a>
            </nav>
        <?php endif; ?>
    </nav>
</main>
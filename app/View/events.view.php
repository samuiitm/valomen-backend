<main>
    <div class="events">
        <section class="events-block">
            <div class="events-header <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>upcoming-events<?php endif; ?>">
                <span>UPCOMING EVENTS</span>
            </div>

            <?php if (empty($ongoingEvents) && empty($upcomingEvents)): ?>
                <p class="info-empty">No upcoming events.</p>
            <?php else: ?>

                <?php foreach ($ongoingEvents as $event): ?>
                    <article class="event-block">
                        <div class="event-info">
                            <div class="event-head">
                                <h3><?= htmlspecialchars($event['name']) ?></h3>    
                                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                                    <div class="event-admin-actions">
                                        <a href="index.php?page=event_edit&id=<?= (int)$event['id'] ?>"
                                        class="event-admin-btn edit"
                                        title="Edit event">
                                            ✎
                                        </a>
                                        <a href="index.php?page=event_delete&id=<?= (int)$event['id'] ?>"
                                        class="event-admin-btn delete js-delete-event"
                                        data-event-label="<?= htmlspecialchars($event['name']) ?>"
                                        title="Delete event">
                                            🗑
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="event-secondary-info">
                                <div class="sec-info event-status">
                                    <span class="status <?= htmlspecialchars($event['status']) ?>">
                                        <?= htmlspecialchars($event['status']) ?>
                                    </span>
                                    <span class="info-title">STATUS</span>
                                </div>
                                <div class="sec-info event-prize">
                                    <span>$<?= formatCurrency((int)$event['prize']) ?></span>
                                    <span class="info-title">PRIZE POOL</span>
                                </div>
                                <div class="sec-info event-dates">
                                    <span><?= formatEventDate($event['start_date'], $event['end_date']) ?></span>
                                    <span class="info-title">DATES</span>
                                </div>
                                <div class="event-region">
                                    <img src="<?= getFlagPath($event['region'] ?? '') ?>" alt="Flag">
                                    <span class="info-title">REGION</span>
                                </div>
                            </div>
                        </div>
                        <div class="event-logo">
                            <img src="assets/icons/events/<?= htmlspecialchars($event['logo']) ?>">
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php foreach ($upcomingEvents as $event): ?>
                    <article class="event-block">
                        <div class="event-info">
                            <div class="event-head">
                                <h3><?= htmlspecialchars($event['name']) ?></h3>    
                                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                                    <div class="event-admin-actions">
                                        <a href="index.php?page=event_edit&id=<?= (int)$event['id'] ?>"
                                        class="event-admin-btn edit"
                                        title="Edit event">
                                            ✎
                                        </a>
                                        <a href="index.php?page=event_delete&id=<?= (int)$event['id'] ?>"
                                        class="event-admin-btn delete js-delete-event"
                                        data-event-label="<?= htmlspecialchars($event['name']) ?>"
                                        title="Delete event">
                                            🗑
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="event-secondary-info">
                                <div class="sec-info event-status">
                                    <span class="status <?= htmlspecialchars($event['status']) ?>">
                                        <?= htmlspecialchars($event['status']) ?>
                                    </span>
                                    <span class="info-title">STATUS</span>
                                </div>
                                <div class="sec-info event-prize">
                                    <span>$<?= formatCurrency((int)$event['prize']) ?></span>
                                    <span class="info-title">PRIZE POOL</span>
                                </div>
                                <div class="sec-info event-dates">
                                    <span><?= formatEventDate($event['start_date'], $event['end_date']) ?></span>
                                    <span class="info-title">DATES</span>
                                </div>
                                <div class="event-region">
                                    <img src="<?= getFlagPath($event['region'] ?? '') ?>" alt="Flag">
                                    <span class="info-title">REGION</span>
                                </div>
                            </div>
                        </div>
                        <div class="event-logo">
                            <img src="assets/icons/events/<?= htmlspecialchars($event['logo']) ?>">
                        </div>
                    </article>
                <?php endforeach; ?>

            <?php endif; ?>
        </section>

        <section class="events-block">
            <div class="events-header">
                <span>COMPLETED EVENTS</span>
                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                    <a href="index.php?page=event_create" class="add-event-btn">
                        <span class="add-event-plus">+</span>
                        <span>Add event</span>
                    </a>
                <?php endif; ?>
            </div>
            <?php if (empty($completedEvents)): ?>
                <p class="info-empty">No completed events.</p>
            <?php else: ?>
                <?php foreach ($completedEvents as $event): ?>
                    <article class="event-block">
                        <div class="event-info">
                            <div class="event-head">
                                <h3><?= htmlspecialchars($event['name']) ?></h3>    
                                <?php if (!empty($_SESSION['is_admin']) && !empty($_SESSION['edit_mode'])): ?>
                                    <div class="event-admin-actions">
                                        <a href="index.php?page=event_edit&id=<?= (int)$event['id'] ?>"
                                        class="event-admin-btn edit"
                                        title="Edit event">
                                            ✎
                                        </a>
                                        <a href="index.php?page=event_delete&id=<?= (int)$event['id'] ?>"
                                        class="event-admin-btn delete js-delete-event"
                                        data-event-label="<?= htmlspecialchars($event['name']) ?>"
                                        title="Delete event">
                                            🗑
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="event-secondary-info">
                                <div class="sec-info event-status">
                                    <span class="status <?= htmlspecialchars($event['status']) ?>"><?= htmlspecialchars($event['status']) ?></span>
                                    <span class="info-title">STATUS</span>
                                </div>
                                <div class="sec-info event-prize">
                                    <span>$<?= formatCurrency((int)$event['prize']) ?></span>
                                    <span class="info-title">PRIZE POOL</span>
                                </div>
                                <div class="sec-info event-dates">
                                    <span><?= formatEventDate($event['start_date'], $event['end_date']) ?></span>
                                    <span class="info-title">DATES</span>
                                </div>
                                <div class="event-region">
                                    <img src="<?= getFlagPath($event['region'] ?? '') ?>" alt="Flag">
                                    <span class="info-title">REGION</span>
                                </div>
                            </div>
                        </div>
                        <div class="event-logo">
                            <img src="assets/icons/events/<?= htmlspecialchars($event['logo']) ?>">
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
    <nav class="pager">
        <div class="filters-pag">
            <label class="filter-label">
                <span>Items per page:</span>
                <select class="filter-select" onchange="location.href='index.php?page=events&view=<?= htmlspecialchars($view) ?>&p=1&order=<?= htmlspecialchars($orderevents) ?>&perPage=' + this.value;">
                    <?php foreach ([5,10,20,50] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $opt === (int)$perPageEvents ? 'selected' : '' ?>>
                            <?= $opt ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="filter-label">
                <span>Order:</span>
                <select class="filter-select" onchange="location.href='index.php?page=events&view=<?= htmlspecialchars($view) ?>&p=1&perPage=<?= htmlspecialchars($perPageEvents) ?>&order=' + this.value;">
                    <option value="date_asc" <?= $orderEvents === 'date_asc' ? 'selected' : '' ?>>Date ASC</option>
                    <option value="date_desc" <?= $orderEvents === 'date_desc' ? 'selected' : '' ?>>Date DESC</option>
                </select>
            </label>
        </div>

        <a href="<?= build_events_url(1, $perPageEvents) ?>"
            class="btn<?= $currentPageEvents === 1 ? ' is-disabled' : '' ?>">« First</a>

        <a href="<?= build_events_url(max(1, $currentPageEvents - 1), $perPageEvents) ?>"
            class="btn<?= $currentPageEvents === 1 ? ' is-disabled' : '' ?>">‹ Prev</a>

        <?php for ($p = $startPageEvents; $p <= $endPageEvents; $p++): ?>
            <?php if ($p === $currentPageEvents): ?>
                <span class="page current"><?= htmlspecialchars((string)$p) ?></span>
            <?php else: ?>
                <a href="<?= build_events_url($p, $perPageEvents) ?>" class="page">
                    <?= htmlspecialchars((string)$p) ?>
                </a>
            <?php endif; ?>
        <?php endfor; ?>

        <a href="<?= build_events_url(min($totalPagesEventsMb, $currentPageEvents + 1), $perPageEvents) ?>"
            class="btn<?= $currentPageEvents === $totalPagesEventsMb ? ' is-disabled' : '' ?>">Next ›</a>

        <a href="<?= build_events_url($totalPagesEventsMb, $perPageEvents) ?>"
            class="btn<?= $currentPageEvents === $totalPagesEventsMb ? ' is-disabled' : '' ?>">Last »</a>
    </nav>
</main>
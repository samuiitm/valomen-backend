<main>
    <div class="events">
        <section class="events-block">
            <div class="events-header">
                <span>UPCOMING EVENTS</span>
            </div>
            <?php if (empty($upcomingEvents)): ?>
                <p class="info-empty">No upcoming events.</p>
            <?php else: ?>
                
                <?php foreach ($ongoingEvents as $event): ?>
                    <article class="event-block">
                        <div class="event-info">
                            <h3><?= htmlspecialchars($event['name']) ?></h3>    
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
                <?php foreach ($upcomingEvents as $event): ?>
                    <article class="event-block">
                        <div class="event-info">
                            <h3><?= htmlspecialchars($event['name']) ?></h3>    
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
        <section class="events-block">
            <div class="events-header">
                <span>COMPLETED EVENTS</span>
            </div>
            <?php if (empty($completedEvents)): ?>
                <p class="info-empty">No completed events.</p>
            <?php else: ?>
                <?php foreach ($completedEvents as $event): ?>
                    <article class="event-block">
                        <div class="event-info">
                            <h3><?= htmlspecialchars($event['name']) ?></h3>    
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
</main>
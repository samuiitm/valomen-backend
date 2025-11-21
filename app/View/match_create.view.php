<main class="main-matches editor-page">
    <div class="matches-header">
        <h1>Create match</h1>
        <p>Select an event and its participants to create a new match.</p>
    </div>

    <section class="match-create-section">

        <form class="match-event-select" method="GET" action="index.php">
            <input type="hidden" name="page" value="match_create">

            <label class="block">
                <span>Event</span>
                <select name="event_id" onchange="this.form.submit()">
                    <option value="">Select event</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= htmlspecialchars($event['id']) ?>"
                            <?= ($selectedEventId ?? null) == $event['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <?php if (!empty($errors['event_id'])): ?>
                <p class="field-error"><?= htmlspecialchars($errors['event_id']) ?></p>
            <?php endif; ?>
        </form>

        <?php if (!empty($selectedEventId)): ?>
            <form class="match-create-form" method="POST" action="index.php?page=match_create">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($selectedEventId) ?>">

                <div class="form-row">
                    <div class="block">
                        <label>Team 1</label>
                        <select name="team_1" required>
                            <option value="">Select team</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?= htmlspecialchars($team['id']) ?>"
                                    <?= (int)($old['team_1'] ?? 0) === (int)$team['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($team['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['team_1'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['team_1']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="block">
                        <label>Team 2</label>
                        <select name="team_2" required>
                            <option value="">Select team</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?= htmlspecialchars($team['id']) ?>"
                                    <?= (int)($old['team_2'] ?? 0) === (int)$team['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($team['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['team_2'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['team_2']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="block">
                        <label>Date</label>
                        <input
                            type="date"
                            name="date"
                            value="<?= htmlspecialchars($old['date'] ?? '') ?>"
                            required
                        >
                        <?php if (!empty($errors['date'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['date']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="block">
                        <label>Hour</label>
                        <input
                            type="time"
                            name="hour"
                            value="<?= htmlspecialchars($old['hour'] ?? '') ?>"
                            required
                        >
                        <?php if (!empty($errors['hour'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['hour']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="block">
                        <label>Score Team 1</label>
                        <input
                            type="number"
                            name="score_team_1"
                            min="0"
                            max="5"
                            value="<?= htmlspecialchars($old['score_team_1'] ?? '') ?>"
                        >
                        <?php if (!empty($errors['score_team_1'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['score_team_1']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="block">
                        <label>Score Team 2</label>
                        <input
                            type="number"
                            name="score_team_2"
                            min="0"
                            max="5"
                            value="<?= htmlspecialchars($old['score_team_2'] ?? '') ?>"
                        >
                        <?php if (!empty($errors['score_team_2'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['score_team_2']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="block">
                        <label>Best of</label>
                        <select name="best_of" required>
                            <?php $bo = (int)($old['best_of'] ?? 3); ?>
                            <option value="1" <?= $bo === 1 ? 'selected' : '' ?>>BO1</option>
                            <option value="3" <?= $bo === 3 ? 'selected' : '' ?>>BO3</option>
                            <option value="5" <?= $bo === 5 ? 'selected' : '' ?>>BO5</option>
                        </select>
                        <?php if (!empty($errors['best_of'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['best_of']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="block">
                        <label>Stage</label>
                        <input
                            type="text"
                            name="event_stage"
                            placeholder="Upper Round 1, Group A, etc."
                            value="<?= htmlspecialchars($old['event_stage'] ?? '') ?>"
                            required
                        >
                        <?php if (!empty($errors['event_stage'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['event_stage']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($errors['global'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['global']) ?></p>
                <?php endif; ?>

                <button class="send-button" type="submit">Create match</button>
            </form>
        <?php endif; ?>
    </section>
</main>
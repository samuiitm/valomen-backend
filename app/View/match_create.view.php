<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Create match</h1>
            <p>Select an event and its participants to create a new match.</p>
        </div>

        <form class="form" method="GET" action="match_create">
            <input type="hidden" name="page" value="match_create">

            <div class="form-row">
                <div class="form-field">
                    <label for="event_id_select">
                        Event <span class="obligatory">*</span>
                    </label>
                    <select id="event_id_select" name="event_id" onchange="this.form.submit()">
                        <option value="">Select event</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?= htmlspecialchars($event['id']) ?>"
                                <?= ($selectedEventId ?? null) == $event['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($event['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['event_id'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['event_id']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <?php if (!empty($selectedEventId)): ?>
            <form class="form" method="POST" action="match_create">
                <input type="hidden" name="event_id" value="<?= htmlspecialchars($selectedEventId) ?>">

                <div class="form-row">
                    <div class="form-field">
                        <label for="team_1">Team 1</label>
                        <select name="team_1" id="team_1">
                            <option value="">TBD</option>
                            <?php foreach ($teams as $team): ?>
                                <option
                                    value="<?= (int)$team['id'] ?>"
                                    <?= ((int)$old['team_1'] === (int)$team['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($team['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['team_1'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['team_1']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="team_2">Team 2</label>
                        <select name="team_2" id="team_2">
                            <option value="">TBD</option>
                            <?php foreach ($teams as $team): ?>
                                <option
                                    value="<?= (int)$team['id'] ?>"
                                    <?= ((int)$old['team_2'] === (int)$team['id']) ? 'selected' : '' ?>
                                >
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
                    <div class="form-field">
                        <label for="date">Date <span class="obligatory">*</span></label>
                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="<?= htmlspecialchars($old['date'] ?? '') ?>"
                            required
                        >
                        <?php if (!empty($errors['date'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['date']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="hour">Hour <span class="obligatory">*</span></label>
                        <input
                            type="time"
                            id="hour"
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
                    <div class="form-field">
                        <label for="score_team_1">Score Team 1</label>
                        <input
                            type="number"
                            id="score_team_1"
                            name="score_team_1"
                            min="0"
                            max="5"
                            value="<?= htmlspecialchars($old['score_team_1'] ?? '') ?>"
                        >
                        <?php if (!empty($errors['score_team_1'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['score_team_1']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="score_team_2">Score Team 2</label>
                        <input
                            type="number"
                            id="score_team_2"
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
                    <div class="form-field">
                        <label for="best_of">Best of <span class="obligatory">*</span></label>
                        <?php $bo = (int)($old['best_of'] ?? 3); ?>
                        <select id="best_of" name="best_of" required>
                            <option value="1" <?= $bo === 1 ? 'selected' : '' ?>>BO1</option>
                            <option value="3" <?= $bo === 3 ? 'selected' : '' ?>>BO3</option>
                            <option value="5" <?= $bo === 5 ? 'selected' : '' ?>>BO5</option>
                        </select>
                        <?php if (!empty($errors['best_of'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['best_of']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-field">
                        <label for="event_stage">Stage <span class="obligatory">*</span></label>
                        <input
                            type="text"
                            id="event_stage"
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
                    <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="matches" class="btn-secondary">Cancel</a>
                    <button class="btn-primary" type="submit">Create match</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
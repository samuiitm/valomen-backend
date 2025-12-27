<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Create event</h1>
            <p>Create a new Valorant event and assign participating teams.</p>
        </div>

        <form method="POST" action="event_create" class="form">
            <div class="form-row">
                <div class="form-field">
                    <label for="name">Name <span class="obligatory">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="start_date">Start date <span class="obligatory">*</span></label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= htmlspecialchars($old['start_date'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['start_date'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['start_date']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="end_date">End date</label>
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="<?= htmlspecialchars($old['end_date'] ?? '') ?>"
                    >
                    <?php if (!empty($errors['end_date'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['end_date']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="prize">Prize pool (USD)</label>
                    <input
                        type="number"
                        min="0"
                        id="prize"
                        name="prize"
                        value="<?= htmlspecialchars($old['prize'] ?? '') ?>"
                    >
                    <?php if (!empty($errors['prize'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['prize']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="region">Region <span class="obligatory">*</span></label>
                    <input
                        type="text"
                        id="region"
                        name="region"
                        placeholder="e.g. eu, us, br..."
                        value="<?= htmlspecialchars($old['region'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['region'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['region']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="logo">Logo filename <span class="obligatory">*</span></label>
                    <input
                        type="text"
                        id="logo"
                        name="logo"
                        placeholder="1.png"
                        value="<?= htmlspecialchars($old['logo'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['logo'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['logo']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field full-width">
                    <label for="teams">Participating teams</label>
                    <select id="teams" name="teams[]" multiple size="8">
                        <?php
                            $selectedTeams = $old['teams'] ?? [];
                            $selectedMap   = [];
                            foreach ($selectedTeams as $tid) {
                                $selectedMap[(int)$tid] = true;
                            }
                        ?>
                        <?php foreach ($allTeams as $team): ?>
                            <option
                                value="<?= (int)$team['id'] ?>"
                                <?= isset($selectedMap[(int)$team['id']]) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($team['name']) ?> (<?= htmlspecialchars($team['country']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['teams'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['teams']) ?></p>
                    <?php endif; ?>
                    <p class="field-help">Hold Ctrl (Cmd on Mac) to select multiple teams.</p>
                </div>
            </div>

            <?php if (!empty($errors['global'])): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="events" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Create event</button>
            </div>
        </form>
    </section>
</main>
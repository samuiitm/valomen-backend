<?php
$matchId = (int)($match['id'] ?? 0);
?>

<main class="match-admin-page">
    <section class="match-admin-card">
        <header class="match-admin-header">
            <h1>Edit match</h1>
            <p>Modify the match information, score and stage details.</p>
        </header>

        <?php if (!empty($errors['global'])): ?>
            <p class="error-message">
                <?= htmlspecialchars($errors['global']) ?>
            </p>
        <?php endif; ?>

        <form
            method="POST" action="index.php?page=match_edit&id=<?= $matchId ?>" class="match-admin-form">

            <div class="field-row">
                <div class="field-block">
                    <label for="event_id">Event <span class="obligatory">*</span></label>
                    <select name="event_id" id="event_id" required>
                        <option value="">Select event</option>
                        <?php foreach ($events as $event): ?>
                            <option
                                value="<?= (int)$event['id'] ?>"
                                <?= ((int)$old['event_id'] === (int)$event['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($event['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (!empty($errors['event_id'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['event_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field-block">
                    <label for="event_stage">Stage <span class="obligatory">*</span></label>
                    <input
                        type="text"
                        id="event_stage"
                        name="event_stage"
                        required
                        value="<?= htmlspecialchars($old['event_stage']) ?>"
                    >

                    <?php if (!empty($errors['event_stage'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['event_stage']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field-row">
                <div class="field-block">
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

                <div class="field-block">
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

            <div class="field-row">
                <div class="field-block">
                    <label for="date">Date <span class="obligatory">*</span></label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        required
                        value="<?= htmlspecialchars($old['date']) ?>"
                    >

                    <?php if (!empty($errors['date'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['date']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field-block">
                    <label for="hour">Hour <span class="obligatory">*</span></label>
                    <input
                        type="time"
                        id="hour"
                        name="hour"
                        required
                        value="<?= htmlspecialchars($old['hour']) ?>"
                    >

                    <?php if (!empty($errors['hour'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['hour']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field-block">
                    <label for="best_of">Best of <span class="obligatory">*</span></label>
                    <select name="best_of" id="best_of" required>
                        <option value="1" <?= (int)$old['best_of'] === 1 ? 'selected' : '' ?>>BO1</option>
                        <option value="3" <?= (int)$old['best_of'] === 3 ? 'selected' : '' ?>>BO3</option>
                        <option value="5" <?= (int)$old['best_of'] === 5 ? 'selected' : '' ?>>BO5</option>
                    </select>

                    <?php if (!empty($errors['best_of'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['best_of']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="field-row">
                <div class="field-block">
                    <label for="score_team_1">Score Team 1</label>
                    <input
                        type="number"
                        id="score_team_1"
                        name="score_team_1"
                        min="0"
                        value="<?= htmlspecialchars((string)$old['score_team_1']) ?>"
                    >

                    <?php if (!empty($errors['score_team_1'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['score_team_1']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field-block">
                    <label for="score_team_2">Score Team 2</label>
                    <input
                        type="number"
                        id="score_team_2"
                        name="score_team_2"
                        min="0"
                        value="<?= htmlspecialchars((string)$old['score_team_2']) ?>"
                    >

                    <?php if (!empty($errors['score_team_2'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['score_team_2']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="match-admin-actions-form">
                <a href="index.php?page=matches" class="btn-secondary">
                    Cancel
                </a>

                <button type="submit" class="send-button">
                    Save changes
                </button>
            </div>
        </form>
    </section>
</main>
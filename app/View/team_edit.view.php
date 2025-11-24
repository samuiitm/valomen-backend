<?php
$old    = $data['old']    ?? [];
$errors = $data['errors'] ?? [];
?>

<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Edit team</h1>
            <a href="index.php?page=admin&section=teams" class="btn-secondary">← Back to teams</a>
        </div>

        <form method="post" class="form">
            <div class="form-row">
                <div class="form-field">
                    <label for="name">Name <span class="obligatory">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES) ?>"
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="country">Country code</label>
                    <input
                        type="text"
                        id="country"
                        name="country"
                        maxlength="5"
                        value="<?= htmlspecialchars($old['country'] ?? '', ENT_QUOTES) ?>"
                    >
                    <?php if (!empty($errors['country'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['country']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($errors['global'])): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="index.php?page=admin&section=teams" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </section>
</main>
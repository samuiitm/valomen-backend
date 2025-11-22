<?php
$old    = $data['old']    ?? [];
$errors = $data['errors'] ?? [];
?>

<main class="admin-main">
    <section class="admin-section">
        <div class="admin-section-header">
            <h2>Edit team</h2>
            <a href="index.php?page=admin&section=teams" class="admin-back-link">← Back to teams</a>
        </div>

        <form method="post" class="admin-form">
            <div class="field-block">
                <label for="name">Name</label>
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

            <div class="field-block">
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

            <?php if (!empty($errors['global'])): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Save changes</button>
                <a href="index.php?page=admin&section=teams" class="secondary-btn">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php
$old    = $data['old']    ?? [];
$errors = $data['errors'] ?? [];
?>

<main class="admin-main">
    <section class="admin-section">
        <div class="admin-section-header">
            <h2>Edit user</h2>
            <a href="index.php?page=admin&section=users" class="admin-back-link">← Back to users</a>
        </div>

        <form method="post" class="admin-form">
            <div class="field-block">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES) ?>"
                >
                <?php if (!empty($errors['username'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['username']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field-block">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES) ?>"
                >
                <?php if (!empty($errors['email'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field-block">
                <label for="points">Points</label>
                <input
                    type="number"
                    id="points"
                    name="points"
                    min="0"
                    value="<?= htmlspecialchars($old['points'] ?? '0', ENT_QUOTES) ?>"
                >
                <?php if (!empty($errors['points'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['points']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field-block">
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="admin"
                        value="1"
                        <?= !empty($old['admin']) && $old['admin'] === '1' ? 'checked' : '' ?>
                    >
                    Admin
                </label>
                <?php if (!empty($errors['admin'])): ?>
                    <p class="field-error"><?= htmlspecialchars($errors['admin']) ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($errors['global'])): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="primary-btn">Save changes</button>
                <a href="index.php?page=admin&section=users" class="secondary-btn">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php
$old    = $data['old']    ?? [];
$errors = $data['errors'] ?? [];
?>

<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Edit user</h1>
            <a href="../admin?section=users" class="btn-secondary">← Back to users</a>
        </div>

        <form method="post" class="form">
            <div class="form-row">
                <div class="form-field">
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
            </div>

            <div class="form-row">
                <div class="form-field">
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
            </div>

            <div class="form-row">
                <div class="form-field">
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
            </div>

            <?php if (!empty($errors['global'])): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="../admin?section=users" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </section>
</main>
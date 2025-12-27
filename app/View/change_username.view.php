<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Change username</h1>
            <p>Update your public name.</p>
        </div>

        <form class="form" method="post" action="profile/username">
            <div class="form-row">
                <div class="form-field full-width">
                    <label for="username">
                        New username <span class="obligatory">*</span>
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                        required
                    >
                    <?php if (!empty($errors['username'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['username']) ?></p>
                    <?php else: ?>
                        <p class="field-help">
                            4-20 chars, lowercase letters, numbers, "." or "_".
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($errors['global'])): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="profile" class="btn-secondary">Cancel</a>
                <button class="btn-primary" type="submit">Save username</button>
            </div>
        </form>
    </section>
</main>
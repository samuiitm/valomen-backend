<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Change password</h1>
            <p>Keep your account secure with a strong password.</p>
        </div>

        <form class="form" method="post">
            <div class="form-row">
                <div class="form-field full-width">
                    <label for="current_password">
                        Current password <span class="obligatory">*</span>
                    </label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                    >
                    <?php if (!empty($errors['current_password'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['current_password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="new_password">
                        New password <span class="obligatory">*</span>
                    </label>
                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        required
                    >
                    <?php if (!empty($errors['new_password'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['new_password']) ?></p>
                    <?php else: ?>
                        <p class="field-help">
                            Min 8 chars, 1 letter, 1 number and 1 symbol.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-field">
                    <label for="confirm_password">
                        Confirm new password <span class="obligatory">*</span>
                    </label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                    >
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($errors['global'])): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="../profile" class="btn-secondary">Cancel</a>
                <button class="btn-primary" type="submit">Save password</button>
            </div>
        </form>
    </section>
</main>
<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <div>
                <h1>Reset password</h1>
                <p>Choose a new password for your account.</p>
            </div>
        </div>

        <?php if (!empty($errors['global'])): ?>
            <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>

            <div class="form-actions">
                <a class="btn-secondary" href="<?= htmlspecialchars(url('forgot_password')) ?>">Request another reset link</a>
                <a class="btn-primary" href="<?= htmlspecialchars(url('login')) ?>">Back to login</a>
            </div>
        <?php else: ?>

            <form class="form" method="POST" action="<?= htmlspecialchars(url('reset_password')) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div class="form-row">
                    <div class="form-field full-width">
                        <label>New password <span class="obligatory">*</span></label>
                        <input
                            type="password"
                            name="new_password"
                            placeholder="New password"
                            autocomplete="new-password"
                        >
                        <?php if (!empty($errors['new_password'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['new_password']) ?></p>
                        <?php endif; ?>
                        <p class="field-help">At least 8 chars, 1 letter, 1 number and 1 symbol.</p>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field full-width">
                        <label>Confirm new password <span class="obligatory">*</span></label>
                        <input
                            type="password"
                            name="confirm_password"
                            placeholder="Repeat new password"
                            autocomplete="new-password"
                        >
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a class="btn-secondary" href="<?= htmlspecialchars(url('login')) ?>">Back to login</a>
                    <button class="btn-primary" type="submit">Update password</button>
                </div>
            </form>

        <?php endif; ?>
    </section>
</main>
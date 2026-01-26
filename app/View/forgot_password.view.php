<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <div>
                <h1>Forgot your password?</h1>
                <p>Write your email and we will send you a reset link.</p>
            </div>
        </div>

        <?php if (!empty($sent)): ?>
            <p class="field-help">
                If that email exists in our system, we have sent you a link to reset your password.
            </p>
        <?php endif; ?>

        <?php if (!empty($errors['global'])): ?>
            <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
        <?php endif; ?>

        <form class="form" method="POST" action="<?= htmlspecialchars(url('forgot_password')) ?>">
            <div class="form-row">
                <div class="form-field full-width">
                    <label>Email <span class="obligatory">*</span></label>
                    <input
                        type="email"
                        name="email"
                        placeholder="your@email.com"
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        autocomplete="email"
                    >
                    <?php if (!empty($errors['email'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <a class="btn-secondary" href="<?= htmlspecialchars(url('login')) ?>">Back to login</a>
                <button class="btn-primary" type="submit">Send reset link</button>
            </div>
        </form>
    </section>
</main>
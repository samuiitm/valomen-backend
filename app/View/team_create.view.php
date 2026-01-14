<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Create team</h1>
            <p>Add a new team to the database.</p>
        </div>

        <form method="post" class="form">
            <div class="form-row">
                <div class="form-field">
                    <label for="name">Name <span class="obligatory">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?= htmlspecialchars($old['name']) ?>"
                    >
                    <?php if ($errors['name']): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-field">
                    <label for="country">Country (2-letter code)</label>
                    <input
                        type="text"
                        id="country"
                        name="country"
                        value="<?= htmlspecialchars($old['country']) ?>"
                    >
                    <?php if ($errors['country']): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['country']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($errors['global']): ?>
                <p class="field-error global-error"><?= htmlspecialchars($errors['global']) ?></p>
            <?php endif; ?>

            <div class="form-actions">
                <a href="../admin?section=teams" class="btn-secondary">Cancel</a>
                <button class="btn-primary" type="submit">Create team</button>
            </div>
        </form>
    </section>
</main>
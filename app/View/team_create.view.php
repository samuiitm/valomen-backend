<main class="admin-main">
    <div class="admin-section">
        <h1>Create team</h1>

        <form method="post" class="admin-form">

            <label>
                <span>Name</span>
                <input type="text" name="name"
                       value="<?= htmlspecialchars($old['name']) ?>">
                <?php if ($errors['name']): ?>
                    <div class="error"><?= htmlspecialchars($errors['name']) ?></div>
                <?php endif; ?>
            </label>

            <label>
                <span>Country (2-letter code)</span>
                <input type="text" name="country"
                       value="<?= htmlspecialchars($old['country']) ?>">
                <?php if ($errors['country']): ?>
                    <div class="error"><?= htmlspecialchars($errors['country']) ?></div>
                <?php endif; ?>
            </label>

            <?php if ($errors['global']): ?>
                <div class="error"><?= htmlspecialchars($errors['global']) ?></div>
            <?php endif; ?>

            <button class="admin-save-btn">Create team</button>

        </form>
    </div>
</main>
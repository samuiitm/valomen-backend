<?php
$avatarFilename = $user['logo'] ?? null;
if (!empty($avatarFilename)) {
    $avatarSrc = 'assets/img/user-avatars/' . htmlspecialchars($avatarFilename);
} else {
    $avatarSrc = 'assets/img/default-avatar.png';
}
?>

<main class="profile-page">
    <section class="profile-card">
        <h1>Your profile</h1>

        <?php if (!empty($success)): ?>
            <div class="profile-success">
                Profile updated successfully.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['global'])): ?>
            <div class="profile-error">
                <?= htmlspecialchars($errors['global']) ?>
            </div>
        <?php endif; ?>

        <form action="index.php?page=profile" method="post" enctype="multipart/form-data" class="profile-form">
            <div class="profile-avatar-block">
                <div class="avatar-preview">
                    <img src="<?= $avatarSrc ?>" alt="User avatar">
                </div>
                <div class="avatar-input">
                    <label for="avatar">Profile picture</label>
                    <input type="file" name="avatar" id="avatar" accept="image/*">
                    <?php if (!empty($errors['avatar'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['avatar']) ?></p>
                    <?php else: ?>
                        <p class="field-hint">JPG, PNG or WEBP. Max 4MB. 500x500 center cropped.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-fields">
                <div class="field-block">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        maxlength="50"
                        value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                        required
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
                        value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                        readonly
                        disabled
                    >
                    <p class="field-hint">Email change will be available in the future.</p>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </section>
</main>
<?php
$avatarFilename = $user['logo'] ?? null;
if (!empty($avatarFilename)) {
    $avatarSrc = 'assets/img/user-avatars/' . htmlspecialchars($avatarFilename);
} else {
    $avatarSrc = 'assets/img/default-avatar.png';
}

$successMessage = !empty($success) ? $success : '';
?>

<main class="profile-page">
    <section class="profile-card">
        <h1>Your profile</h1>

        <?php if (!empty($successMessage)): ?>
            <div class="profile-success">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['global'])): ?>
            <div class="profile-error">
                <?= htmlspecialchars($errors['global']) ?>
            </div>
        <?php endif; ?>

        <form action="profile/avatar" method="post" enctype="multipart/form-data" class="profile-form">
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

            <div class="avatar-actions">
                <button type="submit" class="btn-primary">Upload new picture</button>
            </div>
        </form>

        <div class="profile-fields">
            <a href="profile/username" class="field-block">
                <div class="info-block">
                    <span class="profile-label">Username</span>
                    <span class="label-value"><?= htmlspecialchars($user['username'] ?? '') ?></span>
                </div>
                <span class="arrow"></span>
            </a>

            <a href="profile/email" class="field-block">
                <div class="info-block">
                    <span class="profile-label">Email</span>
                    <span class="label-value"><?= htmlspecialchars($user['email'] ?? '') ?></span>
                </div>
                <span class="arrow"></span>
            </a>

            <a href="profile/password" class="field-block">
                <div class="info-block">
                    <span class="profile-label">Password</span>
                    <span class="label-value">**********</span>
                </div>
                <span class="arrow"></span>
            </a>
        </div>
    </section>
</main>
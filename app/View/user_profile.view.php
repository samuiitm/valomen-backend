<?php
$avatarFilename = $user['logo'] ?? null;
if (!empty($avatarFilename)) {
    $avatarSrc = 'assets/img/user-avatars/' . htmlspecialchars($avatarFilename);
} else {
    $avatarSrc = 'assets/img/default-avatar.png';
}

$pendingAvatar = $pendingAvatar ?? '';
$pendingAvatarSrc = '';
if (!empty($pendingAvatar)) {
    $pendingAvatarSrc = 'assets/img/user-avatars/' . htmlspecialchars($pendingAvatar);
}
?>

<main class="profile-page">
    <section class="profile-card">
        <h1>Your profile</h1>

        <?php if (!empty($success)): ?>
            <div class="profile-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors['global'])): ?>
            <div class="profile-error">
                <?= htmlspecialchars($errors['global']) ?>
            </div>
        <?php endif; ?>

        <form id="avatarForm" action="profile/avatar" method="post" enctype="multipart/form-data" class="profile-form">
            <div class="profile-avatar-block">
                <div class="avatar-preview">
                    <img src="<?= $avatarSrc ?>" alt="User avatar">
                </div>

                <div class="avatar-input">
                    <label for="avatar">Profile picture</label>

                    <input type="file" name="avatar" id="avatar" accept="image/*" style="display:none;">

                    <button type="button" class="send-button" id="chooseAvatarBtn">
                        Change photo
                    </button>

                    <?php if (!empty($errors['avatar'])): ?>
                        <p class="field-error"><?= htmlspecialchars($errors['avatar']) ?></p>
                    <?php else: ?>
                        <p class="field-hint">Select an image and we’ll ask you to confirm.</p>
                    <?php endif; ?>
                </div>
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

    <?php if (!empty($pendingAvatar)): ?>
    <div class="modal-overlay">
        <div class="modal-card">
        <h2>Change profile picture?</h2>
        <p class="modal-subtitle">Do you want to set this image as your new avatar?</p>

        <div class="modal-avatar-preview">
            <img src="assets/img/user-avatars/<?= htmlspecialchars($pendingAvatar) ?>" alt="New avatar">
        </div>

        <form action="profile/avatar/confirm" method="post" class="modal-actions">
            <input type="hidden" name="avatar_filename" value="<?= htmlspecialchars($pendingAvatar) ?>">

            <button class="btn-primary" type="submit" name="decision" value="confirm">
            Yes
            </button>

            <button class="btn-cancel" type="submit" name="decision" value="cancel">
            No
            </button>
        </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        (function () {
            const chooseBtn = document.getElementById('chooseAvatarBtn');
            const fileInput = document.getElementById('avatar');
            const form = document.getElementById('avatarForm');

            if (!chooseBtn || !fileInput || !form) return;

            chooseBtn.addEventListener('click', function () {
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length > 0) {
                    form.submit();
                }
            });
        })();
    </script>
</main>
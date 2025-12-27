<?php
$newAvatarSrc = 'assets/img/user-avatars/' . htmlspecialchars($newAvatarFilename ?? $newAvatarFilename ?? $newAvatarFilename ?? '');
$oldAvatarSrc = !empty($user['logo'])
    ? 'assets/img/user-avatars/' . htmlspecialchars($user['logo'])
    : 'assets/img/default-avatar.png';
?>

<main class="form-page">
    <section class="form-card">
        <div class="form-header">
            <h1>Confirm new profile picture</h1>
            <p>Review your new avatar before saving it.</p>
        </div>

        <div class="avatar-compare">
            <div class="avatar-column">
                <h2>Current</h2>
                <img src="<?= $oldAvatarSrc ?>" alt="Current avatar" class="avatar-preview-img">
            </div>
            <div class="avatar-column">
                <h2>New</h2>
                <img src="<?= $newAvatarSrc ?>" alt="New avatar" class="avatar-preview-img">
            </div>
        </div>

        <form class="form" method="post" action="profile/avatar/confirm">
            <input type="hidden" name="avatar_filename" value="<?= htmlspecialchars($newAvatarFilename) ?>">

            <div class="form-actions">
                <button
                    class="btn-secondary"
                    type="submit"
                    name="decision"
                    value="cancel"
                >
                    Cancel
                </button>

                <button
                    class="btn-primary"
                    type="submit"
                    name="decision"
                    value="confirm"
                >
                    Use this picture
                </button>
            </div>
        </form>
    </section>
</main>

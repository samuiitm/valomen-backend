<main class="agents-page">
    <section class="agents-hero">
        <div class="agents-hero-text">
            <span class="agents-kicker">External API</span>
            <h1>Valorant agents</h1>
            <p>
                Aquesta pàgina consumeix una API externa i mostra les dades dins la web.
            </p>
        </div>
    </section>

    <?php if ($error !== null): ?>
        <section class="agents-message agents-message-error">
            <p><?= htmlspecialchars($error) ?></p>
        </section>
    <?php elseif (empty($agents)): ?>
        <section class="agents-message">
            <p>No hi ha agents per mostrar ara mateix.</p>
        </section>
    <?php else: ?>
        <section class="agents-grid">
            <?php foreach ($agents as $agent): ?>
                <?php
                    $name        = $agent['displayName'] ?? 'Agent';
                    $description = $agent['description'] ?? 'Sense descripció';
                    $image       = $agent['displayIcon'] ?? null;
                    $roleName    = $agent['role']['displayName'] ?? 'Sense rol';
                ?>

                <article class="agent-card">
                    <div class="agent-image-wrap">
                        <?php if (!empty($image)): ?>
                            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>">
                        <?php else: ?>
                            <div class="agent-image-placeholder">No image</div>
                        <?php endif; ?>
                    </div>

                    <div class="agent-content">
                        <span class="agent-role"><?= htmlspecialchars($roleName) ?></span>
                        <h2><?= htmlspecialchars($name) ?></h2>
                        <p><?= htmlspecialchars($description) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
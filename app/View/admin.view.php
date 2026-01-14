<main class="admin-main">

    <div>
        <h1>Admin panel</h1>
        <p>Manage teams and users.</p>
    </div>
    
    <div class="admin-tabs">
        <a href="<?= build_admin_url('users', 1, $perPageAdmin, $searchAdmin) ?>"
           class="admin-tab <?= $section === 'users' ? 'active' : '' ?>">
            Users
        </a>
        <a href="<?= build_admin_url('teams', 1, $perPageAdmin, $searchAdmin) ?>"
           class="admin-tab <?= $section === 'teams' ? 'active' : '' ?>">
            Teams
        </a>
    </div>

    <?php
        $basePerPageUrl = 'admin?section=' . urlencode($section)
                        . '&p=1&search=' . urlencode($searchAdmin) . '&perPage=';
    ?>


    <div class="admin-filters">
        <label>
            Items per page:
            <select onchange="window.location.href='<?= htmlspecialchars($basePerPageUrl, ENT_QUOTES) ?>' + this.value">
                <?php foreach ([5,10,20,50] as $opt): ?>
                    <option value="<?= $opt ?>" <?= (int)$perPageAdmin === $opt ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <?php if ($section === 'teams'): ?>

        <section class="admin-section">
            <div class="admin-section-header">
                <h2>Teams</h2>
                <div class="admin-container">
                    <a href="admin/team_create" class="add-team-btn">
                        <span class="add-team-plus">+</span>
                        <span>Add team</span>
                    </a>
                    <form class="admin-search" action="./" method="get">
                        <input type="hidden" name="section" value="teams">
                        <input type="hidden" name="p" value="1">
                        <input type="hidden" name="perPage" value="<?= htmlspecialchars((string)$perPageAdmin) ?>">
                        <input type="text"
                            name="search"
                            placeholder="Search teams..."
                            class="searchInput"
                            value="<?= htmlspecialchars($searchAdmin) ?>">
                    </form>
                </div>
            </div>

            <?php if (empty($teams)): ?>
                <p class="info-empty">No teams found.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Country</th>
                            <th class="admin-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td><?= (int)$team['id'] ?></td>
                                <td><?= htmlspecialchars($team['name']) ?></td>
                                <td><?= htmlspecialchars($team['country']) ?></td>
                                <td class="admin-actions-col">
                                    <a href="admin/team_edit?id=<?= (int)$team['id'] ?>"
                                       class="admin-icon-btn edit"
                                       title="Edit team">✎</a>

                                    <a href="admin/team_delete?id=<?= (int)$team['id'] ?>"
                                    class="admin-icon-btn delete js-delete-team"
                                    data-team-label="<?= htmlspecialchars($team['name']) ?>"
                                    title="Delete team">
                                        🗑
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php else: ?>

        <section class="admin-section">
            <div class="admin-section-header">
                <h2>Users</h2>

                <form class="admin-search" action="./" method="get">
                    <input type="hidden" name="section" value="users">
                    <input type="hidden" name="p" value="1">
                    <input type="hidden" name="perPage" value="<?= htmlspecialchars((string)$perPageAdmin) ?>">
                    <input type="text"
                           name="search"
                           placeholder="Search users..."
                           class="searchInput"
                           value="<?= htmlspecialchars($searchAdmin) ?>">
                </form>
            </div>

            <?php if (empty($users)): ?>
                <p class="info-empty">No users found.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Points</th>
                            <th>Role</th>
                            <th class="admin-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= (int)$user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= (int)$user['points'] ?></td>
                                <td><?= !empty($user['admin']) ? 'Admin' : 'User' ?></td>
                                <td class="admin-actions-col">
                                    <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0) && (int)$user['admin'] === 1): ?>
                                        <span class="admin-self-label">Admin</span>
                                    <?php elseif ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0) && (int)$user['admin'] === 0): ?>
                                        <a href="admin/user_edit?id=<?= (int)$user['id'] ?>"
                                        class="admin-icon-btn edit"
                                        title="Edit user">✎</a>

                                        <a href="admin/user_delete?id=<?= (int)$user['id'] ?>"
                                        class="admin-icon-btn delete js-delete-user"
                                        data-user-label="<?= htmlspecialchars($user['username']) ?>"
                                        title="Delete user">🗑</a>
                                    <?php else: ?>
                                        <span class="admin-self-label">You</span>
                                        <span><?php (int)$user['admin'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    <?php endif; ?>

    <?php if ($totalPagesAdminMb > 1): ?>
        <nav class="pager admin-pager">
            <a href="<?= build_admin_url($section, 1, $perPageAdmin, $searchAdmin) ?>"
               class="btn<?= $currentPageAdmin === 1 ? ' is-disabled' : '' ?>">« First</a>

            <a href="<?= build_admin_url($section, max(1, $currentPageAdmin - 1), $perPageAdmin, $searchAdmin) ?>"
               class="btn<?= $currentPageAdmin === 1 ? ' is-disabled' : '' ?>">‹ Prev</a>

            <?php for ($p = $startPageAdmin; $p <= $endPageAdmin; $p++): ?>
                <?php if ($p === $currentPageAdmin): ?>
                    <span class="page current"><?= htmlspecialchars((string)$p) ?></span>
                <?php else: ?>
                    <a href="<?= build_admin_url($section, $p, $perPageAdmin, $searchAdmin) ?>" class="page">
                        <?= htmlspecialchars((string)$p) ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <a href="<?= build_admin_url($section, min($totalPagesAdminMb, $currentPageAdmin + 1), $perPageAdmin, $searchAdmin) ?>"
               class="btn<?= $currentPageAdmin === $totalPagesAdminMb ? ' is-disabled' : '' ?>">Next ›</a>

            <a href="<?= build_admin_url($section, $totalPagesAdminMb, $perPageAdmin, $searchAdmin) ?>"
               class="btn<?= $currentPageAdmin === $totalPagesAdminMb ? ' is-disabled' : '' ?>">Last »</a>
        </nav>
    <?php endif; ?>

</main>
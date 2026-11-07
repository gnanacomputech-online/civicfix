<?php $pageTitle = 'My Complaints'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <div class="card-head">
        <h1>My Complaints</h1>
        <a href="/index.php?route=citizen/complaints/new" class="btn btn-accent">File a new complaint</a>
    </div>
    <p class="page-sub">Everything you've reported, and where it stands.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($stats['breached'] > 0): ?>
        <div class="escalation-banner">
            <span class="icon">&#9888;&#65039;</span>
            <span><strong><?= (int)$stats['breached'] ?></strong> of your complaints
            <?= $stats['breached'] === 1 ? 'has' : 'have' ?> gone past its response deadline and been escalated to the Municipal Admin for follow-up.</span>
        </div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['total'] ?></span><span class="stat-label">Total Filed</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['open'] ?></span><span class="stat-label">Open</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['resolved'] ?></span><span class="stat-label">Resolved / Closed</span></div>
        <div class="stat-tile stat-alert"><span class="stat-value"><?= (int)$stats['breached'] ?></span><span class="stat-label">Deadline Breached</span></div>
    </div>

    <div class="card">
        <?php if (empty($complaints)): ?>
            <p class="empty-state">You haven't filed any complaints yet. <a href="/index.php?route=citizen/complaints/new">File your first one</a>.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>ID</th><th>Category</th><th>Department</th><th>Ward</th><th>Status</th><th>Response Deadline</th><th>Filed</th><th></th></tr>
            <?php foreach ($complaints as $c): ?>
                <tr class="<?= Complaint::isBreached($c) ? 'row-breached' : '' ?>">
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><?= e($c['department_name']) ?></td>
                    <td><span class="badge-ward"><?= e($c['ward_name']) ?></span></td>
                    <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
                    <td><?= sla_badge($c) ?></td>
                    <td><?= format_datetime($c['created_at']) ?></td>
                    <td><a class="btn btn-ghost btn-sm" href="/index.php?route=citizen/complaints/view&id=<?= (int)$c['id'] ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

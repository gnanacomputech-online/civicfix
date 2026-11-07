<?php $pageTitle = 'Officer Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Department Queue — <?= e(Auth::name()) ?></h1>
    <p class="page-sub">Complaints routed to your department.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($stats['breached'] > 0): ?>
        <div class="escalation-banner">
            <span class="icon">&#9888;&#65039;</span>
            <span><strong><?= (int)$stats['breached'] ?></strong> complaint<?= $stats['breached'] === 1 ? '' : 's' ?> in your department's queue
            <?= $stats['breached'] === 1 ? 'has' : 'have' ?> breached the response deadline and escalated to the Municipal Admin.
            <a href="/index.php?route=officer/queue">Review the queue &#8594;</a></span>
        </div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['unclaimed'] ?></span><span class="stat-label">Unclaimed (New)</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['in_progress'] ?></span><span class="stat-label">Assigned / In Progress</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['resolved'] ?></span><span class="stat-label">Resolved</span></div>
        <div class="stat-tile stat-alert"><span class="stat-value"><?= (int)$stats['breached'] ?></span><span class="stat-label">Deadline Breached</span></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>My open complaints (<?= count($mine) ?>)</h2>
            <a href="/index.php?route=officer/queue" class="btn btn-ghost btn-sm">View full department queue</a>
        </div>
        <?php if (empty($mine)): ?>
            <p class="empty-state">You haven't claimed any complaints yet. Visit the queue to claim one.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>ID</th><th>Category</th><th>Ward</th><th>Status</th><th>Response Deadline</th><th>Filed</th><th></th></tr>
            <?php foreach ($mine as $c): ?>
                <tr class="<?= Complaint::isBreached($c) ? 'row-breached' : '' ?>">
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><span class="badge-ward"><?= e($c['ward_name']) ?></span></td>
                    <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
                    <td><?= sla_badge($c) ?></td>
                    <td><?= format_datetime($c['created_at']) ?></td>
                    <td><a class="btn btn-ghost btn-sm" href="/index.php?route=officer/complaints/view&id=<?= (int)$c['id'] ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

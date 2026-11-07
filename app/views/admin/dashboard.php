<?php $pageTitle = 'Admin Dashboard'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Municipal Admin Dashboard</h1>
    <p class="page-sub">System-wide complaint activity across every department and ward.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($stats['breached'] > 0): ?>
        <div class="escalation-banner">
            <span class="icon">&#9888;&#65039;</span>
            <span><strong><?= (int)$stats['breached'] ?></strong> complaint<?= $stats['breached'] === 1 ? '' : 's' ?>
            <?= $stats['breached'] === 1 ? 'has' : 'have' ?> breached its response deadline and escalated.
            <a href="/index.php?route=admin/escalations">Review the escalation queue &#8594;</a></span>
        </div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['total'] ?></span><span class="stat-label">Total Complaints</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['open'] ?></span><span class="stat-label">Open</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['resolved'] ?></span><span class="stat-label">Resolved</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['closed'] ?></span><span class="stat-label">Closed</span></div>
        <div class="stat-tile stat-alert"><span class="stat-value"><?= (int)$stats['breached'] ?></span><span class="stat-label">Deadline Breached</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['departments'] ?></span><span class="stat-label">Departments</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['officers'] ?></span><span class="stat-label">Officers</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$stats['citizens'] ?></span><span class="stat-label">Citizens</span></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Recent escalations</h2>
            <a href="/index.php?route=admin/escalations" class="btn btn-ghost btn-sm">View all escalations</a>
        </div>
        <?php if (empty($recentEscalations)): ?>
            <p class="empty-state">No complaints are currently past their response deadline. Nothing escalated.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>ID</th><th>Category</th><th>Department</th><th>Ward</th><th>Status</th><th>Filed</th><th></th></tr>
            <?php foreach ($recentEscalations as $c): ?>
                <tr class="row-breached">
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><?= e($c['department_name']) ?></td>
                    <td><span class="badge-ward"><?= e($c['ward_name']) ?></span></td>
                    <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
                    <td><?= format_datetime($c['created_at']) ?></td>
                    <td><a class="btn btn-ghost btn-sm" href="/index.php?route=admin/complaints/view&id=<?= (int)$c['id'] ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

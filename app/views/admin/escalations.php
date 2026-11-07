<?php $pageTitle = 'Escalations'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Escalation Queue</h1>
    <p class="page-sub">
        Every open complaint (New / Assigned / In Progress) that has already passed its
        category's response deadline — computed live against the clock on every page load, not a
        stored flag a background job has to keep fresh.
    </p>

    <div class="stat-grid">
        <div class="stat-tile stat-alert"><span class="stat-value"><?= count($escalations) ?></span><span class="stat-label">Currently Escalated</span></div>
    </div>

    <div class="card">
        <?php if (empty($escalations)): ?>
            <p class="empty-state">Nothing is currently breached. Every open complaint is still within its response deadline.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>ID</th><th>Category</th><th>Department</th><th>Ward</th><th>Citizen</th><th>Status</th><th>Response Deadline</th><th>Filed</th><th>Overdue by</th><th></th></tr>
            <?php foreach ($escalations as $c):
                $deadline = Complaint::slaDeadline($c);
                $overdueBy = duration_between(date('Y-m-d H:i:s', $deadline), date('Y-m-d H:i:s'));
            ?>
                <tr class="row-breached">
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><?= e($c['department_name']) ?></td>
                    <td><span class="badge-ward"><?= e($c['ward_name']) ?></span></td>
                    <td><?= e($c['citizen_name']) ?></td>
                    <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
                    <td><?= (int)$c['sla_hours'] ?> hrs</td>
                    <td><?= format_datetime($c['created_at']) ?></td>
                    <td><span class="badge badge-breach"><?= e($overdueBy) ?></span></td>
                    <td><a class="btn btn-ghost btn-sm" href="/index.php?route=admin/complaints/view&id=<?= (int)$c['id'] ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

<?php $pageTitle = 'Officer Report'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Officer Performance Report</h1>
    <p class="page-sub">Claimed/resolved counts, average resolution time, and late-resolution rate per officer.</p>

    <div class="card">
        <?php if (empty($officers)): ?>
            <p class="empty-state">No officer accounts yet.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>Officer</th><th>Department</th><th>Total Claimed</th><th>Resolved</th><th>Avg Resolution Time</th><th>Late Resolutions</th></tr>
            <?php foreach ($officers as $o): ?>
                <tr>
                    <td><?= e($o['name']) ?></td>
                    <td><?= e($o['department_name'] ?? '-') ?></td>
                    <td><?= (int)$o['total_claimed'] ?></td>
                    <td><?= (int)$o['resolved_count'] ?></td>
                    <td><?= $o['avg_resolution_hours'] !== null ? e((string)$o['avg_resolution_hours']) . ' hrs' : '-' ?></td>
                    <td><?= $o['late_rate'] !== null ? e((string)$o['late_rate']) . '%' : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

<?php $pageTitle = 'Public Dashboard'; require __DIR__ . '/../partials/header.php'; ?>
<header class="public-hero">
    <h1>&#128737; CivicFix — Public Transparency Dashboard</h1>
    <p>
        Live resolution-rate and average resolution-time statistics for every civic
        complaint filed on CivicFix, broken down by ward and by department. No login
        required — this page shows aggregate counts only.
    </p>
    <a class="btn btn-accent" href="/index.php?route=login">Officer / Admin login</a>
</header>

<main class="public-page">
    <p class="pii-note">
        &#128274; This page shows counts and averages only — no citizen name, contact
        detail, address, complaint description, or photo is ever queried or displayed
        here.
    </p>

    <div class="stat-grid">
        <div class="stat-tile"><span class="stat-value"><?= (int)$totalAll ?></span><span class="stat-label">Total Complaints Filed</span></div>
        <div class="stat-tile"><span class="stat-value"><?= (int)$totalResolved ?></span><span class="stat-label">Resolved or Closed</span></div>
        <div class="stat-tile">
            <span class="stat-value"><?= $totalAll > 0 ? round(($totalResolved / $totalAll) * 100, 1) : 0 ?>%</span>
            <span class="stat-label">Overall Resolution Rate</span>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>By Ward</h2></div>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>Ward</th><th>Total Filed</th><th>Resolved</th><th>Resolution Rate</th><th>Avg. Resolution Time</th></tr>
            <?php foreach ($byWard as $row): ?>
                <tr>
                    <td><span class="badge-ward"><?= e($row['group_name']) ?></span></td>
                    <td><?= (int)$row['total'] ?></td>
                    <td><?= (int)$row['resolved_count'] ?></td>
                    <td>
                        <?php if ($row['resolution_rate'] === null): ?>
                            <span class="muted">No data</span>
                        <?php else: ?>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <div class="rate-bar"><div class="rate-bar-fill" style="width:<?= (float)$row['resolution_rate'] ?>%;"></div></div>
                                <span><?= e((string)$row['resolution_rate']) ?>%</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['avg_resolution_hours'] !== null ? e((string)$row['avg_resolution_hours']) . ' hrs' : '<span class="muted">No data</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>By Department</h2></div>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>Department</th><th>Total Filed</th><th>Resolved</th><th>Resolution Rate</th><th>Avg. Resolution Time</th></tr>
            <?php foreach ($byDepartment as $row): ?>
                <tr>
                    <td><?= e($row['group_name']) ?></td>
                    <td><?= (int)$row['total'] ?></td>
                    <td><?= (int)$row['resolved_count'] ?></td>
                    <td>
                        <?php if ($row['resolution_rate'] === null): ?>
                            <span class="muted">No data</span>
                        <?php else: ?>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <div class="rate-bar"><div class="rate-bar-fill" style="width:<?= (float)$row['resolution_rate'] ?>%;"></div></div>
                                <span><?= e((string)$row['resolution_rate']) ?>%</span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['avg_resolution_hours'] !== null ? e((string)$row['avg_resolution_hours']) . ' hrs' : '<span class="muted">No data</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

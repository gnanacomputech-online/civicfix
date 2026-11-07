<?php $pageTitle = 'All Complaints'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>All Complaints</h1>
    <p class="page-sub">Every complaint filed system-wide, across every department and ward.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="GET" action="/index.php" class="filter-bar">
        <input type="hidden" name="route" value="admin/complaints">
        <label>Department
            <select name="department_id">
                <option value="">All</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= (int)$dept['id'] ?>" <?= (int)($_GET['department_id'] ?? 0) === (int)$dept['id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Ward
            <select name="ward_id">
                <option value="">All</option>
                <?php foreach ($wardsList as $ward): ?>
                    <option value="<?= (int)$ward['id'] ?>" <?= (int)($_GET['ward_id'] ?? 0) === (int)$ward['id'] ? 'selected' : '' ?>><?= e($ward['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Status
            <select name="status">
                <option value="">All</option>
                <?php foreach (Complaint::STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="/index.php?route=admin/complaints" class="btn btn-ghost btn-sm">Reset</a>
    </form>

    <div class="card">
        <div class="card-head"><h2>Results (<?= count($complaints) ?>)</h2></div>
        <?php if (empty($complaints)): ?>
            <p class="empty-state">No complaints match these filters.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>ID</th><th>Category</th><th>Department</th><th>Ward</th><th>Citizen</th><th>Status</th><th>Response Deadline</th><th>Filed</th><th></th></tr>
            <?php foreach ($complaints as $c): $breached = Complaint::isBreached($c); ?>
                <tr class="<?= $breached ? 'row-breached' : '' ?>">
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><?= e($c['department_name']) ?></td>
                    <td><span class="badge-ward"><?= e($c['ward_name']) ?></span></td>
                    <td><?= e($c['citizen_name']) ?></td>
                    <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
                    <td><?= sla_badge($c) ?></td>
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

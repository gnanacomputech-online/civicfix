<?php $pageTitle = 'Complaint Queue'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Department Complaint Queue</h1>
    <p class="page-sub">Unclaimed complaints are shared across the whole department — claim one to start work.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="GET" action="/index.php" class="filter-bar">
        <input type="hidden" name="route" value="officer/queue">
        <label>Status
            <select name="status">
                <option value="">All</option>
                <?php foreach (Complaint::STATUSES as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="/index.php?route=officer/queue" class="btn btn-ghost btn-sm">Reset</a>
    </form>

    <div class="card">
        <div class="card-head"><h2>Results (<?= count($complaints) ?>)</h2></div>
        <?php if (empty($complaints)): ?>
            <p class="empty-state">No complaints match this filter.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>ID</th><th>Category</th><th>Ward</th><th>Assigned to</th><th>Status</th><th>Response Deadline</th><th>Filed</th><th></th></tr>
            <?php foreach ($complaints as $c): ?>
                <tr class="<?= Complaint::isBreached($c) ? 'row-breached' : '' ?>">
                    <td>#<?= (int)$c['id'] ?></td>
                    <td><?= e($c['category_name']) ?></td>
                    <td><span class="badge-ward"><?= e($c['ward_name']) ?></span></td>
                    <td><?= e($c['officer_name'] ?? 'Unclaimed') ?></td>
                    <td><span class="badge badge-<?= e($c['status']) ?>"><?= e(status_label($c['status'])) ?></span></td>
                    <td><?= sla_badge($c) ?></td>
                    <td><?= format_datetime($c['created_at']) ?></td>
                    <td class="row-actions">
                        <?php if ($c['status'] === 'new'): ?>
                            <form method="POST" action="/index.php?route=officer/complaints/claim" class="inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="btn btn-accent btn-sm">Claim</button>
                            </form>
                        <?php else: ?>
                            <a class="btn btn-ghost btn-sm" href="/index.php?route=officer/complaints/view&id=<?= (int)$c['id'] ?>">Open</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

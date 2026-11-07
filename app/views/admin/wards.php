<?php $pageTitle = 'Wards'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Wards</h1>
    <p class="page-sub">The fixed area list citizens choose from when filing a complaint (no live maps/geocoding in this environment).</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-head"><h2>Add a ward</h2></div>
        <form method="POST" action="/index.php?route=admin/wards/store" class="form-grid">
            <?= Csrf::field() ?>
            <label>Name
                <input type="text" name="name" value="<?= old($old ?? [], 'name') ?>" maxlength="100" placeholder="e.g. Ward 21 - Lakeview" required>
                <?php if (!empty($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </label>
            <div class="form-actions">
                <button type="submit" class="btn btn-accent">Add ward</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><h2>All wards (<?= count($wards) ?>)</h2></div>
        <?php if (empty($wards)): ?>
            <p class="empty-state">No wards added yet.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>Ward</th><th>Complaints</th><th>Actions</th></tr>
            <?php foreach ($wards as $ward): ?>
                <tr>
                    <td><span class="badge-ward"><?= e($ward['name']) ?></span></td>
                    <td><?= (int)$ward['complaint_count'] ?></td>
                    <td class="row-actions">
                        <details>
                            <summary class="btn btn-ghost btn-sm">Edit</summary>
                            <form method="POST" action="/index.php?route=admin/wards/update" class="form-grid" style="margin-top:0.6rem;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$ward['id'] ?>">
                                <label>Name <input type="text" name="name" value="<?= e($ward['name']) ?>" maxlength="100" required></label>
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </details>
                        <form method="POST" action="/index.php?route=admin/wards/delete" class="inline-form"
                              onsubmit="return confirm('Remove this ward? Only possible if no complaints use it.');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$ward['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

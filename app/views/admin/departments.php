<?php $pageTitle = 'Departments'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Departments</h1>
    <p class="page-sub">Owning departments that complaint categories auto-route to.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-head"><h2>Add a department</h2></div>
        <form method="POST" action="/index.php?route=admin/departments/store" class="form-grid">
            <?= Csrf::field() ?>
            <label>Name
                <input type="text" name="name" value="<?= old($old ?? [], 'name') ?>" maxlength="120" required>
                <?php if (!empty($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </label>
            <label>Description (optional)
                <textarea name="description"><?= old($old ?? [], 'description') ?></textarea>
            </label>
            <div class="form-actions">
                <button type="submit" class="btn btn-accent">Add department</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><h2>All departments (<?= count($departments) ?>)</h2></div>
        <?php if (empty($departments)): ?>
            <p class="empty-state">No departments added yet.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>Name</th><th>Description</th><th>Categories</th><th>Officers</th><th>Complaints</th><th>Actions</th></tr>
            <?php foreach ($departments as $dept): ?>
                <tr>
                    <td><?= e($dept['name']) ?></td>
                    <td><?= e($dept['description'] ?? '-') ?></td>
                    <td><?= (int)$dept['category_count'] ?></td>
                    <td><?= (int)$dept['officer_count'] ?></td>
                    <td><?= (int)$dept['complaint_count'] ?></td>
                    <td class="row-actions">
                        <details>
                            <summary class="btn btn-ghost btn-sm">Edit</summary>
                            <form method="POST" action="/index.php?route=admin/departments/update" class="form-grid" style="margin-top:0.6rem;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$dept['id'] ?>">
                                <label>Name <input type="text" name="name" value="<?= e($dept['name']) ?>" maxlength="120" required></label>
                                <label>Description <textarea name="description"><?= e($dept['description'] ?? '') ?></textarea></label>
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </details>
                        <form method="POST" action="/index.php?route=admin/departments/delete" class="inline-form"
                              onsubmit="return confirm('Remove this department? Only possible if it has no categories or officers.');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$dept['id'] ?>">
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

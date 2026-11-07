<?php $pageTitle = 'Categories'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Complaint Categories</h1>
    <p class="page-sub">Each category maps to one department (drives auto-routing) and carries a response deadline in hours.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-head"><h2>Add a category</h2></div>
        <form method="POST" action="/index.php?route=admin/categories/store" class="form-grid">
            <?= Csrf::field() ?>
            <label>Name
                <input type="text" name="name" value="<?= old($old ?? [], 'name') ?>" maxlength="150" required>
                <?php if (!empty($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </label>
            <label>Owning department
                <select name="department_id" required>
                    <option value="">-- choose --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int)$dept['id'] ?>"><?= e($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['department_id'])): ?><span class="field-error"><?= e($errors['department_id']) ?></span><?php endif; ?>
            </label>
            <label>Response deadline (hours)
                <input type="number" name="sla_hours" min="1" max="2160" value="<?= old($old ?? [], 'sla_hours', '48') ?>" required>
                <?php if (!empty($errors['sla_hours'])): ?><span class="field-error"><?= e($errors['sla_hours']) ?></span><?php endif; ?>
            </label>
            <div class="form-actions">
                <button type="submit" class="btn btn-accent">Add category</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><h2>All categories (<?= count($categories) ?>)</h2></div>
        <?php if (empty($categories)): ?>
            <p class="empty-state">No categories added yet.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>Name</th><th>Department</th><th>Response deadline</th><th>Complaints</th><th>Actions</th></tr>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= e($cat['name']) ?></td>
                    <td><?= e($cat['department_name']) ?></td>
                    <td><?= (int)$cat['sla_hours'] ?> hrs</td>
                    <td><?= (int)$cat['complaint_count'] ?></td>
                    <td class="row-actions">
                        <details>
                            <summary class="btn btn-ghost btn-sm">Edit</summary>
                            <form method="POST" action="/index.php?route=admin/categories/update" class="form-grid" style="margin-top:0.6rem;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                <label>Name <input type="text" name="name" value="<?= e($cat['name']) ?>" maxlength="150" required></label>
                                <label>Department
                                    <select name="department_id" required>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= (int)$dept['id'] ?>" <?= (int)$dept['id'] === (int)$cat['department_id'] ? 'selected' : '' ?>><?= e($dept['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Response deadline (hours) <input type="number" name="sla_hours" min="1" max="2160" value="<?= (int)$cat['sla_hours'] ?>" required></label>
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            </form>
                        </details>
                        <form method="POST" action="/index.php?route=admin/categories/delete" class="inline-form"
                              onsubmit="return confirm('Remove this category? Only possible if no complaints use it.');">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
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

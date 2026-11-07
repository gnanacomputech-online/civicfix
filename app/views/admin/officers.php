<?php $pageTitle = 'Officer Accounts'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>Department Officer Accounts</h1>
    <p class="page-sub">Each officer belongs to exactly one department and only sees that department's complaint queue.</p>

    <?php if ($success = flash_get('success')): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error = flash_get('error')): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="card">
        <div class="card-head"><h2>Create an officer account</h2></div>
        <form method="POST" action="/index.php?route=admin/officers/store" class="form-grid">
            <?= Csrf::field() ?>
            <label>Full name
                <input type="text" name="name" value="<?= old($old ?? [], 'name') ?>" maxlength="100" required>
                <?php if (!empty($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </label>
            <label>Email
                <input type="email" name="email" value="<?= old($old ?? [], 'email') ?>" required>
                <?php if (!empty($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </label>
            <label>Phone (optional)
                <input type="tel" name="phone" value="<?= old($old ?? [], 'phone') ?>">
            </label>
            <label>Department
                <select name="department_id" required>
                    <option value="">-- choose --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= (int)$dept['id'] ?>"><?= e($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['department_id'])): ?><span class="field-error"><?= e($errors['department_id']) ?></span><?php endif; ?>
            </label>
            <label>Temporary password
                <input type="password" name="password" minlength="6" required>
                <?php if (!empty($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
            </label>
            <div class="form-actions">
                <button type="submit" class="btn btn-accent">Create officer</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-head"><h2>All officers (<?= count($officers) ?>)</h2></div>
        <?php if (empty($officers)): ?>
            <p class="empty-state">No officer accounts yet.</p>
        <?php else: ?>
        <div class="table-scroll">
        <table class="data-table">
            <tr><th>Name</th><th>Email</th><th>Department</th><th>Status</th><th>Actions</th></tr>
            <?php foreach ($officers as $officer): ?>
                <tr>
                    <td><?= e($officer['name']) ?></td>
                    <td><?= e($officer['email']) ?></td>
                    <td><?= e($officer['department_name'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= e($officer['status']) ?>"><?= e(ucfirst($officer['status'])) ?></span></td>
                    <td class="row-actions">
                        <form method="POST" action="/index.php?route=admin/officers/status" class="inline-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= (int)$officer['id'] ?>">
                            <?php if ($officer['status'] === 'active'): ?>
                                <input type="hidden" name="status" value="inactive">
                                <button type="submit" class="btn btn-ghost btn-sm">Deactivate</button>
                            <?php else: ?>
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                            <?php endif; ?>
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

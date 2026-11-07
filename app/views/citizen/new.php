<?php $pageTitle = 'File a Complaint'; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <h1>File a New Complaint</h1>
    <p class="page-sub">Your complaint is auto-routed to the department that owns its category the instant you submit it.</p>

    <?php if (!empty($duplicates)): ?>
        <div class="card" style="border-color:var(--orange);">
            <div class="card-head"><h2>Similar complaints already reported nearby</h2></div>
            <p class="page-sub">These are open complaints in the same ward and category, filed in the last two weeks. If one of these is the same issue, upvote it instead of filing a duplicate.</p>
            <div class="table-scroll">
            <table class="data-table">
                <tr><th>ID</th><th>Description</th><th>Status</th><th>Filed</th><th>Upvotes</th><th></th></tr>
                <?php foreach ($duplicates as $d): ?>
                    <tr>
                        <td>#<?= (int)$d['id'] ?></td>
                        <td><?= e(mb_strimwidth($d['description'], 0, 80, '...')) ?></td>
                        <td><span class="badge badge-<?= e($d['status']) ?>"><?= e(status_label($d['status'])) ?></span></td>
                        <td><?= format_date($d['created_at']) ?></td>
                        <td><?= (int)($d['upvote_count'] ?? 0) ?></td>
                        <td>
                            <form method="POST" action="/index.php?route=citizen/complaints/upvote" class="inline-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="complaint_id" value="<?= (int)$d['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Upvote this instead</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="/index.php?route=citizen/complaints/store" enctype="multipart/form-data" class="form-grid">
            <?php if (!empty($duplicates)): ?><input type="hidden" name="confirm_new" value="1"><?php endif; ?>
            <?= Csrf::field() ?>

            <label>Category
                <select name="category_id" required>
                    <option value="">-- choose the closest match --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)($old['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?> (<?= e($cat['department_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['category_id'])): ?><span class="field-error"><?= e($errors['category_id']) ?></span><?php endif; ?>
            </label>

            <label>Ward
                <select name="ward_id" required>
                    <option value="">-- choose your ward --</option>
                    <?php foreach ($wards as $ward): ?>
                        <option value="<?= (int)$ward['id'] ?>" <?= (int)($old['ward_id'] ?? 0) === (int)$ward['id'] ? 'selected' : '' ?>><?= e($ward['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['ward_id'])): ?><span class="field-error"><?= e($errors['ward_id']) ?></span><?php endif; ?>
            </label>

            <label>Address / landmark
                <input type="text" name="address_line" value="<?= old($old ?? [], 'address_line') ?>" maxlength="255" placeholder="e.g. Near City Park main gate, 3rd Cross Street" required>
                <?php if (!empty($errors['address_line'])): ?><span class="field-error"><?= e($errors['address_line']) ?></span><?php endif; ?>
            </label>

            <label>Description
                <textarea name="description" maxlength="2000" placeholder="Describe the issue in detail..." required><?= old($old ?? [], 'description') ?></textarea>
                <?php if (!empty($errors['description'])): ?><span class="field-error"><?= e($errors['description']) ?></span><?php endif; ?>
            </label>

            <label>Photo of the issue
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-preview="photoPreview" required>
                <?php if (!empty($errors['photo'])): ?><span class="field-error"><?= e($errors['photo']) ?></span><?php endif; ?>
            </label>
            <img id="photoPreview" class="hidden" style="max-width:220px;border-radius:4px;border:1px solid var(--border);" alt="Preview">

            <div class="form-actions">
                <button type="submit" class="btn btn-accent"><?= !empty($duplicates) ? 'None of these match &mdash; file as new' : 'Submit complaint' ?></button>
            </div>
        </form>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

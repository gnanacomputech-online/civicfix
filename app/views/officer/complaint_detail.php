<?php
$pageTitle = 'Complaint #' . $complaint['id'];
require __DIR__ . '/../partials/header.php';
require __DIR__ . '/../partials/navbar.php';

$isMine = (int)($complaint['assigned_officer_id'] ?? 0) === Auth::id();
$nextStatusLabel = [
    'assigned'    => 'In Progress',
    'in_progress' => 'Resolved',
][$complaint['status']] ?? null;
?>
<main class="page">
    <p><a href="/index.php?route=officer/queue">&#8592; Back to queue</a></p>
    <?php require __DIR__ . '/../partials/complaint_detail_body.php'; ?>

    <div class="card">
        <div class="card-head"><h2>Actions</h2></div>

        <?php if ($complaint['status'] === 'new'): ?>
            <p class="muted">This complaint hasn't been claimed yet.</p>
            <form method="POST" action="/index.php?route=officer/complaints/claim">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$complaint['id'] ?>">
                <button type="submit" class="btn btn-accent">Claim this complaint</button>
            </form>
        <?php elseif (!$isMine): ?>
            <p class="muted">This complaint is assigned to <?= e($complaint['officer_name']) ?>. You can view it but only the assigned officer can advance its status.</p>
        <?php elseif ($complaint['status'] === 'closed'): ?>
            <p class="muted">This complaint is Closed. No further action is possible.</p>
        <?php elseif ($complaint['status'] === 'resolved'): ?>
            <p class="muted">Marked Resolved. Waiting on the citizen to confirm and close it, or reopen it, from their side.</p>
        <?php else: ?>
            <form method="POST" action="/index.php?route=officer/complaints/status" enctype="multipart/form-data" class="form-grid form-wide">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$complaint['id'] ?>">

                <label>Note (optional)
                    <textarea name="note" placeholder="What was done / found..."></textarea>
                </label>

                <?php if ($complaint['status'] === 'in_progress'): ?>
                    <label>Resolution proof photo <span class="muted">(required to mark Resolved)</span>
                        <input type="file" name="resolution_photo" accept="image/jpeg,image/png,image/webp" data-preview="resolutionPreview" required>
                    </label>
                    <img id="resolutionPreview" class="hidden" style="max-width:220px;border-radius:4px;border:1px solid var(--border);" alt="Preview">
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-accent">Mark as <?= e($nextStatusLabel) ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

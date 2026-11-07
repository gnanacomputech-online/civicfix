<?php $pageTitle = 'Complaint #' . $complaint['id']; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <p><a href="/index.php?route=admin/complaints">&#8592; Back to all complaints</a></p>
    <?php require __DIR__ . '/../partials/complaint_detail_body.php'; ?>

    <?php if ($complaint['status'] === 'resolved'): ?>
        <div class="card">
            <div class="card-head"><h2>Actions</h2></div>
            <p class="muted">Waiting on the citizen to confirm or reopen. If they don't respond, you can close it directly.</p>
            <form method="POST" action="/index.php?route=admin/complaints/force-close"
                  onsubmit="return confirm('Close this complaint without citizen confirmation?');">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$complaint['id'] ?>">
                <button type="submit" class="btn btn-ghost">Force close</button>
            </form>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

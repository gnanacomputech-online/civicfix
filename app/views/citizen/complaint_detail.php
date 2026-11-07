<?php $pageTitle = 'Complaint #' . $complaint['id']; require __DIR__ . '/../partials/header.php'; require __DIR__ . '/../partials/navbar.php'; ?>
<main class="page">
    <p><a href="/index.php?route=citizen/dashboard">&#8592; Back to my complaints</a></p>
    <?php require __DIR__ . '/../partials/complaint_detail_body.php'; ?>

    <?php if ($complaint['status'] === 'resolved' && $complaint['citizen_rating'] === null): ?>
        <div class="card">
            <div class="card-head"><h2>How did we do?</h2></div>
            <form method="POST" action="/index.php?route=citizen/complaints/rate" class="form-grid">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int)$complaint['id'] ?>">
                <label>Rating
                    <select name="rating" required>
                        <option value="">-- choose --</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Good</option>
                        <option value="3">3 - Okay</option>
                        <option value="2">2 - Poor</option>
                        <option value="1">1 - Very poor</option>
                    </select>
                </label>
                <label>Comment (optional)
                    <textarea name="comment" maxlength="500"></textarea>
                </label>
                <div class="form-actions">
                    <button type="submit" class="btn btn-accent">Confirm &amp; close</button>
                </div>
            </form>

            <?php if ((int)$complaint['reopen_count'] < 1): ?>
                <form method="POST" action="/index.php?route=citizen/complaints/reopen" class="form-grid" style="margin-top:1rem;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int)$complaint['id'] ?>">
                    <label>Not actually fixed? Tell us why (optional)
                        <textarea name="reason" maxlength="500"></textarea>
                    </label>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-ghost">Reopen this complaint</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php elseif ($complaint['citizen_rating'] !== null): ?>
        <div class="card">
            <div class="card-head"><h2>Your feedback</h2></div>
            <p>You rated this <?= (int)$complaint['citizen_rating'] ?>/5<?= $complaint['citizen_rating_comment'] ? ' &mdash; ' . e($complaint['citizen_rating_comment']) : '' ?></p>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

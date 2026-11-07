<?php
/** Expects $complaint, $history, $comments, $commentRoute in scope. Shared read-only
 *  body used by the Admin, Officer, and Citizen complaint-detail screens; each wraps
 *  it with its own action forms (claim/advance status for officers, nothing extra for others). */
$breached = Complaint::isBreached($complaint);
?>
<?php if ($breached): ?>
    <div class="escalation-banner">
        <span class="icon">&#9888;&#65039;</span>
        <span>This complaint has breached its <?= (int)$complaint['sla_hours'] ?>-hour response deadline and is escalated.</span>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <h2>Complaint #<?= (int)$complaint['id'] ?> &mdash; <?= e($complaint['category_name']) ?></h2>
        <span class="badge badge-<?= e($complaint['status']) ?>"><?= e(status_label($complaint['status'])) ?></span>
    </div>

    <div class="meta-grid">
        <div class="meta-item"><span class="meta-label">Department</span><span class="meta-value"><?= e($complaint['department_name']) ?></span></div>
        <div class="meta-item"><span class="meta-label">Ward</span><span class="meta-value"><span class="badge-ward"><?= e($complaint['ward_name']) ?></span></span></div>
        <div class="meta-item"><span class="meta-label">Filed by</span><span class="meta-value"><?= e($complaint['citizen_name']) ?></span></div>
        <div class="meta-item"><span class="meta-label">Assigned officer</span><span class="meta-value"><?= e($complaint['officer_name'] ?? 'Unclaimed') ?></span></div>
        <div class="meta-item"><span class="meta-label">Filed on</span><span class="meta-value"><?= format_datetime($complaint['created_at']) ?></span></div>
        <div class="meta-item"><span class="meta-label">Response Deadline</span><span class="meta-value"><?= (int)$complaint['sla_hours'] ?> hrs</span></div>
        <div class="meta-item"><span class="meta-label">Deadline Status</span><span class="meta-value"><?= sla_badge($complaint) ?: '<span class="badge badge-resolved">Closed before deadline check</span>' ?></span></div>
        <div class="meta-item"><span class="meta-label">Similar reports</span><span class="meta-value"><?= (int)($complaint['upvote_count'] ?? 0) ?></span></div>
        <?php if ($complaint['resolved_at']): ?>
        <div class="meta-item"><span class="meta-label">Resolved on</span><span class="meta-value"><?= format_datetime($complaint['resolved_at']) ?></span></div>
        <?php endif; ?>
    </div>

    <div class="meta-item" style="margin-bottom:1rem;">
        <span class="meta-label">Address / landmark</span>
        <span class="meta-value" style="font-weight:400;"><?= e($complaint['address_line']) ?></span>
    </div>

    <div class="meta-item" style="margin-bottom:1rem;">
        <span class="meta-label">Description</span>
        <div class="text-block"><?= e($complaint['description']) ?></div>
    </div>

    <div class="complaint-photos">
        <?php if (!empty($complaint['photo_path'])): ?>
            <a class="photo-thumb" href="/uploads/complaints/<?= e($complaint['photo_path']) ?>" target="_blank" rel="noopener">
                <img src="/uploads/complaints/<?= e($complaint['photo_path']) ?>" alt="Photo of the reported issue">
                <span class="photo-thumb-label">Issue photo</span>
            </a>
        <?php endif; ?>
        <?php if (!empty($complaint['resolution_photo_path'])): ?>
            <a class="photo-thumb" href="/uploads/resolutions/<?= e($complaint['resolution_photo_path']) ?>" target="_blank" rel="noopener">
                <img src="/uploads/resolutions/<?= e($complaint['resolution_photo_path']) ?>" alt="Resolution proof photo">
                <span class="photo-thumb-label">Resolution proof</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2>Status history</h2></div>
    <ul class="timeline">
        <?php foreach ($history as $h): ?>
            <li class="timeline-<?= e($h['status']) ?>">
                <div class="timeline-status"><?= e(status_label($h['status'])) ?></div>
                <div class="timeline-meta"><?= format_datetime($h['changed_at']) ?> &middot; <?= e($h['changed_by_name'] ?? 'System') ?></div>
                <?php if (!empty($h['note'])): ?><div class="timeline-note"><?= e($h['note']) ?></div><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card">
    <div class="card-head"><h2>Comments</h2></div>
    <?php if (empty($comments)): ?>
        <p class="empty-state">No comments yet.</p>
    <?php else: ?>
        <ul class="timeline">
            <?php foreach ($comments as $cm): ?>
                <li>
                    <div class="timeline-meta"><strong><?= e($cm['author_name']) ?></strong> (<?= e(str_replace('_', ' ', $cm['author_role'])) ?>) &middot; <?= format_datetime($cm['created_at']) ?></div>
                    <div class="timeline-note"><?= e($cm['body']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="POST" action="/index.php?route=<?= e($commentRoute) ?>" class="form-grid" style="margin-top:1rem;">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int)$complaint['id'] ?>">
        <label>Add a comment
            <textarea name="body" maxlength="1000" required></textarea>
        </label>
        <div class="form-actions">
            <button type="submit" class="btn btn-accent">Post comment</button>
        </div>
    </form>
</div>

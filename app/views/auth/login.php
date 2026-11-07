<?php $pageTitle = 'Login'; require __DIR__ . '/../partials/header.php'; ?>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">&#128737;</span>
            <h1>CivicFix</h1>
            <p class="auth-sub">Municipal Complaint Reporting &amp; SLA Escalation</p>
        </div>

        <?php if (!empty($errors['login'])): ?>
            <div class="alert alert-error"><?= e($errors['login']) ?></div>
        <?php endif; ?>
        <?php if ($success = flash_get('success')): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="/index.php?route=login" class="auth-form" novalidate>
            <?= Csrf::field() ?>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= old($old ?? [], 'email') ?>" required autofocus>
            <?php if (!empty($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <?php if (!empty($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>

            <button type="submit" class="btn btn-accent btn-block">Log in</button>
        </form>

        <p class="auth-links">New citizen? <a href="/index.php?route=register">Register an account</a></p>
        <p class="auth-links"><a href="/index.php?route=stats">View the public transparency dashboard &#8594;</a></p>

        <details class="demo-creds">
            <summary>Demo credentials</summary>
            <table>
                <tr><th>Role</th><th>Email</th><th>Password</th></tr>
                <tr><td>Super Admin</td><td>admin@civicfix.test</td><td>Admin@123</td></tr>
                <tr><td>Dept. Officer (Sanitation)</td><td>officer1@civicfix.test</td><td>Officer@123</td></tr>
                <tr><td>Dept. Officer (Water Supply)</td><td>officer3@civicfix.test</td><td>Officer@123</td></tr>
                <tr><td>Citizen</td><td>citizen1@civicfix.test</td><td>Citizen@123</td></tr>
            </table>
        </details>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

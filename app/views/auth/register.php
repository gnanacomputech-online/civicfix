<?php $pageTitle = 'Register'; require __DIR__ . '/../partials/header.php'; ?>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">&#128737;</span>
            <h1>Register</h1>
            <p class="auth-sub">Create a Citizen account to file complaints</p>
        </div>

        <form method="POST" action="/index.php?route=register" class="auth-form" novalidate>
            <?= Csrf::field() ?>
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" value="<?= old($old ?? [], 'name') ?>" maxlength="100" required autofocus>
            <?php if (!empty($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= old($old ?? [], 'email') ?>" required>
            <?php if (!empty($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>

            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?= old($old ?? [], 'phone') ?>" required>
            <?php if (!empty($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="6" required>
            <?php if (!empty($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>

            <label for="password_confirm">Confirm password</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="6" required>
            <?php if (!empty($errors['password_confirm'])): ?><span class="field-error"><?= e($errors['password_confirm']) ?></span><?php endif; ?>

            <button type="submit" class="btn btn-accent btn-block">Create account</button>
        </form>

        <p class="auth-links">Already registered? <a href="/index.php?route=login">Log in</a></p>
    </div>
</main>
<?php require __DIR__ . '/../partials/footer.php'; ?>

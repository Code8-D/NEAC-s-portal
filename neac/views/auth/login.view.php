<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="auth-page">
    <section class="auth-card">
        <div class="auth-card__visual"></div>
        <form class="auth-form" method="post" action="<?= BASE_URL ?>/login.php">
            <?= Security::csrfField() ?>
            <img src="<?= LOGO_IMAGE ?>" alt="NEAC logo">
            <span class="eyebrow">Secure sign in</span>
            <h1>Access your NEAC workspace</h1>
            <?php if (!empty($error)): ?>
                <p class="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <label>
                Email address
                <input type="email" name="email" placeholder="name@neac.edu" required>
            </label>
            <label>
                Password
                <input type="password" name="password" placeholder="Enter password" required>
            </label>
            <label>
                Portal role
                <select name="role">
                    <option value="teacher">Teacher / Instructor</option>
                    <option value="student">Student</option>
                </select>
            </label>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-unlock-keyhole"></i> Sign in</button>
            <p class="form-note">New student? <a href="<?= BASE_URL ?>/signup.php">Create your account</a>.</p>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="auth-page signup-page">
    <section class="signup-card">
        <div class="signup-card__content">
            <span class="eyebrow"><i class="fa-solid fa-user-plus"></i> Student sign up</span>
            <h1>Create your NEAC student account</h1>
            <p>Create a student account for course access, assessments, results, and official NEAC learning updates.</p>
            <div class="signup-benefits">
                <span><i class="fa-solid fa-shield-halved"></i> Secure student workspace</span>
                <span><i class="fa-solid fa-certificate"></i> Certification programme access</span>
            </div>
        </div>

        <form class="signup-form" method="post" action="<?= BASE_URL ?>/signup.php">
            <?= Security::csrfField() ?>
            <div class="form-heading">
                <h2>Student details</h2>
                <p>Use your correct details for records and certification.</p>
            </div>
            <?php if (!empty($error)): ?>
                <p class="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <label>
                Full name
                <input type="text" name="full_name" placeholder="Enter full name" required>
            </label>
            <label>
                Email address
                <input type="email" name="email" placeholder="student@example.com" required>
            </label>
            <label>
                Phone number
                <input type="tel" name="phone" placeholder="+237 000 000 000" required>
            </label>
            <label>
                Program
                <select name="program" required>
                    <?php foreach ($programs as $program): ?>
                        <option value="<?= e($program['name']) ?>"><?= e($program['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Password
                <input type="password" name="password" placeholder="Create password" minlength="6" required>
            </label>
            <label>
                Confirm password
                <input type="password" name="confirm_password" placeholder="Repeat password" minlength="6" required>
            </label>
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-user-check"></i> Create account</button>
            <p class="form-note">Already registered? <a href="<?= BASE_URL ?>/login.php">Log in here</a>.</p>
        </form>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

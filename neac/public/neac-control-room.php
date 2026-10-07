<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';

$error = '';
$pageTitle = 'Restricted Access';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $code = trim($_POST['access_code'] ?? '');
    if (Auth::loginAdmin($code)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid access code. Please try again.';
}

require __DIR__ . '/../views/layouts/header.php';
?>

<main class="auth-page admin-gate">
    <section class="secret-card three-d-card">
        <div class="three-d-layer three-d-layer--back"></div>
        <div class="secret-card__inner">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-lock"></i> Restricted NEAC area</span>
                <h1>Administrative access</h1>
                <p>This entry point is reserved for authorised NEAC administrators only.</p>
            </div>
            <form class="auth-form secret-form" method="post">
                <?= Security::csrfField() ?>
                <?php if ($error): ?>
                    <p class="alert"><?= e($error) ?></p>
                <?php endif; ?>
                <label>
                    Access code
                    <input type="password" name="access_code" placeholder="Enter admin access code" required>
                </label>
                <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-shield-keyhole"></i> Open admin dashboard</button>
            </form>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../views/layouts/footer.php'; ?>

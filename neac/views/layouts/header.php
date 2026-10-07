<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Auth.php';
require_once __DIR__ . '/../../app/core/Security.php';
$__neacScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
Security::sendSecurityHeaders();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? APP_NAME) ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css?v=<?= ASSET_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/ui.css?v=<?= ASSET_VERSION ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23102b4e'/%3E%3Ctext x='50' y='62' font-size='52' text-anchor='middle' fill='%23d9a321' font-family='Inter,sans-serif' font-weight='800'%3EN%3C/text%3E%3C/svg%3E">
</head>
<body>
<?php if (empty($hideTopbar)): ?>
    <header class="topbar" id="topbar">
        <a class="brand" href="<?= BASE_URL ?>/index.php" id="brand-logo" data-admin-trigger>
            <img src="<?= LOGO_IMAGE ?>" alt="NEAC logo">
            <span><b>NEAC</b><small>National Examination<br>&amp; Award Commission</small></span>
        </a>
        <nav class="topnav" id="topnav" aria-label="Primary navigation">
            <a href="<?= BASE_URL ?>/index.php"<?= $__neacScript === 'index.php' || $__neacScript === '' ? ' class="active"' : '' ?>>Home</a>
            <a href="<?= BASE_URL ?>/about.php"<?= $__neacScript === 'about.php' ? ' class="active"' : '' ?>>About</a>
            <a href="<?= BASE_URL ?>/modules.php"<?= $__neacScript === 'modules.php' ? ' class="active"' : '' ?>>Modules</a>
            <a href="<?= BASE_URL ?>/ranking.php"<?= $__neacScript === 'ranking.php' ? ' class="active"' : '' ?>>Ranking</a>
            <a href="<?= BASE_URL ?>/contact.php"<?= $__neacScript === 'contact.php' ? ' class="active"' : '' ?>>Contact</a>
            <?php if (!empty($_SESSION['role'])): ?>
                <a href="<?= BASE_URL ?>/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/signup.php">Sign up</a>
            <?php endif; ?>
        </nav>
        <button type="button" class="nav-toggle" id="nav-toggle" aria-controls="topnav" aria-expanded="false" aria-label="Toggle navigation menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>
<?php else: ?>
    <a class="brand brand--floating" href="<?= BASE_URL ?>/index.php" id="brand-logo" data-admin-trigger aria-label="Home">
        <img src="<?= LOGO_IMAGE ?>" alt="NEAC logo">
        <span><b>NEAC</b><small>National Examination<br>&amp; Award Commission</small></span>
    </a>
<?php endif; ?>

<div class="admin-modal" id="admin-modal" role="dialog" aria-modal="true" aria-labelledby="admin-modal-title" hidden>
    <div class="admin-modal__backdrop" data-admin-close></div>
    <div class="admin-modal__panel three-d-card">
        <button type="button" class="admin-modal__close" data-admin-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <span class="eyebrow"><i class="fa-solid fa-shield-halved"></i> Restricted area</span>
        <h2 id="admin-modal-title">Administrative access</h2>
        <p>Enter the access code to open the super admin dashboard.</p>
        <form class="auth-form" method="post" action="<?= BASE_URL ?>/neac-control-room.php" id="admin-form">
            <?= Security::csrfField() ?>
            <label>
                Access code
                <input type="password" name="access_code" id="admin-code" placeholder="Enter admin access code" required autocomplete="off">
            </label>
            <div class="admin-modal__hint" id="admin-hint">Restricted to authorised NEAC administrators.</div>
            <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-shield-keyhole"></i> Open admin dashboard</button>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/hidden-admin.js?v=<?= ASSET_VERSION ?>"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= ASSET_VERSION ?>"></script>

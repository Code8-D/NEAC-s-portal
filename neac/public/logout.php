<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    Auth::logout();
    header('Location: index.php');
    exit;
}

$_csrf = Security::csrfToken();
?><!doctype html>
<html><head><meta charset="utf-8"><title>Logging out…</title></head>
<body>
<form method="post" action="logout.php" id="logout-form">
    <input type="hidden" name="_csrf_token" value="<?= e($_csrf) ?>">
    <noscript><button type="submit">Confirm logout</button></noscript>
</form>
<script>document.getElementById('logout-form').submit();</script>
</body>
</html>

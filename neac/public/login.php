<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Security.php';

$error = '';
$pageTitle = 'Sign in';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $role = $_POST['role'] ?? 'student';

    if ($role === 'student') {
        if (!Auth::loginStudent($_POST['email'] ?? '', $_POST['password'] ?? '')) {
            $error = Security::loginThrottle('student:' . strtolower(trim($_POST['email'] ?? '')))
                ? 'Too many failed attempts. Please try again in 15 minutes.'
                : 'Invalid student email or password.';
        }
    } elseif ($role === 'teacher') {
        if (!Auth::loginTeacher($_POST['email'] ?? '', $_POST['password'] ?? '')) {
            $error = Security::loginThrottle('teacher:' . strtolower(trim($_POST['email'] ?? '')))
                ? 'Too many failed attempts. Please try again in 15 minutes.'
                : 'Invalid lecturer email or password.';
        }
    } else {
        $error = 'Invalid portal role.';
    }

    if ($error === '') {
        header('Location: dashboard.php');
        exit;
    }
}

require __DIR__ . '/../views/auth/login.view.php';

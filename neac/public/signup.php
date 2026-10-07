<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

$error = '';
$pageTitle = 'Student Sign Up';
$programs = Database::programs();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            Auth::registerStudent($_POST);
            header('Location: dashboard.php');
            exit;
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            $error = str_contains($exception->getMessage(), 'UNIQUE')
                ? 'An account with this email already exists. Please log in.'
                : 'Registration could not be completed. Please try again.';
        }
    }
}

require __DIR__ . '/../views/auth/register.view.php';

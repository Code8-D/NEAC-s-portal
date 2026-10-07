<?php

define('APP_NAME', 'NEAC Integrated Web Platform');
define('ASSET_VERSION', '3');
define('ADMIN_ACCESS_CODE', (string) (getenv('NEAC_ADMIN_CODE') ?: 'NEAC2026'));
define('DB_PATH', __DIR__ . '/../database/neac_platform.sqlite');
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/neac/public/index.php'));
$baseUrl = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
define('BASE_URL', $baseUrl);
define('LOGO_IMAGE', BASE_URL . '/assets/images/WhatsApp%20Image%202026-05-27%20at%2012.27.14.jpeg');

date_default_timezone_set(getenv('NEAC_TZ') ?: 'Africa/Douala');

define('MOMO_NUMBER', '683705219');
define('MOMO_ACCOUNT_NAME', 'NEAC BB Certification');
define('EXAM_FEE', 10000);
define('EXAM_FEE_CURRENCY', 'XAF');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function hasCertAccess(): bool
{
    if (!Auth::check()) return false;
    if (Auth::isAdmin()) return true;
    if (Auth::role() !== 'student') return true;
    $sid = Auth::studentId();
    if (!$sid) return false;
    $db = Database::connection();
    $app = $db->prepare('SELECT status FROM certification_applications WHERE student_id = :sid LIMIT 1');
    $app->execute(['sid' => $sid]);
    $appStatus = $app->fetchColumn();
    if ($appStatus === false) return false;
    if ($appStatus !== 'approved') return false;
    $pay = $db->prepare('SELECT status FROM payment_verifications WHERE student_id = :sid ORDER BY id DESC LIMIT 1');
    $pay->execute(['sid' => $sid]);
    $payStatus = $pay->fetchColumn();
    if ($payStatus === false) return false;
    return $payStatus === 'verified';
}

function getCertStatus(): array
{
    $sid = Auth::studentId();
    if (!$sid) return ['applied' => false, 'paid' => false, 'access' => false];
    $db = Database::connection();
    $appStmt = $db->prepare('SELECT status FROM certification_applications WHERE student_id = :sid LIMIT 1');
    $appStmt->execute(['sid' => $sid]);
    $appStatus = $appStmt->fetchColumn();
    $payStmt = $db->prepare('SELECT status FROM payment_verifications WHERE student_id = :sid ORDER BY id DESC LIMIT 1');
    $payStmt->execute(['sid' => $sid]);
    $payStatus = $payStmt->fetchColumn();
    $recStmt = $db->prepare('SELECT COUNT(*) FROM recommendations WHERE student_id = :sid');
    $recStmt->execute(['sid' => $sid]);
    $recCount = (int) $recStmt->fetchColumn();
    return [
        'applied' => $appStatus !== false && $appStatus !== 'draft',
        'application_status' => $appStatus ?: 'none',
        'paid' => $payStatus === 'verified',
        'payment_status' => $payStatus ?: 'none',
        'recommendations_uploaded' => $recCount,
        'access' => $appStatus === 'approved' && $payStatus === 'verified',
    ];
}

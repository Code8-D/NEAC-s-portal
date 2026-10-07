<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit;
}
$stmt = Database::connection()->prepare('SELECT image_path FROM home_posts WHERE id = :id');
$stmt->execute(['id' => $id]);
$path = $stmt->fetchColumn();
$allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
if (!$path || !isset($allowed[$ext])) {
    http_response_code(404);
    exit;
}
$base = realpath(__DIR__ . '/../storage/uploads/home_posts');
$abs = realpath(__DIR__ . '/../' . $path);
if ($base === false || $abs === false || !str_starts_with($abs, $base . DIRECTORY_SEPARATOR) || !is_file($abs)) {
    http_response_code(404);
    exit;
}
$detected = Security::detectMime($abs);
if (!str_starts_with($detected, 'image/') || $detected !== $allowed[$ext]) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . $detected);
header('Content-Disposition: inline');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($abs);

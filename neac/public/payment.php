<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Setting.php';

if (!Auth::check() || Auth::role() !== 'student') {
    header('Location: signup.php');
    exit;
}

$studentId = (int) Auth::studentId();
$userId = (int) Auth::userId();
$db = Database::connection();
Database::migrate();

$status = getCertStatus();
if (!$status['applied']) {
    header('Location: application.php');
    exit;
}

$existingPayments = $db->prepare('SELECT * FROM payment_verifications WHERE student_id = :sid ORDER BY id DESC');
$existingPayments->execute(['sid' => $studentId]);
$payments = $existingPayments->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'upload_payment') {
        $senderName = trim($_POST['sender_name'] ?? '');
        $senderPhone = trim($_POST['sender_phone'] ?? '');
        $transactionId = trim($_POST['transaction_id'] ?? '');
        if (empty($senderName) || empty($senderPhone) || empty($transactionId)) {
            $error = 'Please fill in all payment details.';
        } elseif (empty($_FILES['screenshot']) || $_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Please upload a screenshot of the transaction.';
        } else {
            $dup = $db->prepare('SELECT 1 FROM payment_verifications WHERE transaction_id = :tid LIMIT 1');
            $dup->execute(['tid' => $transactionId]);
            if ($dup->fetchColumn()) {
                $error = 'This transaction ID was already submitted. If you need help, contact the admin.';
            } else {
            $file = $_FILES['screenshot'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
            [$ok, $msg] = Security::uploadIsAllowed($file, $allowed, ['image/', 'application/pdf'], 10 * 1024 * 1024);
            if (!$ok) {
                $error = $msg;
            } else {
                $uploadDir = __DIR__ . '/../storage/uploads/payments/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $storedName = 'payment_' . $studentId . '_' . time() . '_' . Security::randomCode(6) . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . $storedName)) {
                    $error = 'Could not save the uploaded screenshot. Please try again.';
                } else {
                $stmt = $db->prepare('INSERT INTO payment_verifications (student_id, amount, purpose, transaction_id, sender_name, sender_phone, screenshot_path, status) VALUES (:sid, :amt, :purp, :tid, :sn, :sp, :ss, "pending")');
                $stmt->execute(['sid' => $studentId, 'amt' => EXAM_FEE, 'purp' => 'certification_exam_fee', 'tid' => $transactionId, 'sn' => $senderName, 'sp' => $senderPhone, 'ss' => 'storage/uploads/payments/' . $storedName]);
                require_once __DIR__ . '/../app/models/Notification.php';
                Notification::create($userId, 'Payment Submitted for Verification', 'Your exam fee payment of ' . EXAM_FEE . ' ' . EXAM_FEE_CURRENCY . ' has been submitted. Admin will verify it shortly.', 'payment');
                $adminStmt = $db->query('SELECT id FROM users WHERE role = "admin" LIMIT 1');
                $adminId = (int) $adminStmt->fetchColumn();
                if ($adminId) {
                    Notification::create($adminId, 'New Payment Verification', 'A student has submitted a payment screenshot for certification exam fee. Verify in admin panel.', 'payment');
                }
                $success = 'Payment details submitted! Waiting for admin verification.';
                $payments = $db->prepare('SELECT * FROM payment_verifications WHERE student_id = :sid ORDER BY id DESC');
                $payments->execute(['sid' => $studentId]);
                $payments = $payments->fetchAll();
                }
            }
            }
        }
    }
}

$pageTitle = 'Certification Exam Payment';
$hideTopbar = true;
$certBatch = (string) (Setting::get('cert_batch_label') ?? '4th Batch');
require __DIR__ . '/../views/certification/payment.view.php';

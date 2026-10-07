<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';

if (!Auth::check() || !Auth::isAdmin()) {
    http_response_code(403);
    exit('Admin access required.');
}

$db = Database::connection();
Database::migrate();
$userId = (int) Auth::userId();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $notes = trim($_POST['admin_notes'] ?? '');
    if ($paymentId && in_array($newStatus, ['verified', 'rejected'])) {
        $stmt = $db->prepare('UPDATE payment_verifications SET status = :s, verified_by = :vb, verified_at = CURRENT_TIMESTAMP, admin_notes = :notes WHERE id = :id');
        $stmt->execute(['s' => $newStatus, 'vb' => $userId, 'notes' => $notes, 'id' => $paymentId]);
        $pay = $db->prepare('SELECT student_id FROM payment_verifications WHERE id = :id');
        $pay->execute(['id' => $paymentId]);
        $payData = $pay->fetch();
        if ($payData) {
            $stu = $db->prepare('SELECT user_id FROM students WHERE id = :sid');
            $stu->execute(['sid' => $payData['student_id']]);
            $uid = (int) $stu->fetchColumn();
            require_once __DIR__ . '/../app/models/Notification.php';
            if ($newStatus === 'verified') {
                Notification::create($uid, 'Payment Verified - Access Granted', 'Your exam fee payment has been verified! You now have full access to all courses and classrooms.', 'payment');
            } else {
                Notification::create($uid, 'Payment Rejected', 'Your payment verification was rejected. Reason: ' . ($notes ?: 'Not specified') . '. Please re-upload.', 'payment');
            }
        }
        $success = 'Payment ' . $newStatus . ' successfully.';
    }
}

$payments = $db->query('SELECT pv.*, users.full_name, users.email, students.phone FROM payment_verifications pv INNER JOIN students ON students.id = pv.student_id INNER JOIN users ON users.id = students.user_id ORDER BY pv.created_at DESC')->fetchAll();

$pageTitle = 'Manage Payments';
require __DIR__ . '/../views/layouts/header.php';
?>
<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Super admin</span><h1>Payment Verification</h1></div>
        </div>
        <?php if ($success): ?><p class="notice notice--success"><?= e($success) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>
        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-credit-card"></i> Exam Fee Payments</h2></div>
            <?php if (empty($payments)): ?>
                <div class="empty-state"><i class="fa-solid fa-credit-card"></i><h3>No payments yet</h3></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Student</th><th>Amount</th><th>Transaction ID</th><th>Sender</th><th>Screenshot</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><strong><?= e($p['full_name']) ?></strong></td>
                                    <td><?= (int) $p['amount'] ?> <?= e($p['currency']) ?></td>
                                    <td><?= e($p['transaction_id'] ?? 'N/A') ?></td>
                                    <td><?= e($p['sender_name'] ?? 'N/A') ?><br><small><?= e($p['sender_phone'] ?? '') ?></small></td>
                                    <td><a href="<?= BASE_URL ?>/download.php?path=<?= e($p['screenshot_path']) ?>" target="_blank" class="btn btn--ghost btn--3d" style="font-size:12px;min-height:30px;padding:0 10px"><i class="fa-solid fa-image"></i> View</a></td>
                                    <td><?= e($p['created_at']) ?></td>
                                    <td><span class="status" style="<?= $p['status'] === 'verified' ? 'background:#ecfdf5;color:#065f46' : ($p['status'] === 'rejected' ? 'background:#fef2f2;color:#991b1b' : 'background:#fffbeb;color:#92400e') ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                                    <td>
                                        <?php if ($p['status'] === 'pending'): ?>
                                            <form method="post" style="display:flex;gap:6px;flex-wrap:wrap">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                                                <input type="hidden" name="admin_notes" value="">
                                                <button class="btn btn--primary btn--3d" name="status" value="verified" style="font-size:12px;min-height:30px;padding:0 10px"><i class="fa-solid fa-check"></i> Verify</button>
                                                <button class="btn btn--danger btn--3d" name="status" value="rejected" style="font-size:12px;min-height:30px;padding:0 10px"><i class="fa-solid fa-xmark"></i> Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="status"><?= e(ucfirst($p['status'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>

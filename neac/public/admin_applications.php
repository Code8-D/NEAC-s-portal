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

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $appId = (int) ($_POST['application_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $notes = trim($_POST['admin_notes'] ?? '');

    if ($appId && in_array($newStatus, ['approved', 'rejected'])) {
        $stmt = $db->prepare('UPDATE certification_applications SET status = :s, reviewed_by = :rb, reviewed_at = CURRENT_TIMESTAMP, admin_notes = :notes WHERE id = :id');
        $stmt->execute(['s' => $newStatus, 'rb' => $userId, 'notes' => $notes, 'id' => $appId]);
        $app = $db->prepare('SELECT student_id FROM certification_applications WHERE id = :id');
        $app->execute(['id' => $appId]);
        $appData = $app->fetch();
        if ($appData) {
            $stu = $db->prepare('SELECT user_id FROM students WHERE id = :sid');
            $stu->execute(['sid' => $appData['student_id']]);
            $uid = (int) $stu->fetchColumn();
            require_once __DIR__ . '/../app/models/Notification.php';
            if ($newStatus === 'approved') {
                Notification::create($uid, 'Application Approved', 'Your certification application has been approved! Please proceed to pay the exam fee and upload your recommendation forms.', 'application');
            } else {
                Notification::create($uid, 'Application Rejected', 'Your certification application has been rejected. Reason: ' . ($notes ?: 'Not specified'), 'application');
            }
        }
        $success = 'Application ' . $newStatus . ' successfully.';
    }
}

$applications = $db->query('SELECT ca.*, users.full_name, users.email, students.phone FROM certification_applications ca INNER JOIN students ON students.id = ca.student_id INNER JOIN users ON users.id = students.user_id ORDER BY ca.created_at DESC')->fetchAll();

$pageTitle = 'Manage Applications';
require __DIR__ . '/../views/layouts/header.php';
?>
<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Super admin</span><h1>Certification Applications</h1></div>
        </div>

        <?php if ($success): ?><p class="notice notice--success"><?= e($success) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="notice notice--error"><?= e($error) ?></p><?php endif; ?>

        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-file-lines"></i> All Applications</h2></div>
            <?php if (empty($applications)): ?>
                <div class="empty-state"><i class="fa-solid fa-file-lines"></i><h3>No applications yet</h3><p>Applications submitted by students will appear here.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Student</th><th>Email</th><th>Phone</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td><strong><?= e($app['full_name'] ?? 'N/A') ?></strong></td>
                                    <td><?= e($app['email'] ?? '') ?></td>
                                    <td><?= e($app['phone'] ?? '') ?></td>
                                    <td><?= e($app['application_date'] ?: $app['created_at']) ?></td>
                                    <td><span class="status" style="<?= $app['status'] === 'approved' ? 'background:#ecfdf5;color:#065f46' : ($app['status'] === 'rejected' ? 'background:#fef2f2;color:#991b1b' : '') ?>"><?= e(ucfirst($app['status'])) ?></span></td>
                                    <td>
                                        <a class="btn btn--ghost btn--3d" href="admin_applications.php?action=view&id=<?= (int) $app['id'] ?>" style="font-size:12px;min-height:32px;padding:0 12px"><i class="fa-solid fa-eye"></i> View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($action === 'view'): $appId = (int) ($_GET['id'] ?? 0); $app = $db->prepare('SELECT ca.*, users.full_name, users.email, students.phone, programs.name AS program_name FROM certification_applications ca INNER JOIN students ON students.id = ca.student_id INNER JOIN users ON users.id = students.user_id INNER JOIN programs ON programs.id = students.program_id WHERE ca.id = :id'); $app->execute(['id' => $appId]); $app = $app->fetch(); if ($app): $trainings = $db->prepare('SELECT * FROM bb_trainings WHERE application_id = :aid ORDER BY sort_order'); $trainings->execute(['aid' => $appId]); $trainings = $trainings->fetchAll(); $services = $db->prepare('SELECT * FROM bb_services WHERE application_id = :aid ORDER BY sort_order'); $services->execute(['aid' => $appId]); $services = $services->fetchAll(); ?>
            <section class="panel three-d-card" style="margin-top:18px">
                <div class="panel__head"><h2><i class="fa-solid fa-user"></i> Application Details: <?= e($app['full_name']) ?></h2></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px">
                    <div><strong>Program:</strong> <?= e($app['program_name']) ?></div>
                    <div><strong>Date of Birth:</strong> <?= e($app['date_of_birth'] ?? 'N/A') ?></div>
                    <div><strong>Place of Birth:</strong> <?= e($app['place_of_birth'] ?? 'N/A') ?></div>
                    <div><strong>Residence:</strong> <?= e($app['residence'] ?? 'N/A') ?></div>
                    <div><strong>Academic Qualification:</strong> <?= e($app['academic_qualification'] ?? 'N/A') ?></div>
                    <div><strong>Rank in BB:</strong> <?= e($app['rank_in_bb'] ?? 'N/A') ?></div>
                    <div><strong>Study Option:</strong> <?= e($app['study_option'] ?? 'N/A') ?></div>
                    <div><strong>Phone:</strong> <?= e($app['phone'] ?? 'N/A') ?></div>
                    <div><strong>WhatsApp:</strong> <?= e($app['whatsapp'] ?? 'N/A') ?></div>
                    <div><strong>Email:</strong> <?= e($app['email'] ?? 'N/A') ?></div>
                    <div><strong>Church:</strong> <?= e($app['church_name'] ?? $app['candidate_church'] ?? 'N/A') ?></div>
                    <div><strong>BB Company:</strong> <?= e($app['bb_company_name'] ?? 'N/A') ?></div>
                    <div><strong>Association:</strong> <?= e($app['candidate_association'] ?? 'N/A') ?></div>
                    <div><strong>Field:</strong> <?= e($app['candidate_field'] ?? 'N/A') ?></div>
                    <div><strong>Company Captain:</strong> <?= e($app['company_captain_name'] ?? 'N/A') ?> (<?= e($app['company_captain_phone'] ?? '') ?>)</div>
                    <div><strong>Company Chaplain:</strong> <?= e($app['company_chaplain_name'] ?? 'N/A') ?> (<?= e($app['company_chaplain_phone'] ?? '') ?>)</div>
                </div>
                <?php if (!empty($trainings)): ?>
                    <h3 style="margin-top:18px">BB Trainings Attended</h3>
                    <div class="table-wrap">
                        <table><thead><tr><th>#</th><th>Seminar/Training</th><th>Duration</th><th>Certificate</th><th>Signatory</th><th>Venue/Level</th><th>Date</th></tr></thead>
                        <tbody><?php foreach ($trainings as $i => $t): ?><tr><td><?= $i+1 ?></td><td><?= e($t['seminar_name']) ?></td><td><?= e($t['duration']) ?></td><td><?= e($t['certificate_obtained']) ?></td><td><?= e($t['signatory']) ?></td><td><?= e($t['venue_level']) ?></td><td><?= e($t['training_date']) ?></td></tr><?php endforeach; ?></tbody></table>
                    </div>
                <?php endif; ?>
                <?php if ($app['passport_photo_path']): ?>
                    <div style="margin-top:14px"><strong>Passport Photo:</strong> <a href="<?= BASE_URL ?>/<?= e($app['passport_photo_path']) ?>" target="_blank">View Photo</a></div>
                <?php endif; ?>
                <hr style="margin:18px 0;border-color:var(--line)">
                <?php if ($app['status'] === 'submitted' || $app['status'] === 'pending'): ?>
                    <form method="post" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
                        <label style="flex:1;min-width:200px">Admin Notes<textarea name="admin_notes" style="min-height:60px"><?= e($app['admin_notes'] ?? '') ?></textarea></label>
                        <div style="display:flex;gap:8px;align-items:end;padding-bottom:8px">
                            <button class="btn btn--primary btn--3d" name="status" value="approved"><i class="fa-solid fa-check"></i> Approve</button>
                            <button class="btn btn--danger btn--3d" name="status" value="rejected"><i class="fa-solid fa-xmark"></i> Reject</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p><strong>Status:</strong> <span class="status" style="<?= $app['status'] === 'approved' ? 'background:#ecfdf5;color:#065f46' : 'background:#fef2f2;color:#991b1b' ?>"><?= e(ucfirst($app['status'])) ?></span></p>
                    <p><strong>Admin Notes:</strong> <?= e($app['admin_notes'] ?? 'None') ?></p>
                    <p><strong>Reviewed:</strong> <?= e($app['reviewed_at'] ?? 'N/A') ?></p>
                <?php endif; ?>
                <a class="btn btn--ghost btn--3d" href="admin_applications.php" style="margin-top:12px"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </section>
        <?php endif; endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>

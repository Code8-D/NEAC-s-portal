<?php
$hideTopbar = true;
$certStatus = getCertStatus();
$hasAccess = $certStatus['access'];
require __DIR__ . '/../layouts/header.php';
?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Student portal</span><h1>Learning dashboard</h1></div>
            <div class="workspace__head-actions">
                <?php if ($hasAccess): ?>
                    <a class="btn btn--primary btn--3d" href="classroom.php?action=join"><i class="fa-solid fa-plus"></i> Join a class</a>
                <?php endif; ?>
                <a class="btn btn--ghost btn--3d" href="transcript.php"><i class="fa-solid fa-file-lines"></i> My Transcript</a>
            </div>
        </div>

        <!-- Certification Status Banner -->
        <?php if (!$certStatus['applied']): ?>
            <div class="panel three-d-card" style="border-left:4px solid #d9a321;background:linear-gradient(135deg,#fffbeb,#fff8e7)">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                    <div>
                        <span class="eyebrow" style="color:#d9a321"><i class="fa-solid fa-certificate"></i> Certification Required</span>
                        <h3 style="margin:4px 0 0">Apply for the <?= e($certBatch) ?> BB Officers Certification Exam</h3>
                        <p style="color:var(--muted);margin:4px 0 0">You must submit your certification application and complete payment before you can access courses and classrooms.</p>
                    </div>
                    <a class="btn btn--primary btn--3d" href="application.php" style="white-space:nowrap"><i class="fa-solid fa-file-pen"></i> Apply Now</a>
                </div>
            </div>
        <?php elseif ($certStatus['payment_status'] === 'pending'): ?>
            <div class="panel three-d-card" style="border-left:4px solid #d9a321;background:linear-gradient(135deg,#fffbeb,#fff8e7)">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                    <div>
                        <span class="eyebrow" style="color:#d9a321"><i class="fa-solid fa-clock"></i> Awaiting Verification</span>
                        <h3 style="margin:4px 0 0">Payment Pending Verification</h3>
                        <p style="color:var(--muted);margin:4px 0 0">Your payment is being reviewed by the admin. Access will be granted once verified.</p>
                    </div>
                </div>
            </div>
        <?php elseif (!$certStatus['paid']): ?>
            <div class="panel three-d-card" style="border-left:4px solid #d9a321;background:linear-gradient(135deg,#fefce8,#fff8e7)">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                    <div>
                        <span class="eyebrow" style="color:#d9a321"><i class="fa-solid fa-credit-card"></i> Payment Pending</span>
                        <h3 style="margin:4px 0 0">Pay Your Certification Exam Fee</h3>
                        <p style="color:var(--muted);margin:4px 0 0">Pay <?= EXAM_FEE ?> <?= EXAM_FEE_CURRENCY ?> via Mobile Money to <?= MOMO_NUMBER ?> (<?= MOMO_ACCOUNT_NAME ?>) and upload the screenshot.</p>
                        <?php if ($certStatus['payment_status'] === 'rejected'): ?>
                            <p style="color:#991b1b;margin:4px 0 0"><i class="fa-solid fa-circle-exclamation"></i> Your previous payment was rejected. Please submit a valid transaction.</p>
                        <?php endif; ?>
                    </div>
                    <a class="btn btn--primary btn--3d" href="payment.php" style="white-space:nowrap"><i class="fa-solid fa-credit-card"></i> Pay Now</a>
                </div>
            </div>
        <?php elseif ($certStatus['applied'] && !$hasAccess): ?>
            <div class="panel three-d-card" style="border-left:4px solid #d9a321;background:linear-gradient(135deg,#fffbeb,#fff8e7)">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                    <div>
                        <span class="eyebrow" style="color:#d9a321"><i class="fa-solid fa-file-shield"></i> Application Under Review</span>
                        <h3 style="margin:4px 0 0">Application Pending Approval</h3>
                        <p style="color:var(--muted);margin:4px 0 0">Your payment is verified. Access will be granted once your application is approved.</p>
                    </div>
                </div>
            </div>
        <?php elseif ($hasAccess): ?>
            <div class="panel three-d-card" style="border-left:4px solid #065f46;background:linear-gradient(135deg,#ecfdf5,#f0fdf6)">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                    <div>
                        <span class="eyebrow" style="color:#065f46"><i class="fa-solid fa-check-circle"></i> Full Access Granted</span>
                        <h3 style="margin:4px 0 0">Welcome to the <?= e($certBatch) ?> BB Certification Program</h3>
                        <p style="color:var(--muted);margin:4px 0 0">You have full access to all courses, classrooms, and learning materials.</p>
                    </div>
                    <div style="display:flex;gap:8px">
                        <a class="btn btn--ghost btn--3d" href="recommendation.php"><i class="fa-solid fa-file-signature"></i> Upload Recs</a>
                        <a class="btn btn--ghost btn--3d" href="transcript.php"><i class="fa-solid fa-file-lines"></i> Transcript</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($section === 'overview' || $section === 'classrooms'): ?>
            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-school"></i> Your classrooms</h2>
                </div>
                <?php if (!$hasAccess): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-lock"></i>
                        <h3>Classrooms Locked</h3>
                        <p>Complete your certification application and payment to unlock classroom access.</p>
                        <a class="btn btn--primary btn--3d" href="application.php"><i class="fa-solid fa-file-pen"></i> Complete Application</a>
                    </div>
                <?php elseif (empty($classrooms)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-school"></i>
                        <h3>You haven't joined a class yet</h3>
                        <p>Get a class code from your teacher, or open a join link they shared, then enter the code to join.</p>
                        <a class="btn btn--primary btn--3d" href="classroom.php?action=join"><i class="fa-solid fa-keyboard"></i> Enter a class code</a>
                    </div>
                <?php else: ?>
                    <div class="classroom-card-grid">
                        <?php foreach ($classrooms as $room): ?>
                            <a class="classroom-card three-d-card" href="classroom.php?action=view&amp;id=<?= (int) $room['id'] ?>" style="--banner: <?= e($room['banner_color'] ?? '#102b4e') ?>">
                                <div class="classroom-card__banner"></div>
                                <div class="classroom-card__body">
                                    <span class="eyebrow"><?= e($room['program_name']) ?></span>
                                    <h3><?= e($room['title']) ?></h3>
                                    <small><i class="fa-solid fa-user-tie"></i> <?= e($room['member_role'] ?? 'student') ?> • <?= (int) $room['member_count'] ?> classmates</small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'assignments'): ?>
            <section class="panel three-d-card">
                <h2><i class="fa-solid fa-file-pen"></i> Your assignments</h2>
                <?php if (!$hasAccess): ?>
                    <div class="empty-state"><i class="fa-solid fa-lock"></i><h3>Assignments Locked</h3><p>Complete certification requirements first.</p></div>
                <?php else:
                $assignments = [];
                if (!empty($classrooms)) {
                    $ids = array_column($classrooms, 'id');
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = Database::connection()->prepare(
                        "SELECT classwork.title, classwork.due_at, classwork.max_score, classrooms.title AS classroom_title, classrooms.id AS classroom_id, classwork.id AS classwork_id
                         FROM classroom_classwork classwork
                         INNER JOIN classrooms ON classrooms.id = classwork.classroom_id
                         WHERE classwork.work_type = 'assignment' AND classwork.classroom_id IN ($placeholders)
                         ORDER BY classwork.due_at ASC"
                    );
                    $stmt->execute($ids);
                    $assignments = $stmt->fetchAll();
                }
                ?>
                <?php if (empty($assignments)): ?>
                    <div class="empty-state"><i class="fa-solid fa-file-pen"></i><h3>No assignments</h3><p>Assignments will appear here once your teacher posts them.</p></div>
                <?php else: ?>
                    <ul class="aside-list">
                        <?php foreach ($assignments as $a): ?>
                            <li>
                                <a href="classroom.php?action=classwork&amp;work_id=<?= (int) $a['classwork_id'] ?>"><?= e($a['title']) ?></a>
                                <small><?= e($a['classroom_title']) ?> <?= !empty($a['due_at']) ? '• Due ' . e($a['due_at']) : '' ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'reports'): ?>
            <?php require __DIR__ . '/reports.view.php'; ?>
        <?php elseif ($section === 'certification'): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-certificate"></i> Certification Progress</h2></div>
                <div style="display:grid;gap:14px;margin-top:14px">
                    <div style="display:flex;align-items:center;gap:14px;padding:16px;border-radius:8px;background:<?= $certStatus['applied'] ? '#ecfdf5' : '#f8fafc' ?>;border:1px solid var(--line)">
                        <i class="fa-solid fa-file-pen" style="font-size:24px;color:<?= $certStatus['applied'] ? '#065f46' : 'var(--muted)' ?>"></i>
                        <div style="flex:1"><strong>1. Application</strong><br><small style="color:var(--muted)">Submit certification application</small></div>
                        <?php if ($certStatus['applied']): ?><span class="status" style="background:#ecfdf5;color:#065f46"><i class="fa-solid fa-check"></i> Done</span>
                        <?php else: ?><a class="btn btn--primary btn--3d" href="application.php" style="font-size:12px;min-height:32px;padding:0 14px">Apply</a><?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;padding:16px;border-radius:8px;background:<?= $certStatus['paid'] ? '#ecfdf5' : '#f8fafc' ?>;border:1px solid var(--line)">
                        <i class="fa-solid fa-credit-card" style="font-size:24px;color:<?= $certStatus['paid'] ? '#065f46' : 'var(--muted)' ?>"></i>
                        <div style="flex:1"><strong>2. Payment</strong><br><small style="color:var(--muted)">Pay exam fee and upload screenshot</small></div>
                        <?php if ($certStatus['paid']): ?><span class="status" style="background:#ecfdf5;color:#065f46"><i class="fa-solid fa-check"></i> Verified</span>
                        <?php else: ?><a class="btn btn--primary btn--3d" href="payment.php" style="font-size:12px;min-height:32px;padding:0 14px">Pay</a><?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;padding:16px;border-radius:8px;background:#f8fafc;border:1px solid var(--line)">
                        <i class="fa-solid fa-file-signature" style="font-size:24px;color:var(--muted)"></i>
                        <div style="flex:1"><strong>3. Recommendations</strong><br><small style="color:var(--muted)">Upload recommendation forms (<?= $certStatus['recommendations_uploaded'] ?> uploaded)</small></div>
                        <a class="btn btn--ghost btn--3d" href="recommendation.php" style="font-size:12px;min-height:32px;padding:0 14px">Manage</a>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;padding:16px;border-radius:8px;background:<?= $hasAccess ? '#ecfdf5' : '#f8fafc' ?>;border:1px solid var(--line)">
                        <i class="fa-solid fa-school" style="font-size:24px;color:<?= $hasAccess ? '#065f46' : 'var(--muted)' ?>"></i>
                        <div style="flex:1"><strong>4. Access Learning</strong><br><small style="color:var(--muted)">Full access to classes, courses, and materials</small></div>
                        <?php if ($hasAccess): ?><span class="status" style="background:#ecfdf5;color:#065f46"><i class="fa-solid fa-check"></i> Active</span>
                        <?php else: ?><span class="status" style="background:#fffbeb;color:#92400e">Locked</span><?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

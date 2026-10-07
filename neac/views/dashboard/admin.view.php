<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Super admin</span><h1>Operations dashboard</h1></div>
            <div class="workspace__head-actions">
                <a class="btn btn--ghost btn--3d" href="dashboard.php?section=classrooms"><i class="fa-solid fa-school"></i> Classrooms</a>
                <?php if ($section === 'classrooms'): ?>
                    <a class="btn btn--primary btn--3d" href="dashboard.php?section=courses#add-course"><i class="fa-solid fa-plus"></i> New course</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($section === 'overview'): ?>
            <div class="stat-grid">
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-users"></i><strong><?= (int) $adminStats['students'] ?></strong><span>Active students</span></div>
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-book-open"></i><strong><?= (int) $adminStats['courses'] ?></strong><span>Courses</span></div>
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-person-chalkboard"></i><strong><?= (int) $adminStats['lecturers'] ?></strong><span>Lecturers</span></div>
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-school"></i><strong><?= (int) $adminStats['classrooms'] ?></strong><span>Classrooms</span></div>
            </div>

            <div class="stat-grid" style="margin-top:14px">
                <a class="stat-tile three-d-tile" href="quizzes.php"><i class="fa-solid fa-pen-ruler"></i><strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM quizzes')->fetchColumn() ?></strong><span>Quizzes</span></a>
                <a class="stat-tile three-d-tile" href="lessons.php"><i class="fa-solid fa-book-open"></i><strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM lessons')->fetchColumn() ?></strong><span>Lessons</span></a>
                <a class="stat-tile three-d-tile" href="discussions.php"><i class="fa-solid fa-comments"></i><strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM discussion_threads')->fetchColumn() ?></strong><span>Discussions</span></a>
                <a class="stat-tile three-d-tile" href="results.php"><i class="fa-solid fa-chart-line"></i><strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM results')->fetchColumn() ?></strong><span>Results</span></a>
            </div>

            <div class="stat-grid" style="margin-top:14px">
                <a class="stat-tile three-d-tile" href="admin_applications.php"><i class="fa-solid fa-file-lines"></i><strong><?= (int) Database::connection()->query("SELECT COUNT(*) FROM certification_applications WHERE status = 'submitted'")->fetchColumn() ?></strong><span>Pending Apps</span></a>
                <a class="stat-tile three-d-tile" href="admin_payments.php"><i class="fa-solid fa-credit-card"></i><strong><?= (int) Database::connection()->query("SELECT COUNT(*) FROM payment_verifications WHERE status = 'pending'")->fetchColumn() ?></strong><span>Pending Payments</span></a>
                <a class="stat-tile three-d-tile" href="admin_grading.php"><i class="fa-solid fa-chart-simple"></i><strong><?= (int) Database::connection()->query("SELECT COUNT(*) FROM student_marks WHERE status = 'submitted'")->fetchColumn() ?></strong><span>Marks to Approve</span></a>
                <a class="stat-tile three-d-tile" href="admin_grading.php"><i class="fa-solid fa-file-lines"></i><strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM transcripts')->fetchColumn() ?></strong><span>Transcripts</span></a>
            </div>

            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-school"></i> Recent classrooms</h2>
                    <a class="btn btn--ghost btn--3d" href="dashboard.php?section=classrooms">All classrooms</a>
                </div>
                <?php if (empty($classrooms)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-school"></i>
                        <h3>No classrooms yet</h3>
                        <p>Create a course — a classroom is generated automatically for it.</p>
                    </div>
                <?php else: ?>
                    <div class="classroom-card-grid">
                        <?php foreach (array_slice($classrooms, 0, 6) as $room): ?>
                            <a class="classroom-card three-d-card" href="classroom.php?action=view&amp;id=<?= (int) $room['id'] ?>" style="--banner: <?= e($room['banner_color'] ?? '#102b4e') ?>">
                                <div class="classroom-card__banner"></div>
                                <div class="classroom-card__body">
                                    <span class="eyebrow"><?= e($room['program_name']) ?></span>
                                    <h3><?= e($room['title']) ?></h3>
                                    <small><i class="fa-solid fa-key"></i> <?= e($room['code']) ?> • <?= (int) $room['member_count'] ?> members</small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'classrooms'): ?>
            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-school"></i> All classrooms</h2>
                </div>
                <?php if (empty($classrooms)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-school"></i>
                        <h3>No classrooms yet</h3>
                        <p>Add a course first — a classroom is created automatically with a unique join code.</p>
                    </div>
                <?php else: ?>
                    <div class="classroom-card-grid">
                        <?php foreach ($classrooms as $room): ?>
                            <a class="classroom-card three-d-card" href="classroom.php?action=view&amp;id=<?= (int) $room['id'] ?>" style="--banner: <?= e($room['banner_color'] ?? '#102b4e') ?>">
                                <div class="classroom-card__banner"></div>
                                <div class="classroom-card__body">
                                    <span class="eyebrow"><?= e($room['program_name']) ?></span>
                                    <h3><?= e($room['title']) ?></h3>
                                    <small><i class="fa-solid fa-key"></i> <?= e($room['code']) ?> • <?= (int) $room['member_count'] ?> members</small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'applications'): ?>
            <?php require_once __DIR__ . '/../../app/models/Setting.php'; ?>
            <?php
            $certBatch = (string) (Setting::get('cert_batch_label') ?? '4th Batch');
            $certOpens = (string) (Setting::get('cert_opens_at') ?? '');
            $certCloses = (string) (Setting::get('cert_closes_at') ?? '');
            $certOpen = Setting::get('cert_applications_open') !== '0';
            ?>
            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-door-open"></i> Certification application period</h2>
                </div>
                <p class="muted">When applications are closed, students who try to apply are halted and told the batch is already going on. Existing applicants are not affected.</p>
                <form method="post" action="<?= BASE_URL ?>/dashboard.php?section=applications" class="upload-form" style="margin-top:14px">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="save_cert_period">
                    <label>Batch label <input type="text" name="cert_batch_label" value="<?= e($certBatch) ?>" maxlength="60" placeholder="e.g. 4th Batch"></label>
                    <label>Applications open on <input type="date" name="cert_opens_at" value="<?= e($certOpens) ?>"></label>
                    <label>Applications close on <input type="date" name="cert_closes_at" value="<?= e($certCloses) ?>"></label>
                    <label class="upload-form__wide" style="display:flex;gap:8px;align-items:center;font-weight:600">
                        <input type="checkbox" name="cert_applications_open" <?= $certOpen ? 'checked' : '' ?> style="width:auto;height:auto"> Accept new applications
                    </label>
                    <button class="btn btn--primary btn--3d upload-form__wide" type="submit"><i class="fa-solid fa-save"></i> Save period</button>
                </form>
            </section>
            <?php
            $apps = Database::connection()->query('SELECT ca.*, users.full_name, users.email FROM certification_applications ca INNER JOIN students ON students.id = ca.student_id INNER JOIN users ON users.id = students.user_id ORDER BY ca.created_at DESC')->fetchAll();
            ?>
            <?php if (!empty($_GET['period_saved'])): ?>
                <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Certification application period saved.</p>
            <?php endif; ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-file-lines"></i> Certification Applications</h2>
                    <a class="btn btn--primary btn--3d" href="admin_applications.php"><i class="fa-solid fa-external-link"></i> Full Management</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Student</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($apps as $a): ?>
                                <tr>
                                    <td><?= e($a['full_name']) ?></td>
                                    <td><span class="status" style="<?= $a['status'] === 'approved' ? 'background:#ecfdf5;color:#065f46' : ($a['status'] === 'submitted' ? 'background:#fffbeb;color:#92400e' : '') ?>"><?= e(ucfirst($a['status'])) ?></span></td>
                                    <td><?= e($a['created_at']) ?></td>
                                    <td><a class="btn btn--ghost btn--3d" href="admin_applications.php?action=view&id=<?= (int) $a['id'] ?>" style="font-size:12px;min-height:30px;padding:0 10px">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($section === 'payments'): ?>
            <?php
            $payments = Database::connection()->query('SELECT pv.*, users.full_name FROM payment_verifications pv INNER JOIN students ON students.id = pv.student_id INNER JOIN users ON users.id = students.user_id ORDER BY pv.created_at DESC')->fetchAll();
            ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-credit-card"></i> Payment Verifications</h2>
                    <a class="btn btn--primary btn--3d" href="admin_payments.php"><i class="fa-solid fa-external-link"></i> Full Management</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Student</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= e($p['full_name']) ?></td>
                                    <td><?= (int) $p['amount'] ?> <?= e($p['currency']) ?></td>
                                    <td><span class="status" style="<?= $p['status'] === 'verified' ? 'background:#ecfdf5;color:#065f46' : ($p['status'] === 'pending' ? 'background:#fffbeb;color:#92400e' : 'background:#fef2f2;color:#991b1b') ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                                    <td><a class="btn btn--ghost btn--3d" href="admin_payments.php" style="font-size:12px;min-height:30px;padding:0 10px">Manage</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php elseif ($section === 'grading'): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-chart-simple"></i> Grading & Transcripts</h2>
                    <a class="btn btn--primary btn--3d" href="admin_grading.php"><i class="fa-solid fa-external-link"></i> Full Management</a>
                </div>
                <?php
                $submitted = (int) Database::connection()->query("SELECT COUNT(*) FROM student_marks WHERE status = 'submitted'")->fetchColumn();
                $transcriptCount = (int) Database::connection()->query('SELECT COUNT(*) FROM transcripts')->fetchColumn();
                ?>
                <p style="margin-top:12px"><?= $submitted ?> marks pending approval | <?= $transcriptCount ?> transcripts generated</p>
                <div style="display:flex;gap:12px;margin-top:12px">
                    <a class="btn btn--primary btn--3d" href="admin_grading.php">Review Marks</a>
                    <a class="btn btn--ghost btn--3d" href="admin_grading.php?action=transcripts">View Transcripts</a>
                </div>
            </section>
        <?php elseif ($section === 'courses'): ?>
            <?php require __DIR__ . '/course_create.view.php'; ?>
            <?php require __DIR__ . '/../courses/list.view.php'; ?>
        <?php elseif ($section === 'materials'): ?>
            <?php
            $allMaterials = [];
            try {
                $allMaterials = Database::connection()->query(
                    'SELECT files.id, files.original_name, files.stored_path, files.mime_type, files.file_size,
                            files.upload_context, files.created_at, users.full_name AS uploader
                     FROM files
                     LEFT JOIN users ON users.id = files.uploaded_by
                     WHERE files.upload_context LIKE "teaching_material:%"
                     ORDER BY files.created_at DESC LIMIT 100'
                )->fetchAll();
            } catch (Throwable $e) {}
            ?>
            <?php if (($_GET['upload'] ?? '') === 'success'): ?>
                <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Material uploaded successfully.</p>
            <?php endif; ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-cloud-arrow-up"></i> Course materials uploaded by lecturers (<?= count($allMaterials) ?>)</h2></div>
                <p class="muted">Everything a lecturer uploads via Dashboard → Materials appears here automatically, with the uploader and course code attached.</p>
                <?php if (empty($allMaterials)): ?>
                    <div class="empty-state"><i class="fa-solid fa-folder-open"></i><h3>No materials yet</h3><p>Lecturer uploads will appear here.</p></div>
                <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>File</th><th>Lecturer</th><th>Course</th><th>Uploaded</th></tr></thead>
                        <tbody>
                            <?php foreach ($allMaterials as $file): ?>
                                <?php $meta = json_decode((string) substr($file['upload_context'], strlen('teaching_material:')), true) ?: []; ?>
                                <tr>
                                    <td><a href="<?= BASE_URL ?>/<?= e($file['stored_path']) ?>" target="_blank" rel="noopener"><?= e($file['original_name']) ?></a><br><small class="muted"><?= e($meta['title'] ?? '') ?> • <?= number_format((int) $file['file_size'] / 1024) ?> KB</small></td>
                                    <td><?= e($file['uploader'] ?? '—') ?></td>
                                    <td><?= e($meta['course'] ?? '') ?></td>
                                    <td><?= e(substr($file['created_at'], 0, 16)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'students'): ?>
            <?php require __DIR__ . '/../students/list.view.php'; ?>
        <?php elseif ($section === 'lecturers'): ?>
            <?php require __DIR__ . '/lecturers.view.php'; ?>
        <?php elseif ($section === 'reports'): ?>
            <?php require __DIR__ . '/reports.view.php'; ?>
        <?php elseif ($section === 'home'): ?>
            <?php require_once __DIR__ . '/../../app/models/HomePost.php'; ?>
            <?php require __DIR__ . '/home_posts.view.php'; ?>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Teacher portal</span><h1>Teaching workspace</h1></div>
            <div class="workspace__head-actions">
                <a class="btn btn--primary btn--3d" href="classroom.php?action=view&id=<?= (int) ($classrooms[0]['id'] ?? 0) ?>&section=classwork"><i class="fa-solid fa-plus"></i> New classwork</a>
            </div>
        </div>

        <?php if ($section === 'overview'): ?>
            <?php if (($_GET['upload'] ?? '') === 'success'): ?>
                <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Course material uploaded successfully.</p>
            <?php endif; ?>
            <div class="stat-grid">
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-school"></i><strong><?= count($classrooms) ?></strong><span>Your classrooms</span></div>
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-users"></i><strong><?= array_sum(array_column($classrooms, 'member_count')) ?></strong><span>Students taught</span></div>
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-folder-tree"></i><strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM classroom_classwork WHERE author_id = ' . (int) $userId)->fetchColumn() ?></strong><span>Classwork posted</span></div>
                <div class="stat-tile three-d-tile"><i class="fa-solid fa-paper-plane"></i><strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM classroom_submissions')->fetchColumn() ?></strong><span>Submissions received</span></div>
            </div>

            <div class="stat-grid" style="margin-top:14px">
                <a class="stat-tile three-d-tile" href="quizzes.php">
                    <i class="fa-solid fa-pen-ruler"></i>
                    <strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM quizzes')->fetchColumn() ?></strong>
                    <span>Quizzes created</span>
                </a>
                <a class="stat-tile three-d-tile" href="lessons.php">
                    <i class="fa-solid fa-book-open"></i>
                    <strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM lessons')->fetchColumn() ?></strong>
                    <span>Lessons planned</span>
                </a>
                <a class="stat-tile three-d-tile" href="discussions.php">
                    <i class="fa-solid fa-comments"></i>
                    <strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM discussion_threads')->fetchColumn() ?></strong>
                    <span>Discussions active</span>
                </a>
                <a class="stat-tile three-d-tile" href="results.php">
                    <i class="fa-solid fa-chart-line"></i>
                    <strong><?= (int) Database::connection()->query('SELECT COUNT(*) FROM results')->fetchColumn() ?></strong>
                    <span>Results published</span>
                </a>
            </div>

            <section class="panel three-d-card">
                <div class="panel__head">
                    <h2><i class="fa-solid fa-school"></i> Your classrooms</h2>
                </div>
                <?php if (empty($classrooms)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-school"></i>
                        <h3>You don't have a classroom yet</h3>
                        <p>The admin will assign you to a course. Each course automatically gets its own classroom.</p>
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
        <?php elseif ($section === 'classrooms'): ?>
            <section class="panel three-d-card">
                <h2><i class="fa-solid fa-school"></i> Your classrooms</h2>
                <?php if (empty($classrooms)): ?>
                    <div class="empty-state"><i class="fa-solid fa-school"></i><h3>No classroom assigned</h3><p>The admin will assign you to a course.</p></div>
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
        <?php elseif ($section === 'students'): ?>
            <?php require __DIR__ . '/../students/list.view.php'; ?>
        <?php elseif ($section === 'materials'): ?>
            <?php
            $teacherIdForUpload = (int) Auth::teacherId();
            $uploadCourses = [];
            $myMaterials = [];
            try {
                $courseStmt = Database::connection()->prepare(
                    'SELECT id, title, code FROM courses WHERE teacher_id = :tid ORDER BY title'
                );
                $courseStmt->execute(['tid' => $teacherIdForUpload]);
                $uploadCourses = $courseStmt->fetchAll();
                $fileStmt = Database::connection()->prepare(
                    'SELECT id, original_name, stored_path, mime_type, file_size, upload_context, created_at
                     FROM files WHERE uploaded_by = :uid AND upload_context LIKE "teaching_material:%"
                     ORDER BY created_at DESC LIMIT 50'
                );
                $fileStmt->execute(['uid' => $userId]);
                $myMaterials = $fileStmt->fetchAll();
            } catch (Throwable $e) {}
            ?>
            <?php if (($_GET['upload'] ?? '') === 'success'): ?>
                <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Course material uploaded successfully.</p>
            <?php endif; ?>
            <?php require __DIR__ . '/teacher_upload.view.php'; ?>
            <section class="panel three-d-card" style="margin-top:16px">
                <div class="panel__head"><h2><i class="fa-solid fa-folder-open"></i> My uploaded materials (<?= count($myMaterials) ?>)</h2></div>
                <?php if (empty($myMaterials)): ?>
                    <div class="empty-state"><i class="fa-solid fa-file-arrow-up"></i><h3>No materials yet</h3><p>Upload your first notes, slides, or video above. They can then be reused in the lesson planner.</p></div>
                <?php else: ?>
                    <div class="table-wrap"><table>
                        <thead><tr><th>File</th><th>Course</th><th>Size</th><th>Uploaded</th></tr></thead>
                        <tbody>
                            <?php foreach ($myMaterials as $file): ?>
                                <?php $meta = json_decode((string) substr($file['upload_context'], strlen('teaching_material:')), true) ?: []; ?>
                                <tr>
                                    <td><a href="<?= BASE_URL ?>/<?= e($file['stored_path']) ?>" target="_blank" rel="noopener"><?= e($file['original_name']) ?></a><br><small class="muted"><?= e($meta['title'] ?? '') ?></small></td>
                                    <td><?= e($meta['course'] ?? '') ?></td>
                                    <td><?= number_format((int) $file['file_size'] / 1024) ?> KB</td>
                                    <td><?= e(substr($file['created_at'], 0, 16)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'grading'): ?>
            <section class="panel three-d-card">
                <div class="panel__head"><h2><i class="fa-solid fa-chart-simple"></i> Student Grading</h2>
                    <a class="btn btn--primary btn--3d" href="lecturer_grading.php"><i class="fa-solid fa-external-link"></i> Enter Marks</a>
                </div>
                <?php
                $teacherId = (int) Auth::teacherId();
                $myMarks = Database::connection()->prepare('SELECT sm.*, users.full_name AS student_name, courses.title AS course_title, courses.code AS course_code FROM student_marks sm INNER JOIN students ON students.id = sm.student_id INNER JOIN users ON users.id = students.user_id INNER JOIN courses ON courses.id = sm.course_id WHERE sm.lecturer_id = :lid ORDER BY sm.updated_at DESC LIMIT 20');
                $myMarks->execute(['lid' => $teacherId]);
                $myMarks = $myMarks->fetchAll();
                ?>
                <?php if (empty($myMarks)): ?>
                    <div class="empty-state"><i class="fa-solid fa-chart-simple"></i><h3>No marks entered yet</h3><p>Select a course and enter student marks.</p>
                        <a class="btn btn--primary btn--3d" href="lecturer_grading.php">Start Grading</a>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Student</th><th>Course</th><th>Total</th><th>Grade</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($myMarks as $m): ?>
                                    <tr>
                                        <td><?= e($m['student_name']) ?></td>
                                        <td><?= e($m['course_code']) ?></td>
                                        <td><strong><?= (float) $m['total_score'] ?></strong></td>
                                        <td><?= e($m['grade']) ?></td>
                                        <td><span class="status" style="<?= $m['status'] === 'approved' ? 'background:#ecfdf5;color:#065f46' : ($m['status'] === 'submitted' ? 'background:#fffbeb;color:#92400e' : '') ?>"><?= e(ucfirst($m['status'])) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a class="btn btn--ghost btn--3d" href="lecturer_grading.php" style="margin-top:12px">Manage All Marks</a>
                <?php endif; ?>
            </section>
        <?php elseif ($section === 'reports'): ?>
            <?php require __DIR__ . '/reports.view.php'; ?>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

<?php
$pageTitle = $classwork['title'] ?? 'Classwork';
require __DIR__ . '/../layouts/header.php';
?>
<main class="app-shell classroom-shell">
    <section class="workspace">
        <div class="workspace__head">
            <div>
                <span class="eyebrow"><a href="classroom.php?action=view&amp;id=<?= (int) ($classwork['classroom_id'] ?? ($_GET['classroom'] ?? 0)) ?>"><i class="fa-solid fa-arrow-left"></i> Back to classroom</a></span>
                <h1><?= e($classwork['title'] ?? 'Classwork') ?></h1>
            </div>
        </div>

        <article class="classwork-detail three-d-card">
            <header>
                <span class="badge badge--<?= e($classwork['work_type']) ?>"><?= e(ucfirst($classwork['work_type'])) ?></span>
                <h1><?= e($classwork['title']) ?></h1>
                <p class="muted">Posted by <?= e($classwork['author_name']) ?> on <?= e($classwork['created_at']) ?>
                <?php if (!empty($classwork['due_at'])): ?> • Due <?= e($classwork['due_at']) ?><?php endif; ?></p>
            </header>

            <?php if (!empty($classwork['description'])): ?>
                <p><?= nl2br(e($classwork['description'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($classwork['instructions'])): ?>
                <h3>Instructions</h3>
                <p><?= nl2br(e($classwork['instructions'])) ?></p>
            <?php endif; ?>

            <?php if (!empty($attachments)): ?>
                <h3>Materials</h3>
                <ul class="file-list">
                    <?php foreach ($attachments as $att): ?>
                        <li>
                            <i class="fa-solid <?= str_starts_with(($att['mime_type'] ?? ''), 'image/') ? 'fa-image' : 'fa-file' ?>"></i>
                            <?php if (!empty($att['stored_path'])): ?>
                                <a href="<?= BASE_URL ?>/download.php?file=<?= (int) $att['file_id'] ?>" target="_blank" rel="noopener"><?= e($att['original_name'] ?? 'Attachment') ?></a>
                            <?php else: ?>
                                <?= e($att['link_title'] ?? $att['link_url'] ?? 'Attachment') ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($classwork['work_type'] === 'assignment' && $isTeacher): ?>
                <section class="panel three-d-card submissions-panel">
                    <h2>Submissions</h2>
                    <?php if (empty($submissions)): ?>
                        <p class="muted">No submissions yet.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead><tr><th>Student</th><th>Submitted</th><th>Status</th><th>Score</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach ($submissions as $s): ?>
                                        <tr>
                                            <td><?= e($s['student_name']) ?><br><small><?= e($s['student_number'] ?? '') ?></small></td>
                                            <td><?= e($s['submitted_at']) ?></td>
                                            <td><span class="status"><?= e(ucfirst($s['status'])) ?></span></td>
                                            <td><?= number_format((float) ($s['total_points'] ?? 0), 1) ?></td>
                                            <td><a class="btn btn--ghost btn--3d" href="classroom.php?action=grade&amp;submission_id=<?= (int) $s['id'] ?>"><i class="fa-solid fa-pen-to-square"></i> Grade</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($classwork['work_type'] === 'assignment' && !$isTeacher && Auth::studentId()): ?>
                <section class="panel three-d-card submission-panel">
                    <h2>Your submission</h2>
                    <?php if (!empty($_GET['submitted'])): ?>
                        <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Your work was submitted.</p>
                    <?php endif; ?>
                    <?php if ($studentSubmission): ?>
                        <p><i class="fa-solid fa-file"></i>
                            <?php if (!empty($studentSubmission['stored_path'])): ?>
                                <a href="<?= BASE_URL ?>/download.php?file=<?= (int) $studentSubmission['file_id'] ?>" target="_blank" rel="noopener"><?= e($studentSubmission['original_name'] ?? 'View your file') ?></a>
                            <?php else: ?>
                                Submission on file
                            <?php endif; ?>
                        </p>
                        <p>Status: <span class="status"><?= e(ucfirst($studentSubmission['status'])) ?></span></p>
                        <?php if (!empty($studentSubmission['note'])): ?>
                            <p>Your note: <?= e($studentSubmission['note']) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form class="upload-form" method="post" action="<?= BASE_URL ?>/classroom_action.php" enctype="multipart/form-data">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="submit_assignment">
                        <input type="hidden" name="classwork_id" value="<?= (int) $classwork['id'] ?>">
                        <label class="upload-form__wide">
                            Note to your teacher (optional)
                            <textarea name="note" rows="2" placeholder="Anything your teacher should know"></textarea>
                        </label>
                        <label class="file-drop upload-form__wide">
                            <i class="fa-solid fa-file-arrow-up"></i>
                            <span>Choose your file</span>
                            <small>PDF, Word, image, video, or ZIP up to 80MB</small>
                            <input type="file" name="upload_file" required>
                        </label>
                        <button class="btn btn--primary btn--3d upload-form__wide" type="submit"><i class="fa-solid fa-paper-plane"></i> Submit</button>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($rubric && $isTeacher): ?>
                <section class="panel three-d-card">
                    <h2>Rubric</h2>
                    <ul class="rubric-list">
                        <?php foreach ($rubric['criteria'] as $c): ?>
                            <li>
                                <strong><?= e($c['label']) ?></strong> (<?= (float) $c['max_points'] ?> pts)
                                <?php if (!empty($c['description'])): ?><p class="muted"><?= e($c['description']) ?></p><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

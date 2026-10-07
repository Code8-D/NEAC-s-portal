<?php
$totalAwarded = 0;
$totalMax = 0;
foreach ($rubric['criteria'] ?? [] as $c) {
    $totalMax += (float) $c['max_points'];
    if (isset($gradeByCriterion[(int) $c['id']])) {
        $totalAwarded += (float) $gradeByCriterion[(int) $c['id']]['points'];
    }
}
require __DIR__ . '/../layouts/header.php';
?>
<main class="app-shell classroom-shell">
    <section class="workspace">
        <div class="workspace__head">
            <div>
                <span class="eyebrow"><a href="classroom.php?action=classwork&amp;work_id=<?= (int) $submission['classwork_id'] ?>"><i class="fa-solid fa-arrow-left"></i> Back to classwork</a></span>
                <h1>Grade: <?= e($submission['student_name']) ?></h1>
                <p class="muted"><?= e($submission['classwork_title']) ?> • Max <?= (float) $submission['max_score'] ?> pts</p>
            </div>
            <div class="rubric-total">
                <strong><?= number_format($totalAwarded, 1) ?></strong> / <?= number_format($totalMax, 1) ?> pts
            </div>
        </div>

        <article class="classwork-detail three-d-card">
            <section>
                <h3>Submission</h3>
                <?php if (!empty($submission['stored_path'])): ?>
                    <p><i class="fa-solid fa-file"></i> <a href="<?= BASE_URL ?>/download.php?file=<?= (int) ($submission['file_id'] ?? 0) ?>" target="_blank" rel="noopener"><?= e($submission['original_name'] ?? 'View file') ?></a></p>
                <?php endif; ?>
                <?php if (!empty($submission['note'])): ?>
                    <p><strong>Student note:</strong> <?= nl2br(e($submission['note'])) ?></p>
                <?php endif; ?>
                <p>Submitted at <?= e($submission['submitted_at']) ?> • Status: <span class="status"><?= e(ucfirst($submission['status'])) ?></span></p>
            </section>

            <form class="upload-form" method="post" action="<?= BASE_URL ?>/classroom_action.php">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="save_grade">
                <input type="hidden" name="submission_id" value="<?= (int) $submission['id'] ?>">

                <h3>Rubric</h3>
                <?php if (!$rubric || empty($rubric['criteria'])): ?>
                    <p class="muted">No rubric was attached to this assignment. Add an overall score in the comment field below.</p>
                <?php else: ?>
                    <table class="rubric-table">
                        <thead><tr><th>Criterion</th><th>Points</th><th>Comment</th></tr></thead>
                        <tbody>
                            <?php foreach ($rubric['criteria'] as $c): ?>
                                <?php $existing = $gradeByCriterion[(int) $c['id']] ?? null; ?>
                                <tr>
                                    <td>
                                        <strong><?= e($c['label']) ?></strong>
                                        <small class="muted">Max <?= (float) $c['max_points'] ?> pts</small>
                                        <?php if (!empty($c['description'])): ?>
                                            <p class="muted"><?= e($c['description']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="hidden" name="criterion_id[]" value="<?= (int) $c['id'] ?>">
                                        <input type="number" name="points[]" min="0" max="<?= (float) $c['max_points'] ?>" step="0.5" value="<?= e((string)($existing['points'] ?? 0)) ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="comment[]" placeholder="Optional feedback" value="<?= e($existing['comment'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <label class="upload-form__wide">
                    Overall feedback (optional)
                    <textarea name="overall_feedback" rows="3" placeholder="Encouragement, summary, or instructions for resubmission"></textarea>
                </label>

                <button class="btn btn--primary btn--3d upload-form__wide" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save grade</button>
            </form>
        </article>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

<?php
$profileStudentId = Auth::studentId();
$profileUserId = (int) Auth::userId();
$profile = Database::connection()->prepare(
    'SELECT users.*, students.*, programs.name AS program_name
     FROM users LEFT JOIN students ON students.user_id = users.id
     LEFT JOIN programs ON programs.id = students.program_id
     WHERE users.id = :u LIMIT 1'
);
$profile->execute(['u' => $profileUserId]);
$p = $profile->fetch();
$att = $p ? Attendance::summary((int) $p['id']) : null;
$results = $p ? Gradebook::forStudent((int) $p['id']) : null;
?>
<section class="panel three-d-card">
    <div class="panel__head">
        <div>
            <h2><i class="fa-solid fa-user-graduate"></i> <?= e($p['full_name'] ?? 'Student') ?></h2>
            <p class="muted"><?= e($p['email'] ?? '') ?> • <?= e($p['student_number'] ?? '') ?></p>
        </div>
        <i class="fa-solid fa-circle-user" style="font-size:40px;color:var(--navy)"></i>
    </div>
</section>
<section class="panel three-d-card">
    <h3>Academic info</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-top:12px">
        <div><strong>Program</strong><br><span class="muted"><?= e($p['program_name'] ?? 'N/A') ?></span></div>
        <div><strong>Admission status</strong><br><span class="status"><?= e($p['admission_status'] ?? 'N/A') ?></span></div>
        <div><strong>Phone</strong><br><span class="muted"><?= e($p['phone'] ?? 'N/A') ?></span></div>
        <div><strong>Attendance rate</strong><br><span class="status"><?= ($att ? $att['percent'] : 0) ?>%</span></div>
    </div>
</section>
<section class="panel three-d-card">
    <div class="panel__head"><h3>Recent results</h3></div>
    <?php if (!$results || empty($results)): ?>
        <div class="empty-state"><i class="fa-solid fa-file-lines"></i><h3>No results</h3><p>Results will appear once assessments are graded.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Course</th><th>Assessment</th><th>Score</th><th>Grade</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($results, 0, 10) as $r): ?>
                        <tr>
                            <td><?= e($r['course_code'] ?? e($r['course_title'])) ?></td>
                            <td><?= e(ucfirst($r['assessment_type'])) ?></td>
                            <td><strong><?= number_format((float) $r['score'], 1) ?></strong></td>
                            <td><span class="status"><?= e($r['grade'] ?? '-') ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

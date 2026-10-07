<?php require __DIR__ . '/../layouts/header.php'; ?>
<style>
    .transcript {
        max-width: 900px; margin: 0 auto; background: #fff; border: 2px solid var(--navy); border-radius: 12px; overflow: hidden;
    }
    .transcript-header {
        background: var(--navy); color: #fff; text-align: center; padding: 28px 24px;
    }
    .transcript-header h1 { margin: 0; font-size: 20px; letter-spacing: 2px; text-transform: uppercase; }
    .transcript-header h2 { margin: 6px 0; font-size: 24px; }
    .transcript-header p { margin: 4px 0; font-size: 14px; opacity: .85; }
    .transcript-body { padding: 28px 24px; }
    .transcript-info { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; margin-bottom: 20px; font-size: 14px; }
    .transcript-info strong { display: inline-block; min-width: 100px; }
    .transcript-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
    .transcript-table th { background: #f0f4f8; padding: 10px 12px; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 2px solid var(--navy); }
    .transcript-table td { padding: 10px 12px; border-bottom: 1px solid var(--line); font-size: 14px; }
    .transcript-table tbody tr:hover { background: #f8fafc; }
    .transcript-footer { padding: 20px 24px; background: #f8fafc; border-top: 2px solid var(--navy); }
    .transcript-footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .transcript-footer strong { font-size: 14px; }
    .transcript-footer .label { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
    .transcript-footer .value { font-size: 18px; font-weight: 800; }
    .grade-scale { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; margin-top: 14px; font-size: 12px; }
    .grade-scale span { padding: 4px 8px; background: #f0f4f8; border-radius: 4px; text-align: center; }
</style>

<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-file-lines"></i> Student Transcript</span>
                <h1>Academic Record</h1>
            </div>
            <button class="btn btn--primary btn--3d" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        </div>

        <div class="transcript">
            <div class="transcript-header">
                <p>The Republic of Cameroon</p>
                <p>Cameroon Baptist Convention</p>
                <h2>BOYS' BRIGADE CAMEROON</h2>
                <h1>National Examination and Award Commission</h1>
                <p>La République du Cameroun • Convention baptiste du Cameroun • Brigade des garçons Cameroun</p>
                <p style="margin-top:8px;font-size:16px;font-weight:700">STUDENT TRANSCRIPT</p>
                <p>Academic Year 2024 / 2025</p>
            </div>
            <div class="transcript-body">
                <div class="transcript-info">
                    <div><strong>Student Name:</strong> <?= e($student['full_name']) ?></div>
                    <div><strong>Date of Birth:</strong> <?= e($student['date_of_birth'] ?? 'N/A') ?></div>
                    <div><strong>Student Number:</strong> <?= e($student['student_number'] ?? 'N/A') ?></div>
                    <div><strong>Place of Birth:</strong> <?= e($student['place_of_birth'] ?? 'N/A') ?></div>
                    <div><strong>Program:</strong> <?= e($student['program_name']) ?></div>
                    <div><strong>Organization:</strong> BOYS' BRIGADE CAMEROON</div>
                    <div style="grid-column:1/-1"><strong>Degree in View:</strong> DIPLOMA IN BB NATIONAL CERTIFICATION PROGRAM</div>
                </div>

                <table class="transcript-table">
                    <thead>
                        <tr><th style="width:60px">Code</th><th>Course Title</th><th style="width:60px">CV</th><th style="width:80px">Marks</th><th style="width:60px">Grade</th><th style="width:60px">GP</th><th style="width:80px">WP</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($courseMarks)): ?>
                            <?php foreach ($courseMarks as $cm): ?>
                                <tr>
                                    <td><?= e($cm['course_code'] ?? '') ?></td>
                                    <td><?= e($cm['course_title']) ?></td>
                                    <td style="text-align:center"><?= (int) ($cm['credit_value'] ?? 4) ?></td>
                                    <td style="text-align:center"><?= (float) $cm['total_score'] ?></td>
                                    <td style="text-align:center;font-weight:700"><?= e($cm['grade']) ?></td>
                                    <td style="text-align:center"><?= (float) $cm['grade_point'] ?></td>
                                    <td style="text-align:center"><?= (float) $cm['weighted_point'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center;padding:24px;color:var(--muted)">No approved marks available yet. Courses will appear here once results are published.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($courseMarks)): ?>
                    <tfoot style="font-weight:700">
                        <tr>
                            <td colspan="2" style="text-align:right">Total</td>
                            <td style="text-align:center"><?= (int) $totalCredits ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td style="text-align:center"><?= (float) $totalWeightedPoints ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            <div class="transcript-footer">
                <div class="transcript-footer-grid">
                    <div>
                        <div class="label">GPA</div>
                        <div class="value"><?= number_format($gpa, 2) ?></div>
                        <div class="label" style="margin-top:8px">CGPA (2024/2025)</div>
                        <div class="value"><?= number_format($gpa, 2) ?></div>
                    </div>
                    <div>
                        <div class="label">Degree Classification</div>
                        <div class="value"><?= degreeClass($gpa) ?></div>
                        <div class="label" style="margin-top:8px">Remark</div>
                        <div class="value"><?= $gpa >= 2.0 ? 'Passed' : 'Failed' ?></div>
                    </div>
                </div>
                <div class="grade-scale">
                    <span>A (80-100)</span>
                    <span>B+ (70-79)</span>
                    <span>B (60-69)</span>
                    <span>C+ (55-59)</span>
                    <span>C (50-54)</span>
                    <span>D+ (45-49)</span>
                    <span>D (40-44)</span>
                    <span>F (0-39)</span>
                </div>
                <?php if (empty($courseMarks)): ?>
                <div class="notice notice--success" style="margin-top:18px">
                    <i class="fa-solid fa-info-circle"></i> Your courses will appear on this transcript once lecturers submit your marks and the admin approves them. Check back after your assessments are complete.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

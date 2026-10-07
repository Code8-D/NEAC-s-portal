<section class="panel" id="students">
    <div class="panel__head">
        <h2>Student records</h2>
        <button class="icon-btn" type="button" title="Download records"><i class="fa-solid fa-download"></i></button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>ID</th>
                    <th>Program</th>
                    <th>Certification</th>
                    <th>Payment</th>
                    <th>Access</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr><td colspan="5">No students have signed up yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($students as $student):
                    $db = Database::connection();
                    $appSt = $db->prepare("SELECT status FROM certification_applications WHERE student_id = :sid LIMIT 1");
                    $appSt->execute(['sid' => $student['id']]);
                    $appStatus = $appSt->fetchColumn();
                    $paySt = $db->prepare("SELECT status FROM payment_verifications WHERE student_id = :sid ORDER BY id DESC LIMIT 1");
                    $paySt->execute(['sid' => $student['id']]);
                    $payStatus = $paySt->fetchColumn();
                ?>
                    <tr>
                        <td><?= e($student['name']) ?></td>
                        <td><?= e($student['id']) ?></td>
                        <td><?= e($student['program']) ?></td>
                        <td><span class="status" style="<?= $appStatus === 'approved' ? 'background:#ecfdf5;color:#065f46' : ($appStatus ? 'background:#fffbeb;color:#92400e' : 'background:#f3f4f6;color:#6b7280') ?>"><?= $appStatus ? e(ucfirst($appStatus)) : 'None' ?></span></td>
                        <td><span class="status" style="<?= $payStatus === 'verified' ? 'background:#ecfdf5;color:#065f46' : ($payStatus === 'pending' ? 'background:#fffbeb;color:#92400e' : 'background:#f3f4f6;color:#6b7280') ?>"><?= $payStatus ? e(ucfirst($payStatus)) : 'None' ?></span></td>
                        <td><span class="status" style="<?= $appStatus === 'approved' && $payStatus === 'verified' ? 'background:#ecfdf5;color:#065f46' : 'background:#f3f4f6;color:#6b7280' ?>"><?= ($appStatus === 'approved' && $payStatus === 'verified') ? 'Granted' : 'Locked' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

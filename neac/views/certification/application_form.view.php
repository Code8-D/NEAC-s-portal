<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-file-pen"></i> Certification Application</span>
                <h1><?= e($certBatch) ?> BB Officers Certification Exam</h1>
                <p style="color:var(--muted);margin:4px 0 0">Application Form</p>
            </div>
            <div>
                <?php if ($application && $application['status'] === 'submitted'): ?>
                    <span class="status" style="background:#fffbeb;color:#92400e;font-size:14px;padding:8px 16px"><i class="fa-solid fa-clock"></i> Submitted - Pending Review</span>
                <?php elseif ($application && $application['status'] === 'approved'): ?>
                    <span class="status" style="background:#ecfdf5;color:#065f46;font-size:14px;padding:8px 16px"><i class="fa-solid fa-check-circle"></i> Approved</span>
                <?php elseif ($application && $application['status'] === 'rejected'): ?>
                    <span class="status" style="background:#fef2f2;color:#991b1b;font-size:14px;padding:8px 16px"><i class="fa-solid fa-xmark-circle"></i> Rejected</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="notice notice--success"><?= e($success) ?></p><?php endif; ?>

        <?php if (!$application || $application['status'] === 'draft'): ?>
            <!-- Progress stepper -->
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin:4px 0 20px;padding:14px 16px;background:#f8fafc;border:1px solid var(--line);border-radius:12px">
                <?php foreach ($steps as $n => $label): ?>
                    <?php if ($application && $n !== $step): ?>
                        <a href="application.php?step=<?= $n ?>" style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;text-decoration:none;font-size:13px;font-weight:600;color:<?= $n < $step ? 'var(--gold)' : 'var(--muted)' ?>;border:1px solid <?= $n < $step ? 'var(--gold)' : 'var(--line)' ?>;background:#fff">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:<?= $n < $step ? 'var(--gold)' : '#eef2f7' ?>;color:<?= $n < $step ? '#102b4e' : 'var(--muted)' ?>;font-size:12px;font-weight:800"><?= $n < $step ? '<i class="fa-solid fa-check"></i>' : $n ?></span>
                            <?= e($label) ?>
                        </a>
                    <?php else: ?>
                        <span style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;font-size:13px;font-weight:700;color:#102b4e;border:1px solid var(--gold);background:#fff7e0;box-shadow:0 2px 6px rgba(217,163,33,.25)">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#102b4e;color:#fff;font-size:12px;font-weight:800"><?= $n ?></span>
                            <?= e($label) ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <form method="post" enctype="multipart/form-data" id="appForm">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="" id="formAction">
                <input type="hidden" name="step" value="<?= $step ?>">
                <?php require __DIR__ . "/certification_application/step{$step}.view.php"; ?>
                <div style="display:flex;justify-content:space-between;gap:12px;margin-top:16px;flex-wrap:wrap">
                    <div style="display:flex;gap:12px;flex-wrap:wrap">
                        <?php if ($step > 1): ?>
                            <a class="btn btn--ghost btn--3d" href="application.php?step=<?= $step - 1 ?>"><i class="fa-solid fa-arrow-left"></i> Back</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn--ghost btn--3d" onclick="document.getElementById('formAction').value='save'"><i class="fa-solid fa-save"></i> Save Draft</button>
                    </div>
                    <?php if ($step < 6): ?>
                        <button type="submit" class="btn btn--primary btn--3d" onclick="document.getElementById('formAction').value='next'">Continue <i class="fa-solid fa-arrow-right"></i></button>
                    <?php else: ?>
                        <button type="submit" class="btn btn--primary btn--3d" onclick="document.getElementById('formAction').value='submit';return confirm('Submit application? You will not be able to edit after submission.')"><i class="fa-solid fa-paper-plane"></i> Submit Application</button>
                    <?php endif; ?>
                </div>
            </form>
        <?php else: ?>
            <!-- View Only Mode -->
            <section class="panel three-d-card">
                <div class="panel__head"><i class="fa-solid fa-check-circle"></i><div><h2>Application <?= e(ucfirst($application['status'])) ?></h2></div></div>
                <p style="color:var(--muted)">Your application has been <?= e($application['status']) ?>. You can no longer make changes.</p>
                <?php if ($application['status'] === 'approved'): ?>
                    <div class="notice notice--success" style="margin-top:12px">
                        <i class="fa-solid fa-check-circle"></i> Your application has been approved!
                        <a href="payment.php" class="btn btn--primary btn--3d" style="margin-left:12px"><i class="fa-solid fa-credit-card"></i> Proceed to Payment</a>
                    </div>
                <?php elseif ($application['status'] === 'rejected' && $application['admin_notes']): ?>
                    <div class="notice notice--error" style="margin-top:12px"><i class="fa-solid fa-xmark-circle"></i> Reason: <?= e($application['admin_notes']) ?></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php if ($step === 5 && (!$application || $application['status'] === 'draft')): ?>
<script>
let trainingIdx = <?= count($trainings) ?: 1 ?>;
let serviceIdx = <?= count($services) ?: 1 ?>;
function addTraining() {
    const html = `<div class="training-row" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr 1fr;gap:8px;margin-bottom:8px;padding:8px;background:#f8fafc;border-radius:6px">
        <label style="font-size:12px">Seminar/Training<input type="text" name="trainings[${trainingIdx}][seminar_name]" style="font-size:13px"></label>
        <label style="font-size:12px">Duration<input type="text" name="trainings[${trainingIdx}][duration]" style="font-size:13px"></label>
        <label style="font-size:12px">Certificate<input type="text" name="trainings[${trainingIdx}][certificate_obtained]" style="font-size:13px"></label>
        <label style="font-size:12px">Signatory<input type="text" name="trainings[${trainingIdx}][signatory]" style="font-size:13px"></label>
        <label style="font-size:12px">Venue/Level<input type="text" name="trainings[${trainingIdx}][venue_level]" style="font-size:13px"></label>
        <label style="font-size:12px">Date<input type="text" name="trainings[${trainingIdx}][training_date]" style="font-size:13px"></label>
        <button type="button" class="icon-btn" onclick="this.parentElement.remove()" style="align-self:end"><i class="fa-solid fa-trash"></i></button>
    </div>`;
    document.getElementById('trainings-container').insertAdjacentHTML('beforeend', html);
    trainingIdx++;
}
function addService() {
    const html = `<div class="service-row" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;margin-bottom:8px;padding:8px;background:#f8fafc;border-radius:6px">
        <label style="font-size:12px">Role<input type="text" name="services[${serviceIdx}][role]" style="font-size:13px"></label>
        <label style="font-size:12px">Level<input type="text" name="services[${serviceIdx}][level]" style="font-size:13px"></label>
        <label style="font-size:12px">Date<input type="text" name="services[${serviceIdx}][service_date]" style="font-size:13px"></label>
        <label style="font-size:12px">Duration<input type="text" name="services[${serviceIdx}][duration]" style="font-size:13px"></label>
        <button type="button" class="icon-btn" onclick="this.parentElement.remove()" style="align-self:end"><i class="fa-solid fa-trash"></i></button>
    </div>`;
    document.getElementById('services-container').insertAdjacentHTML('beforeend', html);
    serviceIdx++;
}
</script>
<?php endif; ?>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

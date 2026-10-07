<?php
$certStatus = getCertStatus();
$applied = $certStatus['applied'];
$paid = $certStatus['paid'];
$pendingVerification = $applied && !$paid && $certStatus['payment_status'] === 'pending';
require __DIR__ . '/../layouts/header.php';
?>
<main class="auth-page join-page">
    <section class="join-card three-d-card">
        <div class="three-d-layer three-d-layer--back"></div>
        <div class="join-card__banner">
            <span class="join-card__banner-icon"><i class="fa-solid fa-lock"></i></span>
            <h1>Certification Required</h1>
            <p>Classrooms are reserved for certified candidates. Complete the steps below to unlock full access to courses, classwork, and grades.</p>
        </div>
        <div class="join-card__body">
            <div style="display:grid;gap:12px">
                <!-- Step 1: Application -->
                <div style="display:flex;align-items:center;gap:14px;padding:16px;border-radius:12px;background:<?= $applied ? '#ecfdf5' : '#f8fafc' ?>;border:1px solid <?= $applied ? '#a7f3d0' : 'var(--line)' ?>">
                    <i class="fa-solid fa-file-pen" style="font-size:22px;color:<?= $applied ? '#065f46' : 'var(--muted)' ?>"></i>
                    <div style="flex:1">
                        <strong>1. Submit your application</strong>
                        <small style="display:block;color:var(--muted)"><?= e($certBatch) ?> BB Officers Certification Application Form</small>
                    </div>
                    <?php if ($applied): ?>
                        <span class="status" style="background:#ecfdf5;color:#065f46"><i class="fa-solid fa-check"></i> Submitted</span>
                    <?php else: ?>
                        <a class="btn btn--primary btn--3d" href="application.php" style="font-size:12px;min-height:32px;padding:0 14px;white-space:nowrap"><i class="fa-solid fa-file-pen"></i> Apply Now</a>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Payment -->
                <div style="display:flex;align-items:center;gap:14px;padding:16px;border-radius:12px;background:<?= $paid ? '#ecfdf5' : '#f8fafc' ?>;border:1px solid <?= $paid ? '#a7f3d0' : 'var(--line)' ?>">
                    <i class="fa-solid fa-credit-card" style="font-size:22px;color:<?= $paid ? '#065f46' : 'var(--muted)' ?>"></i>
                    <div style="flex:1">
                        <strong>2. Pay the exam fee</strong>
                        <small style="display:block;color:var(--muted)"><?= EXAM_FEE ?> <?= EXAM_FEE_CURRENCY ?> via Mobile Money to <?= MOMO_NUMBER ?> (<?= MOMO_ACCOUNT_NAME ?>)</small>
                    </div>
                    <?php if ($paid): ?>
                        <span class="status" style="background:#ecfdf5;color:#065f46"><i class="fa-solid fa-check"></i> Verified</span>
                    <?php elseif ($pendingVerification): ?>
                        <span class="status" style="background:#fffbeb;color:#92400e"><i class="fa-solid fa-clock"></i> Reviewing</span>
                    <?php else: ?>
                        <a class="btn btn--primary btn--3d" href="payment.php" style="font-size:12px;min-height:32px;padding:0 14px;white-space:nowrap"><i class="fa-solid fa-credit-card"></i> Pay Now</a>
                    <?php endif; ?>
                </div>

                <!-- Step 3: Recommendations -->
                <div style="display:flex;align-items:center;gap:14px;padding:16px;border-radius:12px;background:#f8fafc;border:1px solid var(--line)">
                    <i class="fa-solid fa-file-signature" style="font-size:22px;color:var(--muted)"></i>
                    <div style="flex:1">
                        <strong>3. Upload recommendations</strong>
                        <small style="display:block;color:var(--muted)">Recommendation forms from your leaders (<?= (int) $certStatus['recommendations_uploaded'] ?> uploaded)</small>
                    </div>
                    <a class="btn btn--ghost btn--3d" href="recommendation.php" style="font-size:12px;min-height:32px;padding:0 14px;white-space:nowrap"><i class="fa-solid fa-arrow-up-from-bracket"></i> Manage</a>
                </div>
            </div>

            <?php if ($pendingVerification): ?>
                <div class="notice notice--success" style="margin-top:16px">
                    <i class="fa-solid fa-clock"></i> Your payment is being reviewed by the admin. Classroom access unlocks as soon as it is verified.
                </div>
            <?php elseif ($applied && !$paid): ?>
                <p class="join-note" style="margin-top:16px"><i class="fa-solid fa-circle-info"></i> Once your payment is verified, classrooms unlock automatically.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

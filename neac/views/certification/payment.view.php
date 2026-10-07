<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-credit-card"></i> Payment</span>
                <h1>Certification Exam Fee Payment</h1>
                <p style="color:var(--muted);margin:4px 0 0">Pay your <?= e($certBatch) ?> BB Officers Certification exam fee</p>
            </div>
            <?php if ($status['paid']): ?>
                <span class="status" style="background:#ecfdf5;color:#065f46;font-size:14px;padding:8px 16px"><i class="fa-solid fa-check-circle"></i> Payment Verified</span>
            <?php endif; ?>
        </div>

        <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="notice notice--success"><?= e($success) ?></p><?php endif; ?>

        <section class="panel three-d-card" style="border-left:4px solid var(--gold)">
            <div class="panel__head">
                <i class="fa-solid fa-circle-info"></i>
                <div><h2>Exam Fee: <?= EXAM_FEE ?> <?= EXAM_FEE_CURRENCY ?></h2><p>Send the fee via Mobile Money and upload your transaction screenshot</p></div>
            </div>
        </section>

        <?php if (!$status['paid']): ?>

        <section class="panel three-d-card">
            <div class="panel__head">
                <i class="fa-solid fa-mobile-screen"></i>
                <div><h2>Mobile Money Transfer</h2><p>Send to the NEAC official number and upload the screenshot</p></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:14px">
                <div style="padding:18px;background:#f8fafc;border-radius:8px;border:1px solid var(--line)">
                    <h3 style="margin:0 0 12px;font-size:16px">Payment Details</h3>
                    <p style="margin:0 0 8px"><strong>Amount:</strong> <?= EXAM_FEE ?> <?= EXAM_FEE_CURRENCY ?></p>
                    <p style="margin:0 0 8px"><strong>Number:</strong> <?= MOMO_NUMBER ?></p>
                    <p style="margin:0 0 8px"><strong>Account Name:</strong> <?= MOMO_ACCOUNT_NAME ?></p>
                </div>
                <div style="padding:18px;background:#f8fafc;border-radius:8px;border:1px solid var(--line)">
                    <h3 style="margin:0 0 12px;font-size:16px">Steps</h3>
                    <ol style="margin:0;padding-left:18px;line-height:1.8">
                        <li>Dial <strong>*126#</strong> on your Mobile Money line</li>
                        <li>Select <strong>Transfer Money</strong></li>
                        <li>Enter: <strong><?= MOMO_NUMBER ?></strong></li>
                        <li>Amount: <strong><?= EXAM_FEE ?> <?= EXAM_FEE_CURRENCY ?></strong></li>
                        <li>Enter your PIN to confirm</li>
                        <li>Take a <strong>screenshot</strong> of the confirmation</li>
                        <li>Upload it below</li>
                    </ol>
                </div>
            </div>
        </section>

        <section class="panel three-d-card">
            <div class="panel__head">
                <i class="fa-solid fa-upload"></i>
                <div><h2>Upload Payment Screenshot</h2></div>
            </div>
            <form method="post" enctype="multipart/form-data" class="upload-form" style="margin-top:16px">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="upload_payment">
                <label>Sender's Full Name <input type="text" name="sender_name" placeholder="Enter the sender name" required></label>
                <label>Sender's Phone <input type="tel" name="sender_phone" placeholder="+237 6XX XXX XXX" required></label>
                <label style="grid-column:1/-1">Transaction ID <input type="text" name="transaction_id" placeholder="Transaction ID from the confirmation message" required></label>
                <div class="file-drop" style="grid-column:1/-1">
                    <i class="fa-solid fa-camera"></i>
                    <span>Upload Screenshot</span>
                    <small>JPG, PNG, GIF, WebP, PDF (max 10MB)</small>
                    <input type="file" name="screenshot" accept="image/*,.pdf" required>
                </div>
                <button class="btn btn--primary btn--3d" type="submit" style="grid-column:1/-1"><i class="fa-solid fa-cloud-upload"></i> Submit for Verification</button>
            </form>
        </section>
        <?php endif; ?>

        <?php if (!empty($payments)): ?>
        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-clock-rotate-left"></i> Payment History</h2></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= (int) $p['amount'] ?> <?= e($p['currency']) ?></td>
                                <td><span class="status">Manual</span></td>
                                <td style="font-size:12px"><?= e($p['transaction_id'] ?? 'N/A') ?></td>
                                <td><?= e($p['created_at']) ?></td>
                                <td><span class="status" style="<?= $p['status'] === 'verified' ? 'background:#ecfdf5;color:#065f46' : ($p['status'] === 'rejected' ? 'background:#fef2f2;color:#991b1b' : 'background:#fffbeb;color:#92400e') ?>"><?= e(ucfirst($p['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

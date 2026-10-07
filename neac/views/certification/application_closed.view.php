<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <?php if ($periodState === 'not_open'): ?>
            <section class="panel three-d-card">
                <div class="empty-state" style="padding:56px 24px">
                    <i class="fa-solid fa-hourglass-half" style="color:#d9a321"></i>
                    <h3>Applications not open yet</h3>
                    <p>Applications for the <?= e($certBatch) ?> BB Officers Certification Exam will open on <strong><?= e(date('j F Y', strtotime($certOpens))) ?></strong>. Please check back then.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="panel three-d-card" style="border-color:rgba(217,163,33,.5)">
                <div class="empty-state" style="padding:56px 24px">
                    <i class="fa-solid fa-pause" style="color:#991b1b"></i>
                    <h3>The <?= e($certBatch) ?> certification program is already going on</h3>
                    <p>New applications are currently closed. If you believe this is an error, please contact the commission at <strong>bbcameroonnationalexam@gmail.com</strong>.</p>
                    <div class="hero__actions" style="justify-content:center;margin-top:20px">
                        <a class="btn btn--primary btn--3d" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to dashboard</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>

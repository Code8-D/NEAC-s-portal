<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Ranking';
require __DIR__ . '/../views/layouts/header.php';
?>

<main>
    <section class="page-hero page-hero--modules">
        <div class="hero__media hero__media--primary" aria-hidden="true"></div>
        <div class="hero__shade" aria-hidden="true"></div>
        <span class="eyebrow">Ranks &amp; rankings</span>
        <h1>Promotion files, ranks, and awards in one place.</h1>
        <p>Track candidates, defence results, and rank recommendations across the Brigade with full traceability.</p>
    </section>

    <section class="section section--tight">
        <div class="empty-state" style="max-width:640px;margin:0 auto;border:1px dashed var(--line);border-radius:12px;padding:56px 28px">
            <i class="fa-solid fa-trophy"></i>
            <h3>Information not received yet</h3>
            <p>Ranking information has not been received yet. Please check back later for official rankings, promotion files, and award updates.</p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../views/layouts/footer.php'; ?>
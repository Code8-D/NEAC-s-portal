<?php require __DIR__ . '/../layouts/header.php'; ?>

<main class="auth-page join-page">
    <section class="join-card three-d-card">
        <div class="three-d-layer three-d-layer--back"></div>
        <div class="join-card__banner">
            <span class="join-card__banner-icon"><i class="fa-solid fa-keyboard"></i></span>
            <h1>Join a classroom</h1>
            <p>Ask your teacher for a 7-character class code, then enter it below. Codes look like <code>H7K2Q9X</code>.</p>
        </div>
        <div class="join-card__body">
            <?php if (!empty($error)): ?>
                <p class="alert"><?= e($error) ?></p>
            <?php endif; ?>
            <form class="join-code-field" method="get" action="<?= BASE_URL ?>/classroom.php">
                <input type="hidden" name="action" value="join">
                <label for="join-code">Class code</label>
                <input class="join-code-input" type="text" id="join-code" name="code" placeholder="H7K2Q9X" maxlength="12" autocomplete="off" spellcheck="false" required autofocus>
                <button class="btn btn--primary btn--3d join-btn" type="submit"><i class="fa-solid fa-arrow-right-to-bracket"></i> Join classroom</button>
            </form>
            <div class="join-help">
                <div><i class="fa-solid fa-comment-dots"></i><small>Ask your teacher<br>for the class code</small></div>
                <div><i class="fa-solid fa-keyboard"></i><small>Type the code<br>exactly as given</small></div>
                <div><i class="fa-solid fa-check"></i><small>You're in — access the<br>stream, classwork &amp; grades</small></div>
            </div>
            <p class="join-note">You can also click a shared class link your teacher sent you.</p>
        </div>
    </section>
</main>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

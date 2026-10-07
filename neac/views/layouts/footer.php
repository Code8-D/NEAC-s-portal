<footer class="footer">
    <div>
        <strong>NEAC Integrated Web Platform</strong>
        <span>Secure learning, records, assessments, and academic operations.</span>
    </div>
    <div class="footer__actions">
        <a href="<?= BASE_URL ?>/login.php"><i class="fa-solid fa-lock"></i> Portal access</a>
        <a href="<?= BASE_URL ?>/dashboard.php?section=classrooms"><i class="fa-solid fa-school"></i> Classrooms</a>
    </div>
</footer>
<?php
$__neacScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$__neacIsClassroom = (strpos($__neacScript, 'classroom') === 0);
?>
<?php if ($__neacIsClassroom): ?>
<script src="<?= BASE_URL ?>/assets/js/classroom.js"></script>
<?php endif; ?>
</body>
</html>

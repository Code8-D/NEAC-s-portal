<div class="classwork-layout">
    <div class="classwork-main">
        <div class="classwork-toolbar">
            <h2><i class="fa-solid fa-folder-tree"></i> Classwork</h2>
            <?php if ($isTeacher): ?>
                <a class="btn btn--primary btn--3d" href="classroom.php?action=manage&amp;id=<?= (int) $classroom['id'] ?>&amp;section=classwork"><i class="fa-solid fa-plus"></i> Create</a>
            <?php endif; ?>
        </div>

        <?php if (empty($classwork)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <h3>No classwork yet</h3>
                <p>Assignments, materials, and questions posted by the teacher will appear here.</p>
            </div>
        <?php else: ?>
            <div class="classwork-list">
                <?php foreach ($classwork as $item): ?>
                    <a class="classwork-card three-d-card" href="classroom.php?action=classwork&amp;work_id=<?= (int) $item['id'] ?>">
                        <div class="classwork-card__icon classwork-card__icon--<?= e($item['work_type']) ?>">
                            <i class="fa-solid <?= $item['work_type'] === 'assignment' ? 'fa-file-pen' : ($item['work_type'] === 'question' ? 'fa-circle-question' : 'fa-file-lines') ?>"></i>
                        </div>
                        <div class="classwork-card__body">
                            <h3><?= e($item['title']) ?></h3>
                            <p><?= e(mb_substr($item['description'] ?? '', 0, 120)) ?></p>
                            <small>
                                <i class="fa-solid fa-user-tie"></i> <?= e($item['author_name']) ?> •
                                <i class="fa-regular fa-clock"></i> <?= e($item['created_at']) ?>
                                <?php if ($item['work_type'] === 'assignment' && !empty($item['due_at'])): ?>
                                    • <i class="fa-regular fa-calendar"></i> Due <?= e($item['due_at']) ?>
                                <?php endif; ?>
                                <?php if ($item['work_type'] === 'assignment'): ?>
                                    • <i class="fa-solid fa-paper-plane"></i> <?= (int) $item['submission_count'] ?> submissions
                                <?php endif; ?>
                            </small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

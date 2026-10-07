<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Security.php';
require_once __DIR__ . '/../app/models/Notification.php';

if (!Auth::check()) { header('Location: signup.php'); exit; }
$userId = (int) Auth::userId();

$action = $_GET['action'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_read' && ($id = (int) ($_GET['id'] ?? 0))) {
    Security::requireCsrf();
    Notification::markRead($id, $userId);
    header('Location: notifications.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'mark_all_read') {
    Security::requireCsrf();
    Notification::markAllRead($userId);
    header('Location: notifications.php');
    exit;
}

$pageTitle = 'Notifications';
$hideTopbar = true;
require __DIR__ . '/../views/layouts/header.php';

$notifications = Notification::forUser($userId, 50);
$unread = Notification::unreadCount($userId);
?>

<main class="app-shell">
    <?php require __DIR__ . '/../views/layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div><span class="eyebrow">Updates</span><h1>Notifications <?php if ($unread > 0): ?><span class="status" style="background:var(--navy);color:#fff"><?= (int) $unread ?> new</span><?php endif; ?></h1></div>
            <div class="workspace__head-actions">
                <?php if ($unread > 0): ?>
                    <form method="post" action="notifications.php?action=mark_all_read" style="display:inline">
                        <?= Security::csrfField() ?>
                        <button class="btn btn--ghost btn--3d" type="submit"><i class="fa-solid fa-check-double"></i> Mark all read</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <section class="panel three-d-card">
            <?php if (empty($notifications)): ?>
                <div class="empty-state"><i class="fa-solid fa-bell"></i><h3>No notifications</h3><p>You're all caught up!</p></div>
            <?php else: ?>
                <div class="classwork-list">
                    <?php foreach ($notifications as $n): ?>
                        <div class="stream-card" style="<?= $n['read_at'] ? '' : 'border-left:3px solid var(--navy);background:#f8faff' ?>">
                            <header>
                                <i class="fa-solid <?= $n['read_at'] ? 'fa-bell' : 'fa-bell' ?>" style="color:<?= $n['read_at'] ? 'var(--muted)' : 'var(--navy)' ?>"></i>
                                <strong><?= e($n['title']) ?></strong>
                                <small><?= e($n['created_at']) ?></small>
                                <?php if (!$n['read_at']): ?>
                                    <form method="post" action="notifications.php?action=mark_read&id=<?= (int) $n['id'] ?>" style="margin-left:auto;display:inline">
                                        <?= Security::csrfField() ?>
                                        <button type="submit" style="font-size:12px;color:var(--navy);background:none;border:none;cursor:pointer;padding:0">Mark read</button>
                                    </form>
                                <?php endif; ?>
                            </header>
                            <p><?= e($n['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require __DIR__ . '/../views/layouts/footer.php'; ?>

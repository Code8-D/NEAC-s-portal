<?php
$homePosts = HomePost::all();
$postTypes = [
    'announcement' => ['Announcement', 'fa-bullhorn'],
    'event' => ['Event', 'fa-calendar-day'],
    'status' => ['Status update', 'fa-circle-info'],
];
?>
<?php if (!empty($_GET['saved'])): ?>
    <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Home page post saved.</p>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <p class="notice notice--success"><i class="fa-solid fa-circle-check"></i> Home page post deleted.</p>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <p class="notice notice--error"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($_GET['error']) ?></p>
<?php endif; ?>

<section class="panel three-d-card">
    <div class="panel__head">
        <h2><i class="fa-solid fa-bullhorn"></i> New home page post</h2>
    </div>
    <p class="muted">Published posts appear on the public home page for visitors.</p>
    <form method="post" action="<?= BASE_URL ?>/dashboard.php?section=home" class="upload-form" id="hp-create-form" enctype="multipart/form-data" style="margin-top:14px">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div class="upload-form__wide">
            <label for="hp-type">Post type</label>
            <select id="hp-type" name="type" onchange="document.getElementById('hp-event-row').style.display = this.value === 'event' ? 'grid' : 'none'">
                <option value="announcement">Announcement</option>
                <option value="event">Event</option>
                <option value="status">Status update</option>
            </select>
        </div>
        <div class="upload-form__wide">
            <label for="hp-title">Title</label>
            <input id="hp-title" name="title" type="text" maxlength="160" required placeholder="e.g. Certification exam results released">
        </div>
        <div class="upload-form__wide" id="hp-event-row" style="display:none">
            <label for="hp-date">Event date</label>
            <input id="hp-date" name="event_date" type="date">
        </div>
        <div class="upload-form__wide">
            <label for="hp-body">Message</label>
            <textarea id="hp-body" name="body" rows="4" required placeholder="Write the announcement, event details, or status update..."></textarea>
        </div>
        <div class="upload-form__wide">
            <label for="hp-image">Image (optional, JPG/PNG/GIF/WebP up to 5 MB)</label>
            <input id="hp-image" name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <button class="btn btn--primary btn--3d upload-form__wide" type="submit"><i class="fa-solid fa-paper-plane"></i> Publish post</button>
    </form>
</section>

<section class="panel three-d-card" style="margin-top:16px">
    <div class="panel__head">
        <h2><i class="fa-solid fa-list"></i> Published posts</h2>
    </div>
    <?php if (empty($homePosts)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-bullhorn"></i>
            <h3>No posts yet</h3>
            <p>Use the form above to add the first announcement, event, or status update.</p>
        </div>
    <?php else: ?>
        <div class="classwork-list">
            <?php foreach ($homePosts as $hp): ?>
                <details class="stream-card" style="margin-bottom:10px">
                    <summary style="cursor:pointer;list-style:none;display:flex;align-items:center;gap:12px">
                        <span class="status" style="background:<?= $hp['post_type'] === 'event' ? 'rgba(217,163,33,.15)' : ($hp['post_type'] === 'status' ? 'rgba(16,43,78,.08)' : 'rgba(217,163,33,.15)') ?>;color:var(--ink);flex-shrink:0">
                            <i class="fa-solid <?= $postTypes[$hp['post_type']][1] ?>"></i> <?= e($postTypes[$hp['post_type']][0]) ?>
                        </span>
                        <strong style="flex:1;min-width:0"><?= e($hp['title']) ?></strong>
                        <small class="muted" style="flex-shrink:0"><?= e($hp['is_published'] ? 'Live' : 'Hidden') ?> • <?= e(date('j M Y', strtotime($hp['created_at']))) ?></small>
                    </summary>
                    <div style="padding:12px 0 0">
                        <p style="margin:0 0 8px;white-space:pre-line"><?= nl2br(e($hp['body'])) ?></p>
                        <?php if ($hp['post_type'] === 'event' && $hp['event_date']): ?>
                            <p class="muted" style="font-size:13px"><i class="fa-solid fa-calendar-day"></i> <?= e(date('j F Y', strtotime($hp['event_date']))) ?></p>
                        <?php endif; ?>
                        <?php if ($hp['image_path']): ?>
                            <div style="display:flex;align-items:center;gap:10px;margin:8px 0">
                                <img src="<?= BASE_URL ?>/post_image.php?id=<?= (int) $hp['id'] ?>" alt="" style="max-width:180px;max-height:120px;border-radius:8px;border:1px solid var(--line)">
                            </div>
                        <?php endif; ?>
                        <form method="post" action="<?= BASE_URL ?>/dashboard.php?section=home" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= (int) $hp['id'] ?>">
                            <select name="type" style="width:auto;padding:6px 8px;height:auto;font-size:13px">
                                <?php foreach ($postTypes as $pk => $pv): ?>
                                    <option value="<?= $pk ?>" <?= $hp['post_type'] === $pk ? 'selected' : '' ?>><?= $pv[0] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input name="title" type="text" maxlength="160" value="<?= e($hp['title']) ?>" style="flex:1;min-width:160px;padding:6px 8px;border:1px solid var(--line);border-radius:6px;font:inherit">
                            <?php if ($hp['post_type'] === 'event'): ?>
                                <input name="event_date" type="date" value="<?= e($hp['event_date'] ?? '') ?>" style="width:auto;padding:6px 8px;height:auto;border:1px solid var(--line);border-radius:6px;font:inherit">
                            <?php endif; ?>
                            <textarea name="body" rows="2" style="flex:1 1 100%;padding:6px 8px;border:1px solid var(--line);border-radius:6px;font:inherit"><?= e($hp['body']) ?></textarea>
                            <input name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp" style="font-size:13px;max-width:220px">
                            <?php if ($hp['image_path']): ?>
                                <label style="font-size:13px;font-weight:600;display:flex;gap:6px;align-items:center">
                                    <input type="checkbox" name="remove_image" value="1"> Remove current image
                                </label>
                            <?php endif; ?>
                            <label style="font-size:13px;font-weight:600;display:flex;gap:6px;align-items:center">
                                <input type="checkbox" name="is_published" <?= $hp['is_published'] ? 'checked' : '' ?>> Show on home page
                            </label>
                            <button class="btn btn--ghost btn--3d" type="submit" style="font-size:13px;min-height:32px;padding:0 12px">Save</button>
                        </form>
                        <form method="post" action="<?= BASE_URL ?>/dashboard.php?section=home" onsubmit="return confirm('Delete this post?')" style="margin-top:6px">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $hp['id'] ?>">
                            <button class="btn" type="submit" style="font-size:13px;min-height:32px;padding:0 12px;background:#fef2f2;color:#991b1b"><i class="fa-solid fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

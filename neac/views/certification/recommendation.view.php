<?php require __DIR__ . '/../layouts/header.php'; ?>
<main class="app-shell">
    <?php require __DIR__ . '/../layouts/sidebar.php'; ?>
    <section class="workspace">
        <div class="workspace__head">
            <div>
                <span class="eyebrow"><i class="fa-solid fa-file-signature"></i> Recommendations</span>
                <h1>Recommendation Forms</h1>
                <p style="color:var(--muted);margin:4px 0 0">Download, fill, and upload recommendation forms from your pastor, company, association, and field</p>
            </div>
        </div>

        <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="notice notice--success"><?= e($success) ?></p><?php endif; ?>

        <div class="notice notice--success" style="margin-bottom:18px">
            <i class="fa-solid fa-circle-info"></i> 
            Download the recommendation form(s), have them filled by the appropriate persons (Pastor, Company Captain/Chaplain, Association Major, Field Colonel), then upload them here.
        </div>

        <!-- Download Section -->
        <section class="panel three-d-card">
            <div class="panel__head">
                <i class="fa-solid fa-download"></i>
                <div><h2>Download Recommendation Forms</h2><p>Click to download the PDF form</p></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-top:14px">
                <a href="<?= BASE_URL ?>/assets/2026%20%20RECOMENDATION%20FORMS%20For%20BB%20NATIONAL%20CERTIFICATION%20EXAM%20(1).pdf" target="_blank" class="btn btn--ghost btn--3d" style="justify-content:start;padding:16px;min-height:auto">
                    <i class="fa-solid fa-file-pdf" style="color:#dc2626;font-size:24px"></i>
                    <span style="display:grid;gap:2px;text-align:left">
                        <strong>Full Recommendation Form (PDF)</strong>
                        <small style="font-weight:400;color:var(--muted)">Contains Pastor, Company, Association & Field sections</small>
                    </span>
                </a>
            </div>
        </section>

        <!-- Upload Section -->
        <section class="panel three-d-card">
            <div class="panel__head">
                <i class="fa-solid fa-upload"></i>
                <div><h2>Upload Filled Recommendation</h2></div>
            </div>
            <form method="post" enctype="multipart/form-data" class="upload-form" style="margin-top:16px">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="upload_recommendation">

                <label>
                    Recommendation Type
                    <select name="recommendation_type" required>
                        <option value="">-- Select --</option>
                        <option value="pastor">Pastor's Recommendation</option>
                        <option value="company">Company Recommendation (Captain & Chaplain)</option>
                        <option value="association">Association Recommendation (Major & Chaplain)</option>
                        <option value="field">Field Recommendation (Colonel & Certified Officer)</option>
                    </select>
                </label>
                <div class="file-drop">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    <span>Upload filled form (PDF, JPG, PNG)</span>
                    <small>Max 10MB</small>
                    <input type="file" name="recommendation_file" accept=".pdf,image/*" required>
                </div>
                <button class="btn btn--primary btn--3d" type="submit"><i class="fa-solid fa-cloud-upload"></i> Upload Recommendation</button>
            </form>
        </section>

        <!-- Uploaded Recommendations -->
        <section class="panel three-d-card">
            <div class="panel__head"><h2><i class="fa-solid fa-file-circle-check"></i> Uploaded Recommendations</h2></div>
            <?php if (empty($recommendations)): ?>
                <div class="empty-state"><i class="fa-solid fa-file-circle-check"></i><h3>No recommendations uploaded yet</h3><p>Download the form, have it filled, then upload it here.</p></div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Type</th><th>File</th><th>Uploaded</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($recommendations as $r): ?>
                                <tr>
                                    <td><strong><?= e(ucfirst($r['recommendation_type'])) ?></strong></td>
                                    <td><a href="<?= BASE_URL ?>/download.php?path=<?= e($r['file_path']) ?>" target="_blank"><i class="fa-solid fa-file"></i> <?= e($r['original_name']) ?></a></td>
                                    <td><?= e($r['uploaded_at']) ?></td>
                                    <td>
                                        <span class="status" style="<?= $r['status'] === 'approved' ? 'background:#ecfdf5;color:#065f46' : ($r['status'] === 'rejected' ? 'background:#fef2f2;color:#991b1b' : 'background:#fffbeb;color:#92400e') ?>">
                                            <?= e(ucfirst($r['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this recommendation?')">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="action" value="delete_recommendation">
                                            <input type="hidden" name="rec_id" value="<?= (int) $r['id'] ?>">
                                            <button class="icon-btn" type="submit"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

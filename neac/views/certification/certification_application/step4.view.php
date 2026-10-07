            <!-- Step 4: Section E Christian Experience -->
            <section class="panel three-d-card">
                <div class="panel__head">
                    <i class="fa-solid fa-cross"></i>
                    <div><h2>E. Christian Experience</h2></div>
                </div>
                <label style="display:flex;align-items:center;gap:12px;font-weight:400;cursor:pointer;margin-bottom:12px">
                    <input type="checkbox" name="is_christian" value="1" <?= !empty($application['is_christian']) ? 'checked' : '' ?>> <strong>I am a Christian</strong>
                </label>
                <label>What makes you a Christian?</label>
                <textarea name="what_makes_christian" style="min-height:60px"><?= e($application['what_makes_christian'] ?? '') ?></textarea>
                <div class="wide-form" style="margin-top:12px">
                    <label>Date of Baptism <input type="date" name="date_of_baptism" value="<?= e($application['date_of_baptism'] ?? '') ?>"></label>
                    <label>Baptized by <input type="text" name="baptized_by" value="<?= e($application['baptized_by'] ?? '') ?>"></label>
                </div>
                <label style="margin-top:12px">Salvation Testimony</label>
                <textarea name="salvation_testimony" style="min-height:120px"><?= e($application['salvation_testimony'] ?? '') ?></textarea>
            </section>

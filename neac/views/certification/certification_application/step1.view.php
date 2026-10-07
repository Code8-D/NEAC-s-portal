            <!-- Step 1: Section A Demographic Information -->
            <section class="panel three-d-card">
                <div class="panel__head">
                    <i class="fa-solid fa-user"></i>
                    <div><h2>A. Demographic Information</h2><p>Personal details of the candidate</p></div>
                </div>
                <div class="wide-form" style="margin-top:16px">
                    <label>Full Name <input type="text" name="full_name" value="<?= e($application['full_name'] ?? '') ?>" required></label>
                    <label>Date of Birth <input type="date" name="date_of_birth" value="<?= e($application['date_of_birth'] ?? '') ?>"></label>
                    <label>Place of Birth <input type="text" name="place_of_birth" value="<?= e($application['place_of_birth'] ?? '') ?>"></label>
                    <label>Residence <input type="text" name="residence" value="<?= e($application['residence'] ?? '') ?>"></label>
                    <label>Name of Church <input type="text" name="church_name" value="<?= e($application['church_name'] ?? '') ?>"></label>
                    <label>Academic Qualification <input type="text" name="academic_qualification" value="<?= e($application['academic_qualification'] ?? '') ?>"></label>
                    <label>Rank in BB <input type="text" name="rank_in_bb" value="<?= e($application['rank_in_bb'] ?? '') ?>"></label>
                    <label>Telephone <input type="tel" name="phone" value="<?= e($application['phone'] ?? '') ?>" required></label>
                    <label>WhatsApp <input type="tel" name="whatsapp" value="<?= e($application['whatsapp'] ?? '') ?>"></label>
                    <label style="grid-column:1/-1">Email <input type="email" name="email" value="<?= e($application['email'] ?? '') ?>" required></label>
                </div>
                <div style="margin-top:16px;padding:16px;border:1px solid var(--line);border-radius:8px;background:#f8fafc">
                    <label style="font-weight:700;margin-bottom:8px">Study Option (select ONE - selecting both disqualifies the application)</label>
                    <div style="display:flex;gap:24px;margin-top:8px">
                        <label style="font-weight:400;display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="radio" name="study_option" value="online" <?= ($application['study_option'] ?? '') === 'online' ? 'checked' : '' ?>>
                            <i class="fa-solid fa-laptop"></i> Online - I will attend online classes starting June 30, 2026 (6 months evening hours)
                        </label>
                        <label style="font-weight:400;display:flex;align-items:center;gap:8px;cursor:pointer">
                            <input type="radio" name="study_option" value="onsite" <?= ($application['study_option'] ?? '') === 'onsite' ? 'checked' : '' ?>>
                            <i class="fa-solid fa-building"></i> Onsite - I will attend in a Battalion curriculum pilot center
                        </label>
                    </div>
                </div>
            </section>

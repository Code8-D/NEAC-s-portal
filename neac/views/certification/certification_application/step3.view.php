            <!-- Step 3: Section D BB Experience -->
            <section class="panel three-d-card">
                <div class="panel__head">
                    <i class="fa-solid fa-medal"></i>
                    <div><h2>D. Candidate's Experience in BB</h2></div>
                </div>
                <div class="wide-form" style="margin-top:16px">
                    <label>Age you joined BB <input type="text" name="age_joined_bb" value="<?= e($application['age_joined_bb'] ?? '') ?>"></label>
                    <label>Name of first BB Company <input type="text" name="first_bb_company" value="<?= e($application['first_bb_company'] ?? '') ?>"></label>
                    <label>Year you first joined <input type="text" name="year_joined_bb" value="<?= e($application['year_joined_bb'] ?? '') ?>"></label>
                    <label>First Captain/Trainer <input type="text" name="first_captain_name" value="<?= e($application['first_captain_name'] ?? '') ?>"></label>
                    <label>National BB Camps Attended <input type="text" name="national_camps_attended" value="<?= e($application['national_camps_attended'] ?? '') ?>"></label>
                </div>
                <label style="margin-top:12px">How did you join the BB?</label>
                <textarea name="how_joined_bb" style="min-height:80px"><?= e($application['how_joined_bb'] ?? '') ?></textarea>
                <label style="margin-top:12px">How did you become an officer?</label>
                <textarea name="how_became_officer" style="min-height:80px"><?= e($application['how_became_officer'] ?? '') ?></textarea>
                <label style="margin-top:12px">Current role in your company, association, and field</label>
                <textarea name="current_role" style="min-height:80px"><?= e($application['current_role'] ?? '') ?></textarea>
                <label style="margin-top:12px">Your pledge for BB</label>
                <textarea name="pledge_for_bb" style="min-height:60px"><?= e($application['pledge_for_bb'] ?? '') ?></textarea>
            </section>

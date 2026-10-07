            <!-- Step 5: Section F Education & Service -->
            <section class="panel three-d-card">
                <div class="panel__head">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <div><h2>F. Education & Service in the Brigade</h2></div>
                </div>
                <div class="wide-form">
                    <label>Educational Level <input type="text" name="educational_level" value="<?= e($application['educational_level'] ?? '') ?>"></label>
                    <label>Profession <input type="text" name="profession" value="<?= e($application['profession'] ?? '') ?>"></label>
                </div>

                <h3 style="margin:18px 0 8px">BB Trainings Attended</h3>
                <div id="trainings-container">
                    <?php $trainingList = !empty($trainings) ? $trainings : [['seminar_name'=>'','duration'=>'','certificate_obtained'=>'','signatory'=>'','venue_level'=>'','training_date'=>'']]; ?>
                    <?php foreach ($trainingList as $i => $t): ?>
                    <div class="training-row" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr 1fr 1fr;gap:8px;margin-bottom:8px;padding:8px;background:#f8fafc;border-radius:6px">
                        <label style="font-size:12px">Seminar/Training<input type="text" name="trainings[<?= $i ?>][seminar_name]" value="<?= e($t['seminar_name'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Duration<input type="text" name="trainings[<?= $i ?>][duration]" value="<?= e($t['duration'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Certificate<input type="text" name="trainings[<?= $i ?>][certificate_obtained]" value="<?= e($t['certificate_obtained'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Signatory<input type="text" name="trainings[<?= $i ?>][signatory]" value="<?= e($t['signatory'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Venue/Level<input type="text" name="trainings[<?= $i ?>][venue_level]" value="<?= e($t['venue_level'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Date<input type="text" name="trainings[<?= $i ?>][training_date]" value="<?= e($t['training_date'] ?? '') ?>" style="font-size:13px"></label>
                        <?php if ($i > 0): ?><button type="button" class="icon-btn" onclick="this.parentElement.remove()" style="align-self:end"><i class="fa-solid fa-trash"></i></button><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--ghost btn--3d" onclick="addTraining()" style="margin-top:8px"><i class="fa-solid fa-plus"></i> Add Training</button>

                <h3 style="margin:18px 0 8px">Services (Roles held)</h3>
                <div id="services-container">
                    <?php $serviceList = !empty($services) ? $services : [['role'=>'','level'=>'','service_date'=>'','duration'=>'']]; ?>
                    <?php foreach ($serviceList as $i => $s): ?>
                    <div class="service-row" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px;margin-bottom:8px;padding:8px;background:#f8fafc;border-radius:6px">
                        <label style="font-size:12px">Role<input type="text" name="services[<?= $i ?>][role]" value="<?= e($s['role'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Level<input type="text" name="services[<?= $i ?>][level]" value="<?= e($s['level'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Date<input type="text" name="services[<?= $i ?>][service_date]" value="<?= e($s['service_date'] ?? '') ?>" style="font-size:13px"></label>
                        <label style="font-size:12px">Duration<input type="text" name="services[<?= $i ?>][duration]" value="<?= e($s['duration'] ?? '') ?>" style="font-size:13px"></label>
                        <?php if ($i > 0): ?><button type="button" class="icon-btn" onclick="this.parentElement.remove()" style="align-self:end"><i class="fa-solid fa-trash"></i></button><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--ghost btn--3d" onclick="addService()" style="margin-top:8px"><i class="fa-solid fa-plus"></i> Add Service</button>
            </section>

            <!-- Step 6: Attestation & Signature -->
            <section class="panel three-d-card">
                <div class="panel__head">
                    <i class="fa-solid fa-pen"></i>
                    <div><h2>Attestation & Signature</h2><p>I attest that all information given here is true.</p></div>
                </div>
                <label>Type your full name as signature <input type="text" name="signature" value="<?= e($application['signature'] ?? '') ?>" placeholder="Type your full name" required></label>
            </section>

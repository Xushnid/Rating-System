<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="editIshlanmaForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="editItemModalLabel">Ishlanmani tahrirlash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="controller" value="ishlanma">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                    <input type="hidden" name="id" id="edit_ishlanma_id">
                    
                    <!-- Alert container for validation messages -->
                    <div id="editFormAlerts" class="mb-3" style="display: none;"></div>
                    
                    <div id="editSectionSpecificFields" class="mb-3">
                        <!-- Dynamic fields will be inserted here -->
                    </div>

                    <div class="edit-common-fields" style="display: none;">
                        <hr>
                        <h6 class="text-primary mb-3">Umumiy ma'lumotlar</h6>
                        <div class="mb-3">
                            <label for="edit_article_name" class="form-label">Maqola/Ishlanma nomi <span class="text-danger">*</span></label>
                            <input type="text" id="edit_article_name" name="article_name" class="form-control" maxlength="255">
                            <div class="invalid-feedback">Bu maydon to'ldirilishi shart.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_publish_date" class="form-label">Nashr sanasi <span class="text-danger">*</span></label>
                            <input type="date" id="edit_publish_date" name="publish_date" class="form-control">
                            <div id="edit_date-error" class="validation-error text-danger small"></div>
                            <div class="invalid-feedback">Sana kiritilishi shart.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_url" class="form-label">Manba Link (ixtiyoriy)</label>
                            <input type="url" id="edit_url" name="url" class="form-control" maxlength="255" placeholder="https://...">
                            <div class="invalid-feedback">To'g'ri URL formatini kiriting.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_authors_count" class="form-label">Mualliflar soni <span class="text-danger">*</span></label>
                                <input type="number" id="edit_authors_count" name="authors_count" class="form-control" min="1" value="1">
                                <div class="invalid-feedback">Mualliflar soni 1 dan kam bo'lmasligi kerak.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_share" class="form-label">Ulush (avtomatik)</label>
                                <input type="number" id="edit_share" name="share" class="form-control" step="0.001" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="edit-file-field" style="display: none;">
                        <hr>
                        <h6 class="text-primary mb-3">Fayl yuklash</h6>
                        <div class="mb-3">
                            <label for="edit_file" class="form-label">Yangi fayl yuklash (PDF, ixtiyoriy, avvalgisi o'chiriladi)</label>
                            <input type="file" id="edit_file" name="file" class="form-control" accept="application/pdf">
                            <div id="edit_file-error" class="validation-error text-danger small"></div>
                            <div class="form-text">Faqat PDF formatidagi fayllar qabul qilinadi. Maksimal hajm: 1.5MB</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" id="editSubmitBtn" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Saqlash
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Debug script for edit modal
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editIshlanmaForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            console.log('Form validity:', form.checkValidity());
            
            // Log all form data
            const formData = new FormData(form);
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            // Check required fields
            const requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');
            console.log('Required fields check:');
            requiredFields.forEach(field => {
                console.log(`${field.name}: "${field.value}" - Valid: ${field.checkValidity()}`);
                if (!field.checkValidity()) {
                    console.log(`Invalid reason: ${field.validationMessage}`);
                }
            });
        });
    }
});
</script>
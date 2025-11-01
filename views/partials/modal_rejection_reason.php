<?php
// Rad etish sababini kiritish uchun modal oyna
?>
<div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-labelledby="rejectionReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="rejectionReasonForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectionReasonModalLabel">Rad Etish Sababi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="rejection_submission_id" name="id">
                    
                    <div class="mb-3">
                        <label for="rejection_reason_text" class="form-label">Iltimos, ishlanmani rad etish sababini aniq ko'rsating:</label>
                        <textarea class="form-control" id="rejection_reason_text" name="reason" rows="4" required></textarea>
                        <div class="invalid-feedback">
                            Rad etish sababini kiritish majburiy.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" class="btn btn-danger">Rad Etishni Tasdiqlash</button>
                </div>
            </form>
        </div>
    </div>
</div>
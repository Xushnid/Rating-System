<?php
// Bu fayl header.php ichidan chaqiriladi, shuning uchun $currentUser o'zgaruvchisi mavjud.
?>
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editProfileForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">Profil Sozlamalari</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="controller" value="user">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">

           
                    <div id="profileAlertContainer"></div>

                    <div class="mb-3">
                        <label for="profile_username" class="form-label">Login (foydalanuvchi nomi)</label>
                        <input type="text" id="profile_username" name="username" class="form-control" value="<?= htmlspecialchars($currentUser['username']); ?>" required>
                    </div>
                    <hr>
                    <p class="text-muted">Parolni o'zgartirish uchun quyidagi maydonlarni to'ldiring. Aks holda bo'sh qoldiring.</p>
                    <div class="mb-3">
                        <label for="profile_password" class="form-label">Yangi parol</label>
                        <input type="password" id="profile_password" name="password" class="form-control" minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="profile_password_confirm" class="form-label">Yangi parolni tasdiqlang</label>
                        <input type="password" id="profile_password_confirm" name="password_confirm" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" id="saveProfileBtn" class="btn btn-primary">Saqlash</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Kafedra O'qituvchilarini Boshqarish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container py-4">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-people"></i> Kafedra O'qituvchilarini Boshqarish</h2>
        </div>
        <div id="alertContainer"></div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0 fw-bold">Yangi O'qituvchi Qo'shish</h5></div>
            <div class="card-body">
                <form id="addUserForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                     <div class="mb-3">
                        <label for="full_name" class="form-label">To'liq ism (F.I.Sh) *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Login *</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Parol *</label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Parolni tasdiqlang *</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                        </div>
                    </div>
                    <p class="text-muted small">Eslatma: Yangi foydalanuvchi avtomatik ravishda sizning kafedrangizga 'user' (o'qituvchi) roli bilan qo'shiladi.</p>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-person-check-fill me-2"></i>Qo'shish
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0 fw-bold">Kafedra O'qituvchilari Ro'yxati</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>To'liq ism (F.I.Sh)</th>
                                <th>Login</th>
                                <th class="text-end">Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($users)): ?>
                                <tr><td colspan="4" class="text-center p-4">Kafedrada o'qituvchilar topilmadi.</td></tr>
                            <?php else: ?>
                                <?php $user_index = 0; ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= ++$user_index; ?></td>
                                        <td>
                                            <?= htmlspecialchars($user['full_name']); ?>
                                            <?php if ($user['id'] === $currentUser['id']): ?>
                                                <span class="badge bg-success ms-2">Siz (Kafedra Admini)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($user['username']); ?></td>
                                        <td class="text-end">
                                            <?php if ($user['id'] !== $currentUser['id']): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary edit-user-btn" data-user='<?= htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>'><i class="bi bi-pencil"></i></button>
                                                    <button class="btn btn-outline-danger delete-user-btn" data-id="<?= $user['id']; ?>"><i class="bi bi-trash"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editUserForm" novalidate>
                    <div class="modal-header"><h5 class="modal-title">O'qituvchi ma'lumotlarini tahrirlash</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_user_id">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                        <div class="mb-3">
                            <label for="edit_full_name" class="form-label">To'liq ism *</label>
                            <input type="text" id="edit_full_name" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Login *</label>
                            <input type="text" id="edit_username" name="username" class="form-control" required>
                        </div>
                        <hr><p class="text-muted small">Parolni o'zgartirish uchun to'ldiring.</p>
                        <div class="mb-3"><label for="edit_password" class="form-label">Yangi parol</label><input type="password" id="edit_password" name="password" class="form-control" minlength="6"></div>
                        <div class="mb-3"><label for="edit_password_confirm" class="form-label">Yangi parolni tasdiqlang</label><input type="password" id="edit_password_confirm" name="password_confirm" class="form-control"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                        <button type="submit" class="btn btn-primary">Saqlash</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const addUserForm = document.getElementById('addUserForm');
        const editUserForm = document.getElementById('editUserForm');
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        const mainCsrfToken = document.getElementById('csrf-token-container').dataset.token;

        // --- O'qituvchi Qo'shish ---
        addUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!addUserForm.checkValidity()) {
                e.stopPropagation();
                addUserForm.classList.add('was-validated');
                return;
            }
            const password = addUserForm.querySelector('#password').value;
            const confirmPassword = addUserForm.querySelector('#password_confirm').value;
            if (password !== confirmPassword) {
                showAlert('Kiritilgan parollar bir-biriga mos kelmadi!', 'danger');
                return;
            }
            
            const submitBtn = addUserForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Qo'shilmoqda...`;

            try {
                const formData = new FormData(addUserForm);
                const response = await fetch(`<?= BASE_URL ?>ajax/department-admin/add-user`, { method: 'POST', body: formData });
                const result = await response.json();
                showAlert(result.message, result.success ? 'success' : 'danger');
                if (result.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                showAlert('Server bilan bog\'lanishda xatolik.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });

        // --- Tahrirlash oynasini ochish ---
        document.querySelectorAll('.edit-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const user = JSON.parse(btn.dataset.user);
                editUserForm.reset();
                editUserForm.classList.remove('was-validated');
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_full_name').value = user.full_name;
                document.getElementById('edit_username').value = user.username;
                editModal.show();
            });
        });

        // --- Tahrirlash formasini yuborish ---
        editUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!editUserForm.checkValidity()) {
                e.stopPropagation();
                editUserForm.classList.add('was-validated');
                return;
            }
            const password = editUserForm.querySelector('#edit_password').value;
            const confirmPassword = editUserForm.querySelector('#edit_password_confirm').value;
            if (password && password !== confirmPassword) {
                showAlert('Kiritilgan yangi parollar bir-biriga mos kelmadi!', 'danger');
                return;
            }

            const submitBtn = editUserForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saqlanmoqda...`;

            try {
                const formData = new FormData(editUserForm);
                const response = await fetch(`<?= BASE_URL ?>ajax/department-admin/update-user`, { method: 'POST', body: formData });
                const result = await response.json();
                showAlert(result.message, result.success ? 'success' : 'danger');
                if (result.success) {
                     setTimeout(() => location.reload(), 1500);
                }
            } catch (error) {
                showAlert('Server bilan bog\'lanishda xatolik.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });

        // --- O'chirish tugmasi ---
        document.querySelectorAll('.delete-user-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (confirm('Ushbu o\'qituvchini o\'chirishga ishonchingiz komilmi?')) {
                    const formData = new FormData();
                    formData.append('csrf_token', mainCsrfToken);
                    formData.append('id', btn.dataset.id);

                    try {
                        const response = await fetch(`<?= BASE_URL ?>ajax/department-admin/delete-user`, { method: 'POST', body: formData });
                        const result = await response.json();
                        showAlert(result.message, result.success ? 'success' : 'danger');
                        if (result.success) {
                            setTimeout(() => location.reload(), 1500);
                        }
                    } catch (error) {
                        showAlert('Server bilan bog\'lanishda xatolik.', 'danger');
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
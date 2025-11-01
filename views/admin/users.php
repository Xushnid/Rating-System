<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Foydalanuvchilarni Boshqarish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container py-4">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-people"></i>Foydalanuvchilarni Boshqarish</h2>
        </div>
        <div id="alertContainer"></div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0 fw-bold">Yangi foydalanuvchi qo'shish</h5></div>
            <div class="card-body">
                <form id="addUserForm" class="row g-3" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                    <div class="col-md-6 col-lg-4">
                        <label for="full_name" class="form-label">To'liq ism *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="username" class="form-label">Login *</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="role" class="form-label">Rol *</label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="user" selected>O'qituvchi</option>
                            <option value="departmentadmin">Kafedra Admini</option>
                            <option value="facultyadmin">Fakultet Admini</option>
                            <option value="superadmin">Super Admin</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="add_faculty_id" class="form-label">Fakultet</label>
                        <select id="add_faculty_id" name="faculty_id" class="form-select">
                            <option value="">-- Tanlanmagan --</option>
                            <?php foreach($faculties as $faculty): ?>
                                <option value="<?= $faculty['id'] ?>"><?= htmlspecialchars($faculty['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="add_department_id" class="form-label">Kafedra</label>
                        <select id="add_department_id" name="department_id" class="form-select" disabled>
                            <option value="">-- Avval fakultetni tanlang --</option>
                        </select>
                    </div>
                     <div class="col-md-6 col-lg-4">
                        <label for="password" class="form-label">Parol *</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label for="password_confirm" class="form-label">Parolni tasdiqlang *</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="6">
                    </div>
                    <div class="col-12 d-grid">
                        <button type="submit" class="btn btn-primary">Qo'shish</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
             <div class="card-header"><h5 class="card-title mb-0 fw-bold">Mavjud Foydalanuvchilar</h5></div>
            
            <div class="card-body border-bottom">
                <form method="GET" action="/admin/users" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="filter_faculty" class="form-label">Fakultet bo'yicha filtrlash</label>
                        <select id="filter_faculty" name="faculty_id" class="form-select">
                            <option value="">Barchasi</option>
                            <?php foreach($faculties as $faculty): ?>
                                <option value="<?= $faculty['id'] ?>" <?= ($filters['faculty_id'] ?? 0) == $faculty['id'] ? 'selected' : '' ?>><?= htmlspecialchars($faculty['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_department" class="form-label">Kafedra bo'yicha</label>
                        <select id="filter_department" name="department_id" class="form-select" <?= !($filters['faculty_id'] ?? 0) ? 'disabled' : '' ?>>
                            <option value="">Barchasi</option>
                            <?php foreach($departments as $department): ?>
                                <option value="<?= $department['id'] ?>" class="department-option" data-faculty-id="<?= $department['faculty_id'] ?>" style="display:none;" <?= ($filters['department_id'] ?? 0) == $department['id'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                         <label for="filter_role" class="form-label">Rol bo'yicha</label>
                         <select id="filter_role" name="role" class="form-select">
                            <option value="">Barchasi</option>
                            <option value="superadmin" <?= ($filters['role'] ?? '') == 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                            <option value="facultyadmin" <?= ($filters['role'] ?? '') == 'facultyadmin' ? 'selected' : '' ?>>Fakultet Admini</option>
                            <option value="departmentadmin" <?= ($filters['role'] ?? '') == 'departmentadmin' ? 'selected' : '' ?>>Kafedra Admini</option>
                            <option value="user" <?= ($filters['role'] ?? '') == 'user' ? 'selected' : '' ?>>O'qituvchi</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filtrlash</button>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>To'liq ism / Login</th>
                                <th>Rol</th>
                                <th>Fakultet</th>
                                <th>Kafedra</th>
                                <th class="text-end">Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
    <?php if(empty($users)): ?>
        <tr><td colspan="6" class="text-center p-4">Filtrga mos foydalanuvchilar topilmadi.</td></tr>
    <?php else: ?>
        <?php foreach ($users as $index => $user): ?>
            <tr>
                <td><?= $index + 1; ?></td>
                <td>
                    <?= htmlspecialchars($user['full_name']); ?><br>
                    <small class="text-muted"><?= htmlspecialchars($user['username']); ?></small>
                </td>
                <td>
                    <?php $roleInfo = translate_role($user['role']); ?>
                    <span class="badge fs-6 fw-normal <?= htmlspecialchars($roleInfo['class']) ?>">
                        <?= htmlspecialchars($roleInfo['name']) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($user['faculty_name'] ?? 'N/A'); ?></td>
                <td><?= htmlspecialchars($user['department_name'] ?? 'N/A'); ?></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary edit-user-btn" data-user='<?= htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>'><i class="bi bi-pencil"></i></button>
                       <?php if ($currentUser['id'] != $user['id'] && $user['id'] != 1): ?>
                            <button class="btn btn-outline-danger delete-user-btn" data-id="<?= $user['id']; ?>"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </div>
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
                    <div class="modal-header"><h5 class="modal-title">Foydalanuvchini tahrirlash</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_user_id">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                        
                        <div class="mb-3"><label for="edit_full_name" class="form-label">To'liq ism *</label><input type="text" id="edit_full_name" name="full_name" class="form-control" required></div>
                        <div class="mb-3"><label for="edit_username" class="form-label">Login *</label><input type="text" id="edit_username" name="username" class="form-control" required></div>
                        <div class="mb-3"><label for="edit_role" class="form-label">Rol *</label><select id="edit_role" name="role" class="form-select" required><option value="user">O'qituvchi</option><option value="departmentadmin">Kafedra Admini</option><option value="facultyadmin">Fakultet Admini</option><option value="superadmin">Super Admin</option></select></div>
                        <div class="mb-3"><label for="edit_faculty_id" class="form-label">Fakultet</label><select id="edit_faculty_id" name="faculty_id" class="form-select"><option value="">-- Tanlanmagan --</option><?php foreach($faculties as $faculty): ?><option value="<?= $faculty['id'] ?>"><?= htmlspecialchars($faculty['name']) ?></option><?php endforeach; ?></select></div>
                        <div class="mb-3"><label for="edit_department_id" class="form-label">Kafedra</label><select id="edit_department_id" name="department_id" class="form-select" disabled><option value="">-- Avval fakultetni tanlang --</option></select></div>
                        <hr><p class="text-muted">Parolni o'zgartirish uchun to'ldiring.</p>
                        <div class="mb-3"><label for="edit_password" class="form-label">Yangi parol</label><input type="password" id="edit_password" name="password" class="form-control" minlength="6"></div>
                        <div class="mb-3"><label for="edit_password_confirm" class="form-label">Yangi parolni tasdiqlang</label><input type="password" id="edit_password_confirm" name="password_confirm" class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button><button type="submit" class="btn btn-primary">Saqlash</button></div>
                </form>
            </div>
        </div>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const addUserForm = document.getElementById('addUserForm');
        const editUserForm = document.getElementById('editUserForm');
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));

        const allDepartments = <?= json_encode($departments) ?>;

        function populateDepartments(facultyId, departmentSelect, selectedDepartmentId = null) {
            departmentSelect.innerHTML = '<option value="">-- Tanlang --</option>';
            departmentSelect.disabled = true;
            if (facultyId) {
                const filtered = allDepartments.filter(d => d.faculty_id == facultyId);
                if(filtered.length > 0) {
                    departmentSelect.disabled = false;
                    filtered.forEach(d => {
                        const option = new Option(d.name, d.id);
                        departmentSelect.add(option);
                    });
                } else {
                     departmentSelect.innerHTML = '<option value="">-- Bu fakultetda kafedra yo\'q --</option>';
                }
            }
            if(selectedDepartmentId) {
                departmentSelect.value = selectedDepartmentId;
            }
        }

        document.getElementById('add_faculty_id').addEventListener('change', (e) => {
            populateDepartments(e.target.value, document.getElementById('add_department_id'));
        });

        document.getElementById('edit_faculty_id').addEventListener('change', (e) => {
            populateDepartments(e.target.value, document.getElementById('edit_department_id'));
        });
        
        async function submitForm(url, formData) {
            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                if (!response.headers.get('content-type')?.includes('application/json')) {
                   throw new Error('Serverdan JSON javob kelmadi.');
                }
                const result = await response.json();
                showAlert(result.message, result.success ? 'success' : 'danger');
                if (result.success) {
                    setTimeout(() => { window.location.href = '/admin/users'; }, 1500);
                }
            } catch (error) {
                console.error("Formani yuborishda xatolik:", error);
                showAlert('Server bilan bog\'lanishda xatolik yuz berdi.', 'danger');
            }
        }

        addUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!addUserForm.checkValidity()) { e.stopPropagation(); addUserForm.classList.add('was-validated'); return; }
            const password = addUserForm.querySelector('#password').value;
            const confirmPassword = addUserForm.querySelector('#password_confirm').value;
            if (password !== confirmPassword) { showAlert('Kiritilgan parollar bir-biriga mos kelmadi!', 'danger'); return; }
            await submitForm('/ajax/admin/users/create', new FormData(addUserForm));
        });

        document.querySelectorAll('.edit-user-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const user = JSON.parse(btn.dataset.user);
                editUserForm.reset();
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_full_name').value = user.full_name;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_role').value = user.role;
                const facultySelect = document.getElementById('edit_faculty_id');
                facultySelect.value = user.faculty_id || '';
                populateDepartments(user.faculty_id, document.getElementById('edit_department_id'), user.department_id);
                editModal.show();
            });
        });

        editUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!editUserForm.checkValidity()) { e.stopPropagation(); editUserForm.classList.add('was-validated'); return; }
            const password = editUserForm.querySelector('#edit_password').value;
            const confirmPassword = editUserForm.querySelector('#edit_password_confirm').value;
            if (password || confirmPassword) { if (password !== confirmPassword) { showAlert('Kiritilgan yangi parollar bir-biriga mos kelmadi!', 'danger'); return; } }
            await submitForm('/ajax/admin/users/update', new FormData(editUserForm));
        });

        document.querySelectorAll('.delete-user-btn').forEach(btn => {
             btn.addEventListener('click', async () => {
                if (confirm('Ushbu foydalanuvchini o\'chirishga ishonchingiz komilmi?')) {
                    const formData = new FormData();
                    formData.append('csrf_token', addUserForm.querySelector('[name="csrf_token"]').value);
                    formData.append('id', btn.dataset.id);
                    await submitForm('/ajax/admin/users/delete', formData);
                }
            });
        });

        const facultyFilter = document.getElementById('filter_faculty');
        const departmentFilter = document.getElementById('filter_department');
        
        function updateDepartmentFilterForFilters() {
            const selectedFacultyId = facultyFilter.value;
            departmentFilter.disabled = !selectedFacultyId;
            
            departmentFilter.querySelectorAll('option').forEach(opt => {
                if (opt.value === "") return;
                if (opt.dataset.facultyId == selectedFacultyId) {
                    opt.style.display = 'block';
                } else {
                    opt.style.display = 'none';
                }
            });
        }
        
        facultyFilter.addEventListener('change', () => {
             departmentFilter.value = '';
             updateDepartmentFilterForFilters();
        });
        
        updateDepartmentFilterForFilters();
	 });
    </script>
</body>
</html>
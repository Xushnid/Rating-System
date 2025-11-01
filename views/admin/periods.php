<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Davrlarni Boshqarish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container py-4">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-calendar-range"></i> Davrlarni Boshqarish</h2>
        </div>
        <div id="alertContainer"></div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0 fw-bold">Yangi davr yaratish</h5></div>
            <div class="card-body">
                <form id="addForm" class="row g-3" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Davr nomi *</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Masalan: 2025-2026 o'quv yili" required>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Boshlanish sanasi *</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Tugash sanasi *</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" required>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary">Yaratish</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0 fw-bold">Mavjud Davrlar</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nomi</th>
                                <th>Boshlanish sanasi</th>
                                <th>Tugash sanasi</th>
                                <th>Holati</th>
                                <th class="text-end">Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($periods)): ?>
                                <tr><td colspan="5" class="text-center p-4">Davrlar topilmadi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($periods as $item): ?>
                                    <tr>
                                        <td class="fw-medium"><?= htmlspecialchars($item['name']); ?></td>
                                        <td><?= date('d.m.Y', strtotime($item['start_date'])); ?></td>
                                        <td><?= date('d.m.Y', strtotime($item['end_date'])); ?></td>
                                        <td>
                                            <?php if ($item['status'] === 'active'): ?>
                                                <span class="badge bg-success">Faol</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Yakunlangan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary edit-btn" data-item='<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>'><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-outline-danger delete-btn" data-id="<?= $item['id']; ?>"><i class="bi bi-trash"></i></button>
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

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editForm" novalidate>
                    <div class="modal-header"><h5 class="modal-title">Davrni tahrirlash</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Davr nomi *</label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="edit_start_date" class="form-label">Boshlanish sanasi *</label>
                                <input type="date" id="edit_start_date" name="start_date" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="edit_end_date" class="form-label">Tugash sanasi *</label>
                                <input type="date" id="edit_end_date" name="end_date" class="form-control" required>
                            </div>
                        </div>
                         <div class="mb-3">
                            <label for="edit_status" class="form-label">Holati *</label>
                            <select id="edit_status" name="status" class="form-select" required>
                                <option value="active">Faol</option>
                                <option value="closed">Yakunlangan</option>
                            </select>
                        </div>
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
    <script src="/assets/js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const addForm = document.getElementById('addForm');
        const editForm = document.getElementById('editForm');
        const editModal = new bootstrap.Modal(document.getElementById('editModal'));

        // Umumiy Formani yuborish funksiyasi
        async function handleFormSubmit(url, formData) {
            try {
                const response = await fetch(url, { method: 'POST', body: formData });
                const result = await response.json();
                showAlert(result.message, result.success ? 'success' : 'danger');
                if (result.success) setTimeout(() => location.reload(), 1500);
            } catch (error) {
                showAlert('Server bilan bog\'lanishda xatolik.', 'danger');
            }
        }

        // Yangi davr qo'shish
        addForm.addEventListener('submit', e => {
            e.preventDefault();
            if (!addForm.checkValidity()) {
                e.stopPropagation();
                addForm.classList.add('was-validated');
                return;
            }
            handleFormSubmit('/ajax/admin/periods/create', new FormData(addForm));
        });

        // Tahrirlash oynasini ochish
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = JSON.parse(btn.dataset.item);
                
                document.getElementById('edit_id').value = item.id;
                document.getElementById('edit_name').value = item.name;
                document.getElementById('edit_start_date').value = item.start_date;
                document.getElementById('edit_end_date').value = item.end_date;
                document.getElementById('edit_status').value = item.status;
                
                editModal.show();
            });
        });

        // Tahrirlangan davrni saqlash
        editForm.addEventListener('submit', e => {
            e.preventDefault();
            if (!editForm.checkValidity()) {
                e.stopPropagation();
                editForm.classList.add('was-validated');
                return;
            }
            handleFormSubmit('/ajax/admin/periods/update', new FormData(editForm));
            editModal.hide();
        });

        // Davrni o'chirish
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Ushbu davrni o\'chirishga ishonchingiz komilmi? Unga bog\'langan barcha rejalar ham o\'chib ketadi.')) {
                    const formData = new FormData();
                    formData.append('id', btn.dataset.id);
                    formData.append('csrf_token', addForm.querySelector('[name="csrf_token"]').value);
                    handleFormSubmit('/ajax/admin/periods/delete', formData);
                }
            });
        });
    });
    </script>
</body>
</html>
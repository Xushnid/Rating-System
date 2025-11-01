<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Fakultetlarni Boshqarish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container py-4">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-building"></i> Fakultetlarni Boshqarish</h2>
        </div>
        <div id="alertContainer"></div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="card-title mb-0 fw-bold">Yangi fakultet qo'shish</h5></div>
            <div class="card-body">
                <form id="addForm" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                    <div class="col-md-10">
                        <label for="name" class="form-label">Fakultet nomi *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-2 d-grid align-self-end">
                        <button type="submit" class="btn btn-primary">Qo'shish</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0 fw-bold">Mavjud Fakultetlar</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nomi</th>
                                <th>Yaratilgan sana</th>
                                <th class="text-end">Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($faculties)): ?>
                                <tr><td colspan="4" class="text-center p-4">Fakultetlar topilmadi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($faculties as $index => $item): ?>
                                    <tr>
                                        <td><?= $index + 1; ?></td>
                                        <td><?= htmlspecialchars($item['name']); ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($item['created_at'])); ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary edit-btn" data-id="<?= $item['id']; ?>" data-name="<?= htmlspecialchars($item['name']); ?>"><i class="bi bi-pencil"></i></button>
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
                <form id="editForm">
                    <div class="modal-header"><h5 class="modal-title">Fakultetni tahrirlash</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Fakultet nomi *</label>
                            <input type="text" id="edit_name" name="name" class="form-control" required>
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

        addForm.addEventListener('submit', e => {
            e.preventDefault();
            handleFormSubmit('/ajax/admin/faculties/create', new FormData(addForm));
        });

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                editModal.show();
            });
        });

        editForm.addEventListener('submit', e => {
            e.preventDefault();
            handleFormSubmit('/ajax/admin/faculties/update', new FormData(editForm));
            editModal.hide();
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Ushbu fakultetni o\'chirishga ishonchingiz komilmi? Unga biriktirilgan barcha kafedralar ham o\'chib ketadi.')) {
                    const formData = new FormData();
                    formData.append('id', btn.dataset.id);
                    formData.append('csrf_token', addForm.querySelector('[name="csrf_token"]').value);
                    handleFormSubmit('/ajax/admin/faculties/delete', formData);
                }
            });
        });
    });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Kafedra Adminlarini Boshqarish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container py-4">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-person-plus"></i> Kafedra Adminlarini Boshqarish</h2>
        </div>
        <div id="alertContainer"></div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Kafedra</th>
                                <th>Biriktirilgan Admin</th>
                                <th>Yangi Admin Tayinlash</th>
                                <th style="width: 1%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <tr data-department-id="<?= $dept['id'] ?>">
                                    <td class="fw-bold"><?= htmlspecialchars($dept['name']) ?></td>
                                    <td>
                                        <span class="current-admin-name">
                                            <?= htmlspecialchars($department_admins[$dept['id']]['full_name'] ?? 'Biriktirilmagan') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm user-select">
    <option value="0">-- Adminni olib tashlash --</option>
    <?php foreach ($users as $user): ?>
        <?php if ($user['id'] != $currentUser['id']): // FAKULTET ADMININING O'ZINI YASHIRAMIZ ?>
            <option value="<?= $user['id'] ?>" <?= ($department_admins[$dept['id']]['id'] ?? 0) == $user['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($user['full_name']) ?>
            </option>
        <?php endif; ?>
    <?php endforeach; ?>
</select>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success save-btn" disabled>Saqlash</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = document.getElementById('csrf-token-container').dataset.token;

        document.querySelectorAll('tr[data-department-id]').forEach(row => {
            const select = row.querySelector('.user-select');
            const saveBtn = row.querySelector('.save-btn');
            const initialUserId = select.value;

            select.addEventListener('change', () => {
                saveBtn.disabled = (select.value === initialUserId);
            });

            saveBtn.addEventListener('click', async () => {
                const departmentId = row.dataset.departmentId;
                const userId = select.value;

                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('department_id', departmentId);
                formData.append('user_id', userId);

                try {
                    const response = await fetch('/ajax/faculty-admin/assign-admin', { method: 'POST', body: formData });
                    const result = await response.json();
                    showAlert(result.message, result.success ? 'success' : 'danger');
                    if(result.success) {
                        setTimeout(() => location.reload(), 1500);
                    }
                } catch(e) {
                    showAlert('Server bilan bog\'lanishda xatolik.', 'danger');
                } finally {
                    saveBtn.innerHTML = 'Saqlash';
                }
            });
        });
    });
    </script>
</body>
</html>
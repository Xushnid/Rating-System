<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Rejalarni Boshqarish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.css" rel="stylesheet">

    <!-- Sizning umumiy uslublaringiz -->
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        /* Tom Select dropdown har doim eng tepada ko'rinsin */
        .ts-dropdown { z-index: 9999 !important; }

        /* Filtr kartasini yuqori layerga ko'taramiz va clippingni oldini olamiz */
        .filter-card { position: relative; z-index: 1080; overflow: visible; }
        .filter-card .card-body { overflow: visible; }

        /* Tom Select ichidagi control Bootstrap bilan uyg'un tursin */
        .ts-control { background-color: #fff; }
        .ts-wrapper.form-select .ts-control { border-radius: .375rem; }

        /* Kafedra kartalari uchun yumshoq stil */
        .department-card .form-control {
            background-color: #f8fafc;
            border: 1px solid #e5e7eb;
        }
        .department-card .form-control:focus {
            background-color: #fff;
            border-color: var(--bs-primary);
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
        }

        /* Yuqoridagi saqlash paneli ekranda qadalib turishi uchun (ixtiyoriy) */
        .toolbar-sticky {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: #fff;
            padding: .5rem 0;
        }
        .toolbar-sticky .card-header {
            background: transparent;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main class="container-fluid py-4 px-lg-5">
        <div class="page-header mb-3">
            <h2 class="h4 page-title"><i class="bi bi-bullseye"></i> Kafedralar uchun Rejalarni Boshqarish</h2>
        </div>

        <div id="alertContainer"></div>

        <!-- FILTR KARTASI -->
        <div class="card mb-4 filter-card">
            <div class="card-body">
                <form id="filterForm" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label for="faculty_filter" class="form-label">Fakultet bo'yicha filtrlash</label>
                        <select id="faculty_filter" name="faculty_id" placeholder="Fakultetni tanlang...">
                            <option value="">Barcha fakultetlar</option>
                            <?php foreach($faculties as $faculty): ?>
                                <option value="<?= $faculty['id'] ?>"
                                    <?= ($filters['faculty_id'] ?? 0) == $faculty['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($faculty['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-5">
                        <label for="department_filter" class="form-label">Kafedra bo'yicha filtrlash</label>
                        <select id="department_filter" name="department_id"
                                placeholder="Kafedrani tanlang..."
                                <?= !($filters['faculty_id'] ?? 0) ? 'disabled' : '' ?>>
                            <option value="">Barcha kafedralar</option>
                            <?php foreach($all_departments as $department): ?>
                                <?php
                                    // Faqat tanlangan fakultet kafedralarini ko'rsatish (tanlangan kafedrani saqlab qolish sharti bilan)
                                    if (($filters['faculty_id'] ?? 0) > 0 && $department['faculty_id'] != ($filters['faculty_id'] ?? 0)) {
                                        if (($filters['department_id'] ?? 0) != $department['id']) {
                                            continue;
                                        }
                                    }
                                ?>
                                <option
                                    value="<?= $department['id'] ?>"
                                    data-faculty-id="<?= $department['faculty_id'] ?>"
                                    <?= ($filters['department_id'] ?? 0) == $department['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($department['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($selectedPeriod) && $selectedPeriod['id'] > 0): ?>
            <form id="targetsForm" method="POST" action="/ajax/admin/targets/save">
                <!-- QADALUVCHI SAQLASH PANELI (ixtiyoriy, lekin foydali) -->
                <div class="toolbar-sticky">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-end">
                        <?php if (!empty($departments_to_show) && !$is_period_closed): ?>
                            <button type="submit" class="btn btn-primary btn-lg" form="targetsForm">
                                <i class="bi bi-save me-2"></i>Barcha O'zgarishlarni Saqlash
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">

                <?php if (empty($departments_to_show)): ?>
                    <div class="card card-body text-center p-5 text-muted">
                        <h5>Filtrga mos kafedralar topilmadi.</h5>
                    </div>
                <?php else: ?>
                    <?php foreach($departments_to_show as $department): ?>
                        <div class="card mb-4 shadow-sm department-card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 fw-bold">
                                    <?= htmlspecialchars($department['name']) ?>
                                    <small class="text-muted fw-normal">
                                        (<?= htmlspecialchars($department['faculty_name']) ?>)
                                    </small>
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    <?php
                                        $section_count = count($sections);
                                        $midpoint = ceil($section_count / 2);
                                        $sections_col1 = array_slice($sections, 0, $midpoint, true);
                                        $sections_col2 = array_slice($sections, $midpoint, null, true);
                                    ?>
                                    <div class="col-lg-6">
                                        <?php foreach($sections_col1 as $code => $section): ?>
                                            <div class="mb-3">
                                                <label for="target-<?= $department['id'] ?>-<?= $code ?>" class="form-label small">
                                                    <?= htmlspecialchars($code) ?> - <?= htmlspecialchars($section['name']) ?>
                                                </label>
                                                <input
                                                    type="number" step="0.01" min="0"
                                                    name="targets[<?= $department['id'] ?>][<?= $code ?>]"
                                                    id="target-<?= $department['id'] ?>-<?= $code ?>"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($targets[$department['id']][$code] ?? '0') ?>"
                                                    <?= $is_period_closed ? 'readonly' : '' ?>>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="col-lg-6">
                                        <?php foreach($sections_col2 as $code => $section): ?>
                                            <div class="mb-3">
                                                <label for="target-<?= $department['id'] ?>-<?= $code ?>" class="form-label small">
                                                    <?= htmlspecialchars($code) ?> - <?= htmlspecialchars($section['name']) ?>
                                                </label>
                                                <input
                                                    type="number" step="0.01" min="0"
                                                    name="targets[<?= $department['id'] ?>][<?= $code ?>]"
                                                    id="target-<?= $department['id'] ?>-<?= $code ?>"
                                                    class="form-control"
                                                    value="<?= htmlspecialchars($targets[$department['id']][$code] ?? '0') ?>"
                                                    <?= $is_period_closed ? 'readonly' : '' ?>>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="card card-body text-center p-5">
                <h5 class="text-muted">Rejalarni kiritish uchun, iltimos, yuqoridan faol davrni tanlang.</h5>
            </div>
        <?php endif; ?>
    </main>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>

    <script src="/assets/js/script.js" defer></script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Tom Select qismi o'zgarishsiz qoladi
        const filterForm = document.getElementById('filterForm');
        if(filterForm) {
            const tsFaculty = new TomSelect("#faculty_filter", {
                plugins: ['clear_button'],
                dropdownParent: 'body',
            });
            const tsDepartment = new TomSelect("#department_filter", {
                plugins: ['clear_button'],
                dropdownParent: 'body',
            });
            tsFaculty.on('change', (value) => {
                if (!value) {
                    tsDepartment.clear(true);
                    tsDepartment.disable();
                } else {
                    tsDepartment.enable();
                }
                filterForm.submit();
            });
            tsDepartment.on('change', () => {
                filterForm.submit();
            });
        }

        // ### YANGI MANTIQ BOSHLANISHI ###
        const targetsForm = document.getElementById('targetsForm');
        if (targetsForm) {
            // 1. Sahifa ochilganda barcha inputlarning boshlang'ich qiymatlarini saqlab olamiz
            const initialTargetValues = {};
            document.querySelectorAll('.form-control[name^="targets["]').forEach(input => {
                initialTargetValues[input.name] = input.value;
            });
            
            targetsForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const submitBtn = document.querySelector('button[type="submit"][form="targetsForm"]');
                if (!submitBtn) return;
                
                // 2. Forma yuborilganda faqat o'zgargan qiymatlarni aniqlaymiz
                const changedData = new FormData();
                changedData.append('csrf_token', targetsForm.querySelector('[name="csrf_token"]').value);
                let hasChanges = false;

                document.querySelectorAll('.form-control[name^="targets["]').forEach(input => {
                    if (input.value !== initialTargetValues[input.name]) {
                        changedData.append(input.name, input.value);
                        hasChanges = true;
                    }
                });
                
                // 3. Agar o'zgarish bo'lmasa, serverga so'rov yubormaymiz
                if (!hasChanges) {
                    showAlert("Hech narsa o'zgartirilmadi.", 'info');
                    return;
                }

                // 4. Faqat o'zgargan ma'lumotlarni serverga yuboramiz
                const originalBtnHTML = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Saqlanmoqda...`;

                try {
                    const response = await fetch(targetsForm.action, { 
                        method: 'POST', 
                        body: changedData // O'zgargan ma'lumotlar bilan yuboramiz
                    });
                    const result = await response.json();
                    showAlert(result.message || 'Saqlash yakunlandi.', result.success ? 'success' : 'danger');
                    
                    // Muvaffaqiyatli saqlangach, yangi qiymatlarni boshlang'ich sifatida saqlab qo'yamiz
                    if (result.success) {
                        document.querySelectorAll('.form-control[name^="targets["]').forEach(input => {
                            initialTargetValues[input.name] = input.value;
                        });
                    }
                } catch (err) {
                    showAlert('Server bilan bog\'lanishda xatolik.', 'danger');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHTML;
                }
            });
        }
    });
    </script>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Boshqaruv Paneli - SuperAdmin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container-fluid py-4 px-lg-5">

        <div class="d-md-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold">Boshqaruv Paneli</h1>
                <p class="text-muted mb-0">Tizimning umumiy holati va statistikasi.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <h5 class="mb-0 text-end">
                    <small class="text-muted fw-normal">Joriy davr:</small><br>
                    <span class="text-primary fw-bold"><?= htmlspecialchars($selectedPeriod['name'] ?? 'Tanlanmagan') ?></span>
                </h5>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-primary me-3"><i class="bi bi-journal-text"></i></div><div><h4 class="mb-0"><?= $globalStats['total'] ?? 0 ?></h4><p class="text-muted mb-0">Jami ishlanmalar</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-warning me-3"><i class="bi bi-clock-history"></i></div><div><h4 class="mb-0"><?= $globalStats['pending'] ?? 0 ?></h4><p class="text-muted mb-0">Kutilmoqda</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-success me-3"><i class="bi bi-check-circle-fill"></i></div><div><h4 class="mb-0"><?= $globalStats['approved'] ?? 0 ?></h4><p class="text-muted mb-0">Tasdiqlangan</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-danger me-3"><i class="bi bi-x-circle-fill"></i></div><div><h4 class="mb-0"><?= $globalStats['rejected'] ?? 0 ?></h4><p class="text-muted mb-0">Rad etilgan</p></div></div></div></div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><h5 class="card-title mb-0">Umumiy Reja va Bajarilish Holati</h5></div>
            <div class="card-body">
                <?php $isFilterActive = $filter_faculty_id > 0; ?>
                <div class="accordion" id="mainReportAccordion">
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingInstitute">
                            <button class="accordion-button <?= $isFilterActive ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInstitute" aria-expanded="<?= !$isFilterActive ? 'true' : 'false' ?>" aria-controls="collapseInstitute">
                                <i class="bi bi-bank me-2"></i> Umumiy Institut Hisoboti
                            </button>
                        </h2>
                        <div id="collapseInstitute" class="accordion-collapse collapse <?= !$isFilterActive ? 'show' : '' ?>" data-bs-parent="#mainReportAccordion">
                            <div class="accordion-body">
                                <?php if (empty($instituteSummary)): ?>
                                    <p class="text-muted">Ma'lumotlar topilmadi.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light"><tr><th>Bo'lim</th><th class="text-center" style="width: 15%;">Jami Reja</th><th class="text-center" style="width: 15%;">Jami Bajarildi</th><th style="width: 25%;">Umumiy Bajarilish (%)</th></tr></thead>
                                            <tbody>
                                            <?php foreach($instituteSummary as $code => $summary): ?>
                                                <?php
                                                    $target = $summary['target']; $accomplished = $summary['accomplished'];
                                                    $percentage = 0; $percentage_text = '0%'; $bar_class = 'bg-secondary'; $progress_wrapper_style = '';
                                                    if ($target > 0) {
                                                        $percentage = round(($accomplished / $target) * 100); $percentage_text = $percentage . '%';
                                                        if ($accomplished == 0) { $progress_wrapper_style = 'border: 1px solid #dc3545; background-color: rgba(220, 53, 69, 0.1);'; $bar_class = ''; $percentage = 0; }
                                                        elseif ($accomplished >= $target) { $bar_class = 'bg-success'; } else { $bar_class = 'bg-warning'; }
                                                    } elseif ($accomplished > 0) { $percentage = 100; $percentage_text = '★ Bonus'; $bar_class = 'bg-info'; }
                                                ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($code) ?></strong> - <?= htmlspecialchars($sections[$code]['name']) ?></td>
                                                    <td class="text-center"><?= number_format($target, 2) ?></td>
                                                    <td class="text-center fw-bold"><?= number_format($accomplished, 2) ?></td>
                                                    <td>
                                                        <div class="progress position-relative" title="<?= $percentage_text ?>" style="height: 20px; font-size: 0.8rem; <?= $progress_wrapper_style ?>">
                                                            <?php if($accomplished == 0 && $target > 0): ?><div class="position-absolute w-100 text-center text-danger fw-bold"><?= $percentage_text ?></div><?php endif; ?>
                                                            <div class="progress-bar <?= $bar_class ?>" role="progressbar" style="width: <?= min($percentage, 100) ?>%;" aria-valuenow="<?= $percentage ?>"><?php if(!($accomplished == 0 && $target > 0)): ?><?= $percentage_text ?><?php endif; ?></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFilter">
                            <button class="accordion-button <?= !$isFilterActive ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilter" aria-expanded="<?= $isFilterActive ? 'true' : 'false' ?>" aria-controls="collapseFilter">
                                <i class="bi bi-filter-circle me-2"></i> Filtrlar Orqali Tahlil
                            </button>
                        </h2>
                        <div id="collapseFilter" class="accordion-collapse collapse <?= $isFilterActive ? 'show' : '' ?>" data-bs-parent="#mainReportAccordion">
                            <div class="accordion-body">
                                <form method="GET" class="row g-3 align-items-end p-3 mb-3 bg-light rounded border">
                                    <div class="col-md-5"><label for="faculty_id" class="form-label">Fakultetni tanlang:</label><select name="faculty_id" id="faculty_id" class="form-select"><option value="">-- Barcha fakultetlar --</option><?php foreach ($faculties as $faculty): ?><option value="<?= $faculty['id'] ?>" <?= $filter_faculty_id == $faculty['id'] ? 'selected' : '' ?>><?= htmlspecialchars($faculty['name']) ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-5"><label for="department_id" class="form-label">Kafedrani tanlang:</label><select name="department_id" id="department_id" class="form-select" <?= !$filter_faculty_id ? 'disabled' : ''?>><option value="">-- Avval fakultetni tanlang --</option><?php foreach ($departments as $department): ?><option value="<?= $department['id'] ?>" class="department-option" data-faculty-id="<?= $department['faculty_id'] ?>" style="display: none;"><?= htmlspecialchars($department['name']) ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Ko'rsatish</button></div>
                                </form>

                                <?php if($filter_faculty_id > 0 && isset($systemProgressData[$filter_faculty_id])): ?>
                                <div class="row g-4 mt-2">
                                    <div class="col-lg-5">
                                        <h5><span class="text-muted">Fakultet:</span> <?= htmlspecialchars($systemProgressData[$filter_faculty_id]['name']) ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light"><tr><th>Bo'lim</th><th class="text-center" style="width: 15%;">Jami Reja</th><th class="text-center" style="width: 15%;">Jami Bajarildi</th><th style="width: 25%;">Bajarilish (%)</th></tr></thead>
                                                <tbody>
                                                <?php foreach($systemProgressData[$filter_faculty_id]['summary'] as $code => $summary): ?>
                                                    <?php
                                                        $target = $summary['target']; $accomplished = $summary['accomplished'];
                                                        $percentage = 0; $percentage_text = '0%'; $bar_class = 'bg-secondary'; $progress_wrapper_style = '';
                                                        if ($target > 0) {
                                                            $percentage = round(($accomplished / $target) * 100); $percentage_text = $percentage . '%';
                                                            if ($accomplished == 0) { $progress_wrapper_style = 'border: 1px solid #dc3545; background-color: rgba(220, 53, 69, 0.1);'; $bar_class = ''; $percentage = 0; }
                                                            elseif ($accomplished >= $target) { $bar_class = 'bg-success'; } else { $bar_class = 'bg-warning'; }
                                                        } elseif ($accomplished > 0) { $percentage = 100; $percentage_text = '★ Bonus'; $bar_class = 'bg-info'; }
                                                    ?>
                                                    <tr><td><strong><?= htmlspecialchars($code) ?></strong></td><td class="text-center"><?= number_format($target, 2) ?></td><td class="text-center fw-bold"><?= number_format($accomplished, 2) ?></td>
                                                        <td>
                                                            <div class="progress position-relative" title="<?= $percentage_text ?>" style="height: 20px; font-size: 0.8rem; <?= $progress_wrapper_style ?>">
                                                                <?php if($accomplished == 0 && $target > 0): ?><div class="position-absolute w-100 text-center text-danger fw-bold"><?= $percentage_text ?></div><?php endif; ?>
                                                                <div class="progress-bar <?= $bar_class ?>" role="progressbar" style="width: <?= min($percentage, 100) ?>%;" aria-valuenow="<?= $percentage ?>"><?php if(!($accomplished == 0 && $target > 0)): ?><?= $percentage_text ?><?php endif; ?></div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <h5>Kafedralar kesimida batafsil:</h5>
                                        <div class="table-responsive" >
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light"><tr><th>Kafedra / Bo'lim</th><th class="text-center" style="width: 15%;">Reja</th><th class="text-center" style="width: 15%;">Bajarildi</th><th style="width: 25%;">Bajarilish (%)</th></tr></thead>
                                                <tbody>
                                                <?php foreach($systemProgressData[$filter_faculty_id]['departments'] as $deptId => $deptData): ?>
                                                    <?php if($filter_department_id > 0 && $deptId != $filter_department_id) continue; ?>
                                                    
                                                    <tr class="table-secondary">
                                                        <td colspan="4" class="fw-bold"><?= htmlspecialchars($deptData['name']) ?></td>
                                                    </tr>

                                                    <?php foreach($deptData['progress'] as $code => $progress): ?>
                                                        <?php
                                                            $target = $progress['target']; $accomplished = $progress['accomplished'];
                                                            $percentage = 0; $percentage_text = '0%'; $bar_class = 'bg-secondary'; $progress_wrapper_style = '';
                                                            if ($target > 0) {
                                                                $percentage = round(($accomplished / $target) * 100); $percentage_text = $percentage . '%';
                                                                if ($accomplished == 0) { $progress_wrapper_style = 'border: 1px solid #dc3545; background-color: rgba(220, 53, 69, 0.1);'; $bar_class = ''; $percentage = 0; }
                                                                elseif ($accomplished >= $target) { $bar_class = 'bg-success'; } else { $bar_class = 'bg-warning'; }
                                                            } elseif ($accomplished > 0) { $percentage = 100; $percentage_text = '★ Bonus'; $bar_class = 'bg-info'; }
                                                        ?>
                                                        <tr>
                                                            <td class="ps-4"><small><strong><?= htmlspecialchars($code) ?></strong> - <?= htmlspecialchars($sections[$code]['name']) ?></small></td>
                                                            <td class="text-center"><?= number_format($target, 2) ?></td>
                                                            <td class="text-center fw-bold"><?= number_format($accomplished, 2) ?></td>
                                                            <td>
                                                                <div class="progress position-relative" title="<?= $percentage_text ?>" style="height: 20px; font-size: 0.8rem; <?= $progress_wrapper_style ?>">
                                                                    <?php if($accomplished == 0 && $target > 0): ?><div class="position-absolute w-100 text-center text-danger fw-bold"><?= $percentage_text ?></div><?php endif; ?>
                                                                    <div class="progress-bar <?= $bar_class ?>" role="progressbar" style="width: <?= min($percentage, 100) ?>%;" aria-valuenow="<?= $percentage ?>"><?php if(!($accomplished == 0 && $target > 0)): ?><?= $percentage_text ?><?php endif; ?></div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php elseif($isFilterActive && !isset($systemProgressData[$filter_faculty_id])): ?>
                                     <p class="text-danger text-center mt-3">Tanlangan fakultet uchun ma'lumotlar topilmadi.</p>
                                <?php else: ?>
                                    <p class="text-muted text-center mt-3">Natijalarni ko'rish uchun yuqoridan fakultetni tanlang.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const facultySelect = document.getElementById('faculty_id');
        const departmentSelect = document.getElementById('department_id');
        
        if (facultySelect && departmentSelect) {
            const departmentOptions = departmentSelect.querySelectorAll('.department-option');

            function updateDepartmentOptions() {
                const selectedFacultyId = facultySelect.value;
                let currentDeptId = '<?= $filter_department_id ?>';

                if (!selectedFacultyId) {
                    departmentSelect.disabled = true;
                    departmentSelect.value = '';
                    departmentOptions.forEach(opt => opt.style.display = 'none');
                    departmentSelect.querySelector('option[value=""]').textContent = '-- Avval fakultetni tanlang --';
                    return;
                }

                departmentSelect.disabled = false;
                let hasOptions = false;
                departmentOptions.forEach(opt => {
                    if (opt.dataset.facultyId == selectedFacultyId) {
                        opt.style.display = 'block';
                        hasOptions = true;
                    } else {
                        opt.style.display = 'none';
                    }
                });
                
                if(hasOptions) {
                    departmentSelect.querySelector('option[value=""]').textContent = '-- Barcha kafedralar --';
                } else {
                     departmentSelect.querySelector('option[value=""]').textContent = '-- Kafedralar topilmadi --';
                }
                
                if (document.querySelector(`.department-option[value="${currentDeptId}"][data-faculty-id="${selectedFacultyId}"]`)) {
                    departmentSelect.value = currentDeptId;
                } else {
                    departmentSelect.value = '';
                }
            }

            facultySelect.addEventListener('change', () => {
                document.getElementById('department_id').value = '';
                updateDepartmentOptions();
            });
            
            updateDepartmentOptions();
        }
    });
    </script>
</body>
</html>
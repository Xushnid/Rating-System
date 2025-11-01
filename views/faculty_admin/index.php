<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Fakultet Boshqaruv Paneli - Rating System</title>
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
                <h1 class="h3 fw-bold">Fakultet Boshqaruv Paneli</h1>
                <p class="text-muted mb-0">Fakultet kesimida kafedralarning reja bajarilish holati.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <h5 class="mb-0 text-end">
                    <small class="text-muted fw-normal">Joriy davr:</small><br>
                    <span class="text-primary fw-bold"><?= htmlspecialchars($selectedPeriod['name'] ?? 'Tanlanmagan') ?></span>
                </h5>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-primary me-3"><i class="bi bi-journal-text"></i></div><div><h4 class="mb-0"><?= $facultyStats['total'] ?? 0 ?></h4><p class="text-muted mb-0">Jami ishlanmalar</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-warning me-3"><i class="bi bi-clock-history"></i></div><div><h4 class="mb-0"><?= $facultyStats['pending'] ?? 0 ?></h4><p class="text-muted mb-0">Kutilmoqda</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-success me-3"><i class="bi bi-check-circle-fill"></i></div><div><h4 class="mb-0"><?= $facultyStats['approved'] ?? 0 ?></h4><p class="text-muted mb-0">Tasdiqlangan</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-danger me-3"><i class="bi bi-x-circle-fill"></i></div><div><h4 class="mb-0"><?= $facultyStats['rejected'] ?? 0 ?></h4><p class="text-muted mb-0">Rad etilgan</p></div></div></div></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Fakultet bo'yicha Umumiy Reja va Bajarilish Holati</h5>
            </div>
            <div class="card-body">
                <?php $isFilterActive = $filter_department_id > 0; ?>
                <div class="accordion" id="facultyReportAccordion">

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $isFilterActive ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFacultySummary" aria-expanded="<?= !$isFilterActive ? 'true' : 'false' ?>" aria-controls="collapseFacultySummary">
                                <i class="bi bi-pie-chart-fill me-2"></i> Fakultet Umumiy Hisoboti
                            </button>
                        </h2>
                        <div id="collapseFacultySummary" class="accordion-collapse collapse <?= !$isFilterActive ? 'show' : '' ?>" data-bs-parent="#facultyReportAccordion">
                            <div class="accordion-body">
                                <?php if (empty($facultyProgressData) || empty($facultyProgressData['summary'])): ?>
                                    <p class="text-muted">Ma'lumotlar topilmadi.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light"><tr><th>Bo'lim</th><th class="text-center" style="width: 15%;">Umumiy Reja</th><th class="text-center" style="width: 15%;">Jami Bajarildi</th><th style="width: 25%;">Bajarilish (%)</th></tr></thead>
                                            <tbody>
                                            <?php foreach($sections as $code => $section): ?>
                                                <?php
                                                    $summary = $facultyProgressData['summary'][$code] ?? ['target' => 0, 'accomplished' => 0];
                                                    $target = $summary['target']; $accomplished = $summary['accomplished'];
                                                    $percentage = 0; $percentage_text = '0%'; $bar_class = 'bg-secondary'; $progress_wrapper_style = '';
                                                    if ($target > 0) {
                                                        $percentage = round(($accomplished / $target) * 100); $percentage_text = $percentage . '%';
                                                        if ($accomplished == 0) { $progress_wrapper_style = 'border: 1px solid #dc3545; background-color: rgba(220, 53, 69, 0.1);'; $bar_class = ''; $percentage = 0; }
                                                        elseif ($accomplished >= $target) { $bar_class = 'bg-success'; } else { $bar_class = 'bg-warning'; }
                                                    } elseif ($accomplished > 0) { $percentage = 100; $percentage_text = '★ Bonus'; $bar_class = 'bg-info'; }
                                                ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($code) ?></strong> - <?= htmlspecialchars($section['name']) ?></td>
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
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= !$isFilterActive ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDeptFilter" aria-expanded="<?= $isFilterActive ? 'true' : 'false' ?>" aria-controls="collapseDeptFilter">
                                <i class="bi bi-filter-circle me-2"></i> Kafedralar Bo'yicha Tahlil
                            </button>
                        </h2>
                        <div id="collapseDeptFilter" class="accordion-collapse collapse <?= $isFilterActive ? 'show' : '' ?>" data-bs-parent="#facultyReportAccordion">
                            <div class="accordion-body">
                                <form method="GET" class="row g-3 align-items-end p-3 mb-3 bg-light rounded border">
                                    <div class="col-md-10">
                                        <label for="department_id" class="form-label">Kafedrani tanlang:</label>
                                        <select name="department_id" id="department_id" class="form-select">
                                            <option value="">-- Barcha kafedralar --</option>
                                            <?php foreach ($departments_in_faculty as $department): ?>
                                                <option value="<?= $department['id'] ?>" <?= $filter_department_id == $department['id'] ? 'selected' : '' ?>><?= htmlspecialchars($department['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Ko'rsatish</button>
                                    </div>
                                </form>

                                <?php if (!empty($facultyProgressData) && !empty($facultyProgressData['departments'])): ?>
                                <div class="table-responsive mt-3" >
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light"><tr><th>Kafedra / Bo'lim</th><th class="text-center" style="width: 15%;">Reja</th><th class="text-center" style="width: 15%;">Bajarildi</th><th style="width: 25%;">Bajarilish (%)</th></tr></thead>
                                        <tbody>
                                        <?php foreach($facultyProgressData['departments'] as $deptId => $deptData): ?>
                                            <?php if($filter_department_id > 0 && $deptId != $filter_department_id) continue; ?>
                                            <tr class="table-secondary"><td colspan="4" class="fw-bold"><?= htmlspecialchars($deptData['name']) ?></td></tr>
                                            <?php if (empty($deptData['progress'])): ?>
                                                <tr><td colspan="4" class="text-center text-muted ps-4"><small>Ushbu kafedra uchun rejalar biriktirilmagan.</small></td></tr>
                                            <?php else: ?>
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
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
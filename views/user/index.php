<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Bosh Sahifa - Rating System</title>
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
                <h1 class="h3 fw-bold">Xush kelibsiz, <?= htmlspecialchars($currentUser['full_name']) ?>!</h1>
                <p class="text-muted mb-0">
                    <?php if ($currentUser['role'] === 'departmentadmin'): ?>
                         Bu sizning kafedra boshqaruv panelingiz.
                    <?php else: ?>
                        Bu sizning shaxsiy kabinetingiz.
                    <?php endif; ?>
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                 <h5 class="mb-0 text-end">
                    <small class="text-muted fw-normal">Joriy davr:</small><br>
                     <span class="text-primary fw-bold"><?= htmlspecialchars($selectedPeriod['name'] ?? 'Tanlanmagan') ?></span>
                </h5>
            </div>
        </div>

        <?php if ($currentUser['role'] === 'departmentadmin' && !empty($selectedPeriod) && $selectedPeriod['id'] > 0): ?>
            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="/department-admin/export-excel-11" class="btn btn-primary">
                    <i class="bi bi-file-earmark-excel me-2"></i> Jami Excelga export
                </a>
                <a href="/department-admin/export-excel" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-2"></i> PO' kesimida Excelga export 
                </a>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">

            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-primary me-3"><i class="bi bi-journal-text"></i></div><div><h4 class="mb-0"><?= $stats['total'] ?? 0 ?></h4><p class="text-muted mb-0">Jami ishlanmalar</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-warning me-3"><i class="bi bi-clock-history"></i></div><div><h4 class="mb-0"><?= $stats['pending'] ?? 0 ?></h4><p class="text-muted mb-0">Kutilmoqda</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-success me-3"><i class="bi bi-check-circle-fill"></i></div><div><h4 class="mb-0"><?= $stats['approved'] ?? 0 ?></h4><p class="text-muted mb-0">Tasdiqlangan</p></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body d-flex align-items-center"><div class="fs-2 text-danger me-3"><i class="bi bi-x-circle-fill"></i></div><div><h4 class="mb-0"><?= $stats['rejected'] ?? 0 ?></h4><p class="text-muted mb-0">Rad etilgan</p></div></div></div></div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="accordion" id="mainDashboardAccordion">

                    <?php $isTeacherFilterActive = $filter_teacher_id > 0; ?>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingMyProgress">
                            <button class="accordion-button <?= $isTeacherFilterActive ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMyProgress" aria-expanded="<?= !$isTeacherFilterActive ? 'true' : 'false' ?>" aria-controls="collapseMyProgress">
                                <i class="bi bi-person-check-fill me-2"></i> Mening Shaxsiy Rejalarimning Bajarilishi
                            </button>
                        </h2>
                        <div id="collapseMyProgress" class="accordion-collapse collapse <?= !$isTeacherFilterActive ? 'show' : '' ?>" data-bs-parent="#mainDashboardAccordion">
                            <div class="accordion-body">
                                <?php if (empty($userProgressData)): ?>
                                    <div class="text-center p-4 text-muted">Siz uchun tanlangan davrga reja-qiymatlar hali biriktirilmagan.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm align-middle">
                                            <thead class="table-light"><tr><th>Bo'lim</th><th class="text-center" style="width: 15%;">Reja</th><th class="text-center" style="width: 15%;">Bajarildi</th><th style="width: 30%;">Bajarilish (%)</th></tr></thead>
                                            <tbody>
                                                <?php foreach($userProgressData as $code => $data): ?>
                                                    <?php
                                                        $target = $data['target']; $accomplished = $data['accomplished'];
                                                        $percentage = 0; $percentage_text = '0%'; $bar_class = 'bg-secondary'; $progress_wrapper_style = '';
                                                        if ($target > 0) {
                                                            $percentage = round(($accomplished / $target) * 100); $percentage_text = $percentage . '%';
                                                            if ($accomplished == 0) { $progress_wrapper_style = 'border: 1px solid #dc3545; background-color: rgba(220, 53, 69, 0.1);'; $bar_class = ''; $percentage = 0; $percentage_text = '0%'; }
                                                            elseif ($accomplished >= $target) { $bar_class = 'bg-success'; } else { $bar_class = 'bg-warning'; }
                                                        } elseif ($accomplished > 0) { $percentage = 100; $percentage_text = '★ Bonus'; $bar_class = 'bg-info'; }
                                                    ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($code) ?></strong> - <?= htmlspecialchars($sections[$code]['name'] ?? 'Noma\'lum bo\'lim') ?></td>
                                                        <td class="text-center fw-medium"><?= number_format($target, 2) ?></td>
                                                        <td class="text-center fw-bold"><?= number_format($accomplished, 2) ?></td>
                                                        <td>
                                                            <div class="progress position-relative" title="<?= $percentage_text ?>" style="height: 20px; font-size: 0.8rem; <?= $progress_wrapper_style ?>">
                                                                <?php if($accomplished == 0 && $target > 0): ?><div class="position-absolute w-100 text-center text-danger fw-bold"><?= $percentage_text ?></div><?php endif; ?>
                                                                <div class="progress-bar <?= $bar_class ?>" role="progressbar" style="width: <?= $percentage > 100 ? 100 : $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"><?php if(!($accomplished == 0 && $target > 0)): ?><?= $percentage_text ?><?php endif; ?></div>
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

                    <?php if ($currentUser['role'] === 'departmentadmin'): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingDepartmentProgress">
                             <button class="accordion-button <?= !$isTeacherFilterActive ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDepartmentProgress" aria-expanded="<?= $isTeacherFilterActive ? 'true' : 'false' ?>" aria-controls="collapseDepartmentProgress">
                                <i class="bi bi-diagram-3-fill me-2"></i> Kafedra Bo'yicha Tahlil
                            </button>
                        </h2>
                        <div id="collapseDepartmentProgress" class="accordion-collapse collapse <?= $isTeacherFilterActive ? 'show' : '' ?>" data-bs-parent="#mainDashboardAccordion">
                            <div class="accordion-body">
                                <form method="GET" class="row g-3 align-items-end p-3 mb-3 bg-light rounded border">
                                    <div class="col-md-10"><label for="teacher_id" class="form-label">O'qituvchini tanlang:</label><select name="teacher_id" id="teacher_id" class="form-select"><option value="">-- Barcha o'qituvchilar --</option><?php foreach ($teachers_in_department as $teacher): ?><option value="<?= $teacher['id'] ?>" <?= $filter_teacher_id == $teacher['id'] ? 'selected' : '' ?>><?= htmlspecialchars($teacher['full_name']) ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Ko'rsatish</button></div>
                                </form>
                                
                                <div class="row g-4 mt-2">
                                    <div class="col-lg-5">
                                        <h5>Kafedra umumiy natijasi:</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light"><tr><th>Bo'lim</th><th class="text-center">Umumiy Reja</th><th class="text-center">Jami Bajarildi</th><th class="text-center">Bajarilish (%)</th></tr></thead>
                                                <tbody>
                                                <?php foreach($departmentProgressData['summary'] as $code => $summary): ?>
                                                    <?php
                                                        $target = $summary['target']; $accomplished = $summary['accomplished'];
                                                        $percentage = 0; $percentage_text = '0%'; $bar_class = 'bg-secondary'; $progress_wrapper_style = '';
                                                        if ($target > 0) {
                                                            $percentage = round(($accomplished / $target) * 100); $percentage_text = $percentage . '%';
                                                            if ($accomplished == 0) { $progress_wrapper_style = 'border: 1px solid #dc3545; background-color: rgba(220, 53, 69, 0.1);'; $bar_class = ''; $percentage = 0; }
                                                            elseif ($accomplished >= $target) { $bar_class = 'bg-success'; } else { $bar_class = 'bg-warning'; }
                                                        } elseif ($accomplished > 0) { $percentage = 100; $percentage_text = '★ Bonus'; $bar_class = 'bg-info'; }
                                                    ?>
                                                    <tr><td><strong><?= htmlspecialchars($code) ?></strong> - <?= htmlspecialchars($sections[$code]['name'] ?? '') ?></td><td class="text-center"><?= number_format($target, 2) ?></td><td class="text-center"><?= number_format($accomplished, 2) ?></td>
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
                                        <h5>
                                            <?php if($filter_teacher_id > 0 && isset($departmentProgressData['users'][$filter_teacher_id])): ?>
                                                Tanlangan o'qituvchi: <span class="text-primary"><?= htmlspecialchars($departmentProgressData['users'][$filter_teacher_id]['full_name']) ?></span>
                                            <?php else: ?>
                                                O'qituvchilar kesimida:
                                            <?php endif; ?>
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light"><tr><th>O'qituvchi / Bo'lim</th><th class="text-center">Reja</th><th class="text-center">Bajarildi</th><th>Bajarilish (%)</th></tr></thead>
                                                <tbody>
                                                <?php foreach($departmentProgressData['users'] as $userId => $userData): ?>
                                                    <?php if($filter_teacher_id > 0 && $userId != $filter_teacher_id) continue; ?>
                                                    <tr class="table-secondary"><td colspan="4" class="fw-bold"><?= htmlspecialchars($userData['full_name']) ?></td></tr>
                                                    <?php if(empty($userData['progress'])): ?>
                                                        <tr><td colspan="4" class="text-center text-muted ps-4"><small>Ushbu o'qituvchi uchun rejalar biriktirilmagan.</small></td></tr>
                                                    <?php else: ?>
                                                        <?php foreach($userData['progress'] as $code => $data): ?>
                                                            <?php
                                                                $target = $data['target']; $accomplished = $data['accomplished'];
                                                                $percentage = 0; $percentage_text = '0%'; $bar_class = 'bg-secondary'; $progress_wrapper_style = '';
                                                                if ($target > 0) {
                                                                    $percentage = round(($accomplished / $target) * 100); $percentage_text = $percentage . '%';
                                                                    if ($accomplished == 0) { $progress_wrapper_style = 'border: 1px solid #dc3545; background-color: rgba(220, 53, 69, 0.1);'; $bar_class = ''; $percentage = 0; }
                                                                    elseif ($accomplished >= $target) { $bar_class = 'bg-success'; } else { $bar_class = 'bg-warning'; }
                                                                } elseif ($accomplished > 0) { $percentage = 100; $percentage_text = '★ Bonus'; $bar_class = 'bg-info'; }
                                                            ?>
                                                            <tr>
                                                                <td class="ps-4"><small><strong><?= htmlspecialchars($code) ?></strong></small></td>
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>O'qituvchilarga Rejalarni Taqsimlash - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .table-responsive { overflow-x: auto; max-height: 70vh; }
        th, td { white-space: nowrap; vertical-align: middle; }
        .table thead th { position: sticky; top: 0; z-index: 1; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .remaining-positive { color: #fd7e14; } /* Orange */
        .remaining-zero { color: var(--bs-success); }
        .remaining-negative { color: var(--bs-danger); font-weight: bold; }
        input:read-only { /* O'ZGARTIRILDI: disabled -> readonly */
            background-color: #e9ecef !important;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container-fluid py-4 px-lg-5">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-distribute-vertical"></i> O'qituvchilarga Rejalarni Taqsimlash</h2>
        </div>
        <div id="alertContainer"></div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    Joriy tanlangan davr: 
                    <span class="text-primary fw-bold"><?= htmlspecialchars($selectedPeriod['name'] ?? 'Tanlanmagan') ?></span>
                    <?= ($selectedPeriod['status'] ?? '') === 'closed' ? '<span class="badge bg-secondary ms-2">Yakunlangan</span>' : '' ?>
                </h5>
            </div>

            <?php if (!empty($selectedPeriod) && $selectedPeriod['id'] > 0): ?>
                
                <div class="card-header p-0 border-bottom">
                    <ul class="nav nav-tabs card-header-tabs" id="group-tabs-targets">
                        <li class="nav-item">
                            <a class="nav-link <?= $active_group_key === 'all' ? 'active' : '' ?>" href="?group=all">
                                Barchasi
                            </a>
                        </li>
                        <?php foreach($section_groups as $g_key => $group): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_group_key === $g_key ? 'active' : '' ?>" href="?group=<?= htmlspecialchars($g_key) ?>">
                                <?= htmlspecialchars($group['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <form id="userTargetsForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="targetsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">O'qituvchi</th>
                                        <?php
                                            // YANGI MANTIQ: Faqat tanlangan guruhdagi bo'limlarni ko'rsatish
                                            $sections_to_show = [];
                                            if ($active_group_key === 'all') {
                                                $sections_to_show = $sections;
                                            } else {
                                                $section_codes_in_group = $section_groups[$active_group_key]['sections'] ?? [];
                                                foreach($section_codes_in_group as $code) {
                                                    if(isset($sections[$code])) {
                                                        $sections_to_show[$code] = $sections[$code];
                                                    }
                                                }
                                            }
                                        ?>
                                        <?php foreach($sections_to_show as $code => $section): ?>
                                            <th class="text-center" data-bs-toggle="tooltip" title="<?= htmlspecialchars($section['name']) ?>">
                                                <?= htmlspecialchars($code) ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($usersInDepartment as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                        <?php foreach($sections_to_show as $code => $section): ?>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm target-input"
                                                   name="targets[<?= $user['id'] ?>][<?= $code ?>]"
                                                   data-section-code="<?= htmlspecialchars($code) ?>"
                                                   value="<?= htmlspecialchars($userTargets[$user['id']][$code] ?? '0') ?>"
                                                   <?= $is_period_closed ? 'readonly' : '' ?>>
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr class="total-row">
                                        <td><strong>Jami Taqsimlandi:</strong></td>
                                        <?php foreach($sections_to_show as $code => $section): ?>
                                            <td class="text-center" id="distributed-total-<?= $code ?>">0.00</td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr class="total-row">
                                        <td><strong>Kafedra Rejasi:</strong></td>
                                        <?php foreach($sections_to_show as $code => $section): ?>
                                            <td class="text-center text-primary" id="department-target-<?= $code ?>">
                                                <?= htmlspecialchars($departmentTargets[$currentUser['department_id']][$code] ?? '0.00') ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <tr class="total-row">
                                        <td><strong>Qoldiq:</strong></td>
                                        <?php foreach($sections_to_show as $code => $section): ?>
                                             <td class="text-center" id="remaining-<?= $code ?>">0.00</td>
                                        <?php endforeach; ?>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if (!$is_period_closed): ?>
                    <div class="card-footer text-end">
                         <button type="submit" id="saveBtn" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i> Taqsimotni Saqlash
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div class="card-body text-center p-5">
                    <h5 class="text-muted">Rejalarni taqsimlash uchun, iltimos, yuqoridan faol davrni tanlang.</h5>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        const userTargetsForm = document.getElementById('userTargetsForm');
        if (userTargetsForm) {
            // 1. Sahifa ochilganda barcha inputlarning boshlang'ich qiymatlarini saqlab olamiz
            const initialTargetValues = {};
            document.querySelectorAll('.target-input').forEach(input => {
                initialTargetValues[input.name] = input.value;
            });

            const inputs = document.querySelectorAll('.target-input');
            const sectionCodes = <?= json_encode(array_keys($sections_to_show)) ?>;

            function calculateTotalsAndLockInputs() {
                sectionCodes.forEach(code => {
                    const distributedTotalEl = document.getElementById(`distributed-total-${code}`);
                    const departmentTargetEl = document.getElementById(`department-target-${code}`);
                    const remainingEl = document.getElementById(`remaining-${code}`);
                    
                    if (!distributedTotalEl || !departmentTargetEl || !remainingEl) return;

                    const inputsForCode = document.querySelectorAll(`input[data-section-code="${code}"]`);
                    let distributed = 0;
                    inputsForCode.forEach(input => {
                        distributed += parseFloat(input.value) || 0;
                    });
                    
                    const target = parseFloat(departmentTargetEl.textContent) || 0;
                    const remaining = target - distributed;

                    distributedTotalEl.textContent = distributed.toFixed(2);
                    remainingEl.textContent = remaining.toFixed(2);
                    
                    remainingEl.className = 'text-center';
                    if (remaining < 0) {
                        remainingEl.classList.add('remaining-negative');
                    } else if (remaining > 0) {
                        remainingEl.classList.add('remaining-positive');
                    } else {
                        remainingEl.classList.add('remaining-zero');
                    }

                    // ### QAYTA TIKLANGAN MANTIQ ###
                    // Reja bajarilganda yoki ortib ketganda, qiymati 0 bo'lgan inputlarni o'chirish (disable).
                    const isFullyDistributed = distributed >= target;
                    inputsForCode.forEach(input => {
                        // Agar davr yopiq bo'lmasa, bu mantiq ishlaydi
                        if (<?= !$is_period_closed ? 'true' : 'false' ?>) {
                             if (isFullyDistributed && (parseFloat(input.value) || 0) === 0) {
                                input.disabled = true;
                            } else {
                                input.disabled = false;
                            }
                        }
                    });
                    // ### MANTIQ TUGADI ###
                });
            }
            
            inputs.forEach(input => {
                input.addEventListener('input', calculateTotalsAndLockInputs);
            });

            calculateTotalsAndLockInputs();

            userTargetsForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const changedData = new FormData();
                changedData.append('csrf_token', userTargetsForm.querySelector('[name="csrf_token"]').value);

                let hasChanges = false;
                document.querySelectorAll('.target-input').forEach(input => {
                    if (input.value !== initialTargetValues[input.name]) {
                        // Faqat aktiv (disabled bo'lmagan) va o'zgargan inputlarni yuboramiz
                        if (!input.disabled) {
                            changedData.append(input.name, input.value);
                            hasChanges = true;
                        }
                    }
                });

                if (!hasChanges) {
                    showAlert("Hech narsa o'zgartirilmadi.", 'info');
                    return;
                }
                
                const saveBtn = document.getElementById('saveBtn');
                const originalBtnText = saveBtn.innerHTML;
                saveBtn.disabled = true;
                saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Saqlanmoqda...`;
                
                try {
                    const response = await fetch('<?= BASE_URL ?>ajax/department-admin/targets/save', {
                        method: 'POST',
                        body: changedData
                    });
                    const result = await response.json();
                    showAlert(result.message, result.success ? 'success' : 'danger');

                    if (result.success) {
                        document.querySelectorAll('.target-input').forEach(input => {
                            initialTargetValues[input.name] = input.value;
                        });
                    }
                } catch (error) {
                    showAlert('Server bilan bog\'lanishda xatolik yuz berdi.', 'danger');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalBtnText;
                }
            });
        }
    });
    </script>
</body>
</html>
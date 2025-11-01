<?php
// Get current user directly from auth object to ensure it's always available
$currentUser = $auth->getCurrentUser();
$currentUserRole = $currentUser['role'] ?? 'user';

// NOTIFICATION INDICATORS - Calculate before nav_items array
$showAdminPendingIndicator = false;
$showUserRejectedIndicator = false;
$showDeptAdminRejectedIndicator = false;

// Check if we need to show indicators (only when logged in and required variables are available)
if ($auth->isLoggedIn()) {
    $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
    
    // Debug logging
    error_log("Header notifications - User role: {$currentUserRole}, Period: {$selectedPeriodId}");
    
    // Create a simple Ishlanma instance to check for notifications
    if ($selectedPeriodId > 0) {
        try {
            // Check if global db is available
            if (isset($GLOBALS['db'])) {
                $ishlanmaForNotifications = new \App\Ishlanma($GLOBALS['db']);
                error_log("Header notifications - Ishlanma instance created successfully");
            } else {
                // Fallback: create new Database instance
                $db = new \App\Database();
                $ishlanmaForNotifications = new \App\Ishlanma($db);
                error_log("Header notifications - Created new database instance");
            }
            
            // For SuperAdmin and DepartmentAdmin - yellow dot on Tasdiqlash button
            if (in_array($currentUserRole, ['superadmin', 'departmentadmin'])) {
                $departmentId = ($currentUserRole === 'departmentadmin') ? ($currentUser['department_id'] ?? null) : null;
                $showAdminPendingIndicator = $ishlanmaForNotifications->hasPendingSubmissionsForAdmin($currentUserRole, $departmentId, $selectedPeriodId);
                error_log("Header notifications - Admin pending check: role={$currentUserRole}, dept={$departmentId}, result=" . ($showAdminPendingIndicator ? 'true' : 'false'));
            }
            
            // For User and DepartmentAdmin - red dot on Mening Ishlanmalarim button
            if (in_array($currentUserRole, ['user', 'departmentadmin'])) {
                $userId = $currentUser['id'] ?? null;
                error_log("Header notifications - User/DepartmentAdmin role detected. User ID: {$userId}, Period: {$selectedPeriodId}");
                if ($userId) {
                    $showUserRejectedIndicator = $ishlanmaForNotifications->hasRejectedSubmissionsForUser($userId, $selectedPeriodId);
                    error_log("Header notifications - User rejected check: user={$userId}, period={$selectedPeriodId}, result=" . ($showUserRejectedIndicator ? 'true' : 'false'));
                    
                    // FAILSAFE: Direct database check
                    try {
                        $directQuery = "SELECT COUNT(*) as count FROM submissions WHERE user_id = :user_id AND status = 'rejected' AND period_id = :period_id";
                        // Use global db or the same db instance
                        $dbToUse = isset($GLOBALS['db']) ? $GLOBALS['db'] : $db;
                        $directResult = $dbToUse->query($directQuery, ['user_id' => $userId, 'period_id' => $selectedPeriodId])->fetch();
                        $directCount = $directResult['count'] ?? 0;
                        error_log("Header notifications - FAILSAFE direct query count: {$directCount}");
                        error_log("Header notifications - Query: {$directQuery} with user_id={$userId}, period_id={$selectedPeriodId}");
                        
                        // Also check all submissions for this user
                        $allQuery = "SELECT id, status FROM submissions WHERE user_id = :user_id AND period_id = :period_id";
                        $allResult = $dbToUse->query($allQuery, ['user_id' => $userId, 'period_id' => $selectedPeriodId])->fetchAll();
                        error_log("Header notifications - All submissions for user: " . json_encode($allResult));
                        
                        if ($directCount > 0 && !$showUserRejectedIndicator) {
                            error_log("Header notifications - MISMATCH! Direct query shows {$directCount} but method returned false");
                            $showUserRejectedIndicator = true; // Force it to true
                        }
                    } catch (Exception $failsafeError) {
                        error_log("Header notifications - Failsafe error: " . $failsafeError->getMessage());
                    }
                } else {
                    error_log("Header notifications - User ID is null or missing");
                }
            } else {
                error_log("Header notifications - Current user role is not user/departmentadmin: {$currentUserRole}");
            }
            
            // For DepartmentAdmin - red dot on Kafedra Ishlanmalari button (department-wide rejected submissions)
            if ($currentUserRole === 'departmentadmin') {
                $departmentId = $currentUser['department_id'] ?? null;
                if ($departmentId) {
                    // Check if there are any rejected submissions in the entire department
                    try {
                        $deptQuery = "SELECT COUNT(*) as count FROM submissions s JOIN users u ON s.user_id = u.id WHERE u.department_id = :department_id AND s.status = 'rejected' AND s.period_id = :period_id";
                        $dbToUse = isset($GLOBALS['db']) ? $GLOBALS['db'] : $db;
                        $deptResult = $dbToUse->query($deptQuery, ['department_id' => $departmentId, 'period_id' => $selectedPeriodId])->fetch();
                        $deptRejectedCount = $deptResult['count'] ?? 0;
                        $showDeptAdminRejectedIndicator = $deptRejectedCount > 0;
                        error_log("Header notifications - Department admin rejected check: dept={$departmentId}, period={$selectedPeriodId}, count={$deptRejectedCount}, result=" . ($showDeptAdminRejectedIndicator ? 'true' : 'false'));
                    } catch (Exception $deptError) {
                        error_log("Header notifications - Department rejected check error: " . $deptError->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Header notification indicator error: ' . $e->getMessage());
        }
    } else {
        error_log("Header notifications - No period selected or period is 0");
    }
}

$nav_items = [
    // --- SuperAdmin bo'limlari ---
    'admin_index' => ['url' => '/admin', 'label' => 'Bosh Sahifa', 'role' => ['superadmin']],
	'periods' => ['url' => '/admin/periods', 'label' => 'Davrlar', 'role' => ['superadmin']],
	'targets' => ['url' => '/admin/targets', 'label' => 'Rejalar', 'role' => ['superadmin']],
    'faculties' => ['url' => '/admin/faculties', 'label' => 'Fakultetlar', 'role' => ['superadmin']],
    'departments' => ['url' => '/admin/departments', 'label' => 'Kafedralar', 'role' => ['superadmin']],
	'users' => ['url' => '/admin/users', 'label' => 'Foydalanuvchilar', 'role' => ['superadmin']],
    'admin_ishlanma' => ['url' => '/admin/ishlanmalar', 'label' => 'Barcha Ishlanmalar', 'role' => ['superadmin']],
    'verify' => ['url' => '/admin/verify', 'label' => 'Tasdiqlash', 'role' => ['superadmin']],

    // --- Faculty Admin bo'limlari ---
    'faculty_admin_index' => ['url' => '/faculty-admin', 'label' => 'Boshqaruv Paneli', 'role' => ['facultyadmin']],
    'faculty_admin_submissions' => ['url' => '/faculty-admin/submissions', 'label' => 'Ishlanmalar', 'role' => ['facultyadmin']],
    'faculty_admin_dept_admins' => ['url' => '/faculty-admin/department-admins', 'label' => 'Kafedra Adminlari', 'role' => ['facultyadmin']],
    
    // --- O'qituvchi (User) va Kafedra Admini uchun shaxsiy bo'limlar ---
    'user_index' => ['url' => '/user', 'label' => 'Bosh Sahifa', 'role' => ['user', 'departmentadmin'], 'group' => 'shaxsiy'],
    'ishlanmalar' => ['url' => '/user/ishlanmalar', 'label' => 'Mening Ishlanmalarim', 'role' => ['user', 'departmentadmin'], 'group' => 'shaxsiy'],
    'add_ishlanma' => ['url' => '/user/add-ishlanma', 'label' => 'Ishlanma Qo\'shish', 'role' => ['user', 'departmentadmin'], 'group' => 'shaxsiy'],
    
    // --- Kafedra Admini uchun ma'muriy bo'limlar ---
    'dept_admin_submissions' => ['url' => '/department-admin/submissions', 'label' => 'Kafedra Ishlanmalari', 'role' => ['departmentadmin'], 'group' => 'admin'],
    'dept_admin_verify' => ['url' => '/department-admin/verify', 'label' => 'Tasdiqlash', 'role' => ['departmentadmin'], 'group' => 'admin'],
    'dept_admin_targets' => ['url' => '/department-admin/targets', 'label' => 'Rejalar', 'role' => ['departmentadmin'], 'group' => 'admin'],
    'dept_admin_add_user' => ['url' => '/department-admin/add-user', 'label' => "O'qituvchi Qo'shish", 'role' => ['departmentadmin'], 'group' => 'admin'],
];

$brand_url = '/';

// index.php faylida yaratilgan global o'zgaruvchilarni chaqirib olamiz
global $all_periods_for_header, $selectedPeriod;
?>

<header class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= $brand_url ?>">
            <i class="bi bi-graph-up-arrow me-2"></i>NamDTU Rating
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="main-nav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php 
                $last_group = null;
                foreach ($nav_items as $key => $item): ?>
                    <?php if (in_array($currentUserRole, $item['role'])): ?>
                        <?php 
                        if ($currentUserRole === 'departmentadmin' && isset($item['group']) && $item['group'] !== $last_group) {
                            if ($last_group !== null) {
                                echo '<li class="nav-item mx-2 border-start border-light opacity-25"></li>';
                            }
                            $last_group = $item['group'];
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page ?? '') === $key ? 'active' : '' ?>" href="<?= $item['url']; ?>">
                                <?= htmlspecialchars($item['label']); ?>
                                <?php 
                                // Add notification indicators
                                if (($key === 'verify' || $key === 'dept_admin_verify') && $showAdminPendingIndicator): 
                                ?>
                                    <span class="d-inline-block ms-1" style="width: 10px; height: 10px; background-color: #ffc107; border-radius: 50%; vertical-align: middle;"></span>
                                <?php 
                                elseif ($key === 'ishlanmalar' && $showUserRejectedIndicator): 
                                ?>
                                    <span class="d-inline-block ms-1" style="width: 10px; height: 10px; background-color: #dc3545; border-radius: 50%; vertical-align: middle;"></span>
                                <?php 
                                elseif ($key === 'dept_admin_submissions' && $showDeptAdminRejectedIndicator): 
                                ?>
                                    <span class="d-inline-block ms-1" style="width: 10px; height: 10px; background-color: #dc3545; border-radius: 50%; vertical-align: middle;"></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <?php if ($auth->isLoggedIn()): // Faqat tizimga kirganlarga ko'rsatish ?>
            <form method="POST" action="" class="d-flex align-items-center me-lg-3" id="globalPeriodForm">
                <label for="global_period_id" class="form-label text-white-50 mb-0 me-2" style="white-space: nowrap;">Joriy Davr:</label>
                <select name="global_period_id" id="global_period_id" class="form-select form-select-sm bg-light bg-opacity-25 text-white border-0" onchange="this.form.submit()">
                    <?php if (empty($all_periods_for_header)): ?>
                        <option>Davrlar mavjud emas</option>
                    <?php else: ?>
                        <?php foreach($all_periods_for_header as $period): ?>
                            <option value="<?= $period['id'] ?>" <?= ($selectedPeriod['id'] ?? 0) == $period['id'] ? 'selected' : '' ?> style="color: black;">
                                <?= htmlspecialchars($period['name']) ?>
                                <?= $period['status'] === 'closed' ? '(Yakunlangan)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </form>
            <?php endif; ?>
           <div class="dropdown text-end">
                <a href="#" class="d-block link-light text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle fs-4 align-middle me-1"></i>
                    <span class="d-none d-sm-inline mx-1"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small shadow" aria-labelledby="dropdownUser">
                    <li>
                        <h6 class="dropdown-header">
                            <?= htmlspecialchars($currentUser['full_name']); ?><br>
                            <?php $roleInfo = translate_role($currentUser['role']); ?>
                            <span class="badge rounded-pill <?= htmlspecialchars($roleInfo['class']) ?>"><?= htmlspecialchars($roleInfo['name']) ?></span>
                        </h6>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="bi bi-person-gear me-2"></i>Profil sozlamalari</a></li>
                    <li><a class="dropdown-item" href="/logout"><i class="bi bi-box-arrow-right me-2"></i>Chiqish</a></li>
                </ul>
            </div>

        </div>
    </div>
</header>

<div id="csrf-token-container" data-token="<?= $auth->generateCsrfToken(); ?>"></div>

<?php include __DIR__ . '/modal_edit_profile.php'; ?>
<script>
// Bu yerdagi profilni tahrirlash skripti o'zgarishsiz qoladi
document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('editProfileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const password = document.getElementById('profile_password').value;
            const confirmPassword = document.getElementById('profile_password_confirm').value;
            const alertContainer = document.getElementById('profileAlertContainer');
            const saveBtn = document.getElementById('saveProfileBtn');
            
            if (password !== confirmPassword) {
                alertContainer.innerHTML = '<div class="alert alert-danger">Kiritilgan parollar bir-biriga mos kelmadi.</div>';
                return;
            }

            if (password && password.length < 6) {
                alertContainer.innerHTML = '<div class="alert alert-danger">Yangi parol kamida 6 ta belgidan iborat bo\'lishi kerak.</div>';
                return;
            }

            const originalBtnText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saqlanmoqda...';
            alertContainer.innerHTML = '';

            try {
                const formData = new FormData(profileForm);
                const response = await fetch('/ajax/user/profile/update', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alertContainer.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alertContainer.innerHTML = `<div class="alert alert-danger">${result.message || 'Xatolik yuz berdi.'}</div>`;
                }

            } catch (error) {
                alertContainer.innerHTML = '<div class="alert alert-danger">Server bilan bog\'lanishda xatolik.</div>';
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
            }
        });
    }
});
</script>


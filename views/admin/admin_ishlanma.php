<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Barcha Ishlanmalar - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container-fluid py-4 px-lg-5">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-journal-text"></i> Barcha Ishlanmalar</h2>
        </div>
        <div id="alertContainer"></div>
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs nav-tabs-bordered px-3" id="group-tabs">
                    <?php foreach ($section_groups as $g_key => $group): ?>
                        <li class="nav-item">
                            <a href="#" class="nav-link fs-6 fw-medium py-3 <?= $active_group_key === $g_key ? 'active' : '' ?>" 
                               data-group-key="<?= htmlspecialchars($g_key) ?>">
                                <?= htmlspecialchars($group['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <hr class="m-0">
                <ul class="nav nav-tabs card-header-tabs" id="ishlanma-tabs" role="tablist">
                    <?php 
                    $sections_in_active_group = $section_groups[$active_group_key]['sections'] ?? [];
                    foreach ($sections_in_active_group as $s_code): 
                        $section = $sections[$s_code];
                    ?>
                        <li class="nav-item" role="presentation">
                            <a href="#" class="nav-link px-4 <?= $active_section_code === $s_code ? 'active' : '' ?>" data-section="<?= htmlspecialchars($s_code) ?>" type="button">
                                 <?= htmlspecialchars($s_code) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-3 fw-bold" id="section-title"><?= htmlspecialchars($sections[$active_section_code]['name'] ?? 'Bo‘lim') ?></h5>
                
                <div class="row g-3 mb-4 p-3 bg-light border rounded">
                    <div class="col-md-6 col-lg-3">
                        <label for="facultyFilter" class="form-label">Fakultet</label>
                        <select id="facultyFilter" class="form-select">
                            <option value="">Barcha Fakultetlar</option>
                            <?php foreach ($faculties as $faculty): ?>
                                <option value="<?= $faculty['id'] ?>" <?= (int)($faculty_id_filter ?? 0) === $faculty['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($faculty['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <label for="departmentFilter" class="form-label">Kafedra</label>
                         <select id="departmentFilter" class="form-select" <?= empty($faculty_id_filter) ? 'disabled' : '' ?>>
                            <option value="">-- Avval fakultetni tanlang --</option>
                        </select>
                    </div>
                     <div class="col-md-6 col-lg-3">
                        <label for="userFilter" class="form-label">O'qituvchi</label>
                        <select id="userFilter" class="form-select" <?= empty($faculty_id_filter) ? 'disabled' : '' ?>>
                             <option value="">-- Avval fakultet/kafedrani tanlang --</option>
                        </select>
                    </div>
                     <div class="col-md-6 col-lg-3">
                        <label for="statusFilter" class="form-label">Holati</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">Barchasi</option>
                            <option value="pending" <?= ($status_filter ?? '') === 'pending' ? 'selected' : '' ?>>Kutilmoqda</option>
                            <option value="approved" <?= ($status_filter ?? '') === 'approved' ? 'selected' : '' ?>>Tasdiqlangan</option>
                            <option value="rejected" <?= ($status_filter ?? '') === 'rejected' ? 'selected' : '' ?>>Rad etilgan</option>
                        </select>
                    </div>
                </div>

                <div id="ishlanma-content">
                   <?php
                   $section_code = $active_section_code;
                   include __DIR__ . '/../partials/ishlanma_table_partial.php';
                   ?>
                </div>
            </div>
        </div>
    </main>
   <?php include __DIR__ . '/../partials/modal_edit_ishlanma.php'; ?>
   <?php include __DIR__ . '/../partials/modal_rejection_reason.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
    <script>
        const sectionGroups = <?= json_encode($section_groups) ?>;
        const sections = <?= json_encode($sections) ?>; /* TUZATILDI: allSections -> sections */
        const allDepartments = <?php echo json_encode($departments ?? []); ?>;
       
        const allUsers = <?php echo json_encode($users ?? []); ?>;
        const initialFilters = {
            faculty: "<?= $faculty_id_filter ?? '' ?>",
            department: "<?= $department_id_filter ?? '' ?>",
            user: "<?= $user_id_filter ?? '' ?>"
        };
    </script>
    <script src="<?= BASE_URL ?>assets/js/ishlanma-manager.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const manager = new IshlanmaManager('editItemModal', 'admin');
        const contentArea = document.getElementById('ishlanma-content');
        
        // Filtr elementlari
        const facultyFilter = document.getElementById('facultyFilter');
        const departmentFilter = document.getElementById('departmentFilter');
        const userFilter = document.getElementById('userFilter');
        const statusFilter = document.getElementById('statusFilter');

        // ======== HOLATNI BOSHQARISH (STATE MANAGEMENT) ========
        const currentURL = new URL(window.location.href);
        const activeGroup = currentURL.searchParams.get('group') || '<?= $active_group_key ?>';
        const activeSection = currentURL.searchParams.get('section') || '<?= $active_section_code ?>';

        function restoreStateOnReload() {
            const storedGroup = sessionStorage.getItem('activeGroup');
            const storedSection = sessionStorage.getItem('activeSection');
            const isReloading = sessionStorage.getItem('isReloading');

            if (isReloading && storedGroup && storedSection) {
                sessionStorage.removeItem('isReloading');
                
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('group', storedGroup);
                newUrl.searchParams.set('section', storedSection);
                
                if (window.location.href !== newUrl.href) {
                    window.location.href = newUrl.href;
                }
            }
        }
        
        restoreStateOnReload();

        // Guruh tabi bosilganda
        document.querySelectorAll('#group-tabs .nav-link').forEach(tab => {
            tab.addEventListener('click', e => {
                e.preventDefault();
                const groupKey = tab.dataset.groupKey;
                const firstSectionKey = sectionGroups[groupKey].sections[0];
                
                sessionStorage.setItem('activeGroup', groupKey);
                sessionStorage.setItem('activeSection', firstSectionKey);

                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('group', groupKey);
                newUrl.searchParams.set('section', firstSectionKey);
                // Filtr holatini saqlab qolish
                newUrl.searchParams.set('faculty_id', facultyFilter.value);
                newUrl.searchParams.set('department_id', departmentFilter.value);
                newUrl.searchParams.set('user_id', userFilter.value);
                newUrl.searchParams.set('status', statusFilter.value);
                window.location.href = newUrl.href;
            });
        });

        // Bo'lim tabi bosilganda
        document.querySelectorAll('#ishlanma-tabs .nav-link').forEach(tab => {
            tab.addEventListener('click', e => {
                e.preventDefault();
                const sectionKey = tab.dataset.section;

                sessionStorage.setItem('activeGroup', activeGroup);
                sessionStorage.setItem('activeSection', sectionKey);
                
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('section', sectionKey);
                 // Filtr holatini saqlab qolish
                newUrl.searchParams.set('faculty_id', facultyFilter.value);
                newUrl.searchParams.set('department_id', departmentFilter.value);
                newUrl.searchParams.set('user_id', userFilter.value);
                newUrl.searchParams.set('status', statusFilter.value);
                window.location.href = newUrl.href;
            });
        });

        // Filtr o'zgarganda sahifani qayta yuklash
        function handleFilterChange() {
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('faculty_id', facultyFilter.value);
            newUrl.searchParams.set('department_id', departmentFilter.value);
            newUrl.searchParams.set('user_id', userFilter.value);
            newUrl.searchParams.set('status', statusFilter.value);
            window.location.href = newUrl.href;
        }

        facultyFilter.addEventListener('change', handleFilterChange);
        departmentFilter.addEventListener('change', handleFilterChange);
        userFilter.addEventListener('change', handleFilterChange);
        statusFilter.addEventListener('change', handleFilterChange);

        window.refreshTable = () => {
            sessionStorage.setItem('activeGroup', activeGroup);
            sessionStorage.setItem('activeSection', activeSection);
            sessionStorage.setItem('isReloading', 'true');
            location.reload();
        };
        
        // ----- Kaskadli filtrlar uchun eski kod -----
        function populateDepartments(facultyId, selectedDepartmentId = null) {
            departmentFilter.innerHTML = '<option value="">Barcha Kafedralar</option>';
            if (!facultyId) {
                departmentFilter.disabled = true;
                return;
            }
            const filtered = allDepartments.filter(d => d.faculty_id == facultyId);
            departmentFilter.disabled = filtered.length === 0;
            filtered.forEach(d => {
                departmentFilter.add(new Option(d.name, d.id));
            });
            if (selectedDepartmentId) {
                departmentFilter.value = selectedDepartmentId;
            }
        }

        function populateUsers(facultyId, departmentId, selectedUserId = null) {
            userFilter.innerHTML = '<option value="">Barcha O\'qituvchilar</option>';
            if (!facultyId) {
                userFilter.disabled = true;
                return;
            }
            let filtered;
            if (departmentId) {
                filtered = allUsers.filter(u => u.department_id == departmentId);
            } else {
                filtered = allUsers.filter(u => u.faculty_id == facultyId);
            }
            userFilter.disabled = filtered.length === 0;
            filtered.forEach(u => {
                userFilter.add(new Option(u.full_name, u.id));
            });
            if (selectedUserId) {
                userFilter.value = selectedUserId;
            }
        }
        
        if (initialFilters.faculty) {
            populateDepartments(initialFilters.faculty, initialFilters.department);
            populateUsers(initialFilters.faculty, initialFilters.department, initialFilters.user);
        }
        
        initTooltips();
    });
    </script>
</body>
</html>
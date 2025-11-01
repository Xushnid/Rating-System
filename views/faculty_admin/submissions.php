<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Fakultet Ishlanmalari - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
     <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container-fluid py-4 px-lg-5">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-journal-text"></i>Fakultet Ishlanmalari</h2>
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
                    <div class="col-md-4">
                        <label for="department_filter" class="form-label">Kafedra</label>
                        <select id="department_filter" class="form-select">
                             <option value="">Barchasi</option>
                            <?php foreach ($departments_in_faculty as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($_GET['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="user_filter" class="form-label">O'qituvchi</label>
                         <select id="user_filter" class="form-select">
                            <option value="">Barchasi</option>
                            <?php foreach ($users_in_faculty as $user): ?>
                                 <option value="<?= $user['id'] ?>" <?= ($_GET['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status_filter" class="form-label">Holati</label>
                         <select id="status_filter" class="form-select">
                            <option value="">Barchasi</option>
                            <option value="pending" <?= ($_GET['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Kutilmoqda</option>
                            <option value="approved" <?= ($_GET['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Tasdiqlangan</option>
                            <option value="rejected" <?= ($_GET['status'] ?? '') == 'rejected' ? 'selected' : '' ?>>Rad etilgan</option>
                        </select>
                    </div>
                </div>

                 <div id="ishlanma-content">
                     <?php 
                     $is_readonly = true;
                     $page_type = 'faculty_admin';
                     $section_code = $active_section_code;
                     include __DIR__ . '/../partials/ishlanma_table_partial.php'; 
                     ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js"></script>
   <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sectionGroups = <?= json_encode($section_groups) ?>;
        // QO'SHILDI VA TUZATILDI: "sections" o'zgaruvchisi bu yerda ham kerak
        const sections = <?= json_encode($sections) ?>;
        
        const departmentFilter = document.getElementById('department_filter');
        
        const userFilter = document.getElementById('user_filter');
        const statusFilter = document.getElementById('status_filter');

        // ======== HOLATNI BOSHQARISH (STATE MANAGEMENT) ========
        const currentURL = new URL(window.location.href);
        const activeGroup = currentURL.searchParams.get('group') || '<?= $active_group_key ?>';
        const activeSection = currentURL.searchParams.get('section') || '<?= $active_section_code ?>';

        // Guruh tabi bosilganda
        document.querySelectorAll('#group-tabs .nav-link').forEach(tab => {
            tab.addEventListener('click', e => {
                e.preventDefault();
                const groupKey = tab.dataset.groupKey;
                const firstSectionKey = sectionGroups[groupKey].sections[0];
                
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('group', groupKey);
                newUrl.searchParams.set('section', firstSectionKey);
                // Filtr holatini saqlab qolish
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
                
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('section', sectionKey);
                 // Filtr holatini saqlab qolish
                newUrl.searchParams.set('department_id', departmentFilter.value);
                newUrl.searchParams.set('user_id', userFilter.value);
                newUrl.searchParams.set('status', statusFilter.value);
                window.location.href = newUrl.href;
            });
        });

        // Filtr o'zgarganda sahifani qayta yuklash
        function handleFilterChange() {
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('department_id', departmentFilter.value);
            newUrl.searchParams.set('user_id', userFilter.value);
            newUrl.searchParams.set('status', statusFilter.value);
            window.location.href = newUrl.href;
        }

        departmentFilter.addEventListener('change', handleFilterChange);
        userFilter.addEventListener('change', handleFilterChange);
        statusFilter.addEventListener('change', handleFilterChange);
    });
    </script>
</body>
</html>
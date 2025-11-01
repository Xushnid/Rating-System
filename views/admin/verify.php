<!DOCTYPE html>
<html lang="uz">
<head>
   <meta charset="UTF-8">
    <title>Ishlanmalarni Tasdiqlash - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        /* Guruh tabidagi kutilayotgan ishlanma indikatori uchun stil */
        .nav-link.has-pending::after {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ffc107; /* Bootstrap warning color */
            display: inline-block;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container-fluid py-4 px-lg-5">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-patch-check"></i>Ishlanmalarni Tasdiqlash</h2>
        </div>
        
        <div id="alertContainer"></div>

        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs nav-tabs-bordered px-3" id="group-tabs">
                    <?php foreach ($section_groups as $g_key => $group): ?>
                        <li class="nav-item">
                            <a href="#" class="nav-link fs-6 fw-medium py-3 <?= $active_group_key === $g_key ? 'active' : '' ?> <?= ($group_pending_status[$g_key] ?? false) ? 'has-pending' : '' ?>" 
                               data-group-key="<?= htmlspecialchars($g_key) ?>">
                                <?= htmlspecialchars($group['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <hr class="m-0">
                <ul class="nav nav-tabs card-header-tabs" id="ishlanma-tabs" role="tablist">
                    <?php 
                    // Faqat aktiv guruhga tegishli bo'limlarni chiqaramiz
                    $sections_in_active_group = $section_groups[$active_group_key]['sections'] ?? [];
                    foreach ($sections_in_active_group as $s_code): 
                        $section = $sections[$s_code];
                    ?>
                        <li class="nav-item" role="presentation">
                            <a href="#" class="nav-link px-4 d-flex align-items-center gap-2 <?= $active_section_code === $s_code ? 'active' : '' ?>" data-section="<?= htmlspecialchars($s_code) ?>" type="button">
                                <?= htmlspecialchars($s_code) ?>
                                <?php if (isset($pending_counts[$s_code]) && $pending_counts[$s_code] > 0): ?>
                                    <span class="badge bg-warning text-dark rounded-pill"><?= $pending_counts[$s_code] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="card-body">
                <h5 class="card-title mb-3 fw-bold" id="section-title"><?= htmlspecialchars($sections[$active_section_code]['name'] ?? 'Bo‘lim') ?></h5>
                
                <div id="ishlanma-content">
   <?php
   // TUZATISH: Partial fayl $section_code o'zgaruvchisini kutmoqda
   $section_code = $active_section_code;
   include __DIR__ . '/../partials/ishlanma_table_partial.php';
   ?> 
</div>
            </div>
        </div>
    </main>
    <?php include __DIR__ . '/../partials/modal_rejection_reason.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js"></script>
    <script>
        // Barcha guruhlar va bo'limlar haqidagi ma'lumotni JS uchun saqlab olamiz
        const sectionGroups = <?= json_encode($section_groups) ?>;
        const sections = <?= json_encode($sections) ?>; /* TUZATILDI: allSections -> sections */
    </script>
    <script src="/assets/js/ishlanma-manager.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const manager = new IshlanmaManager('editItemModal', 'admin');
        const contentArea = document.getElementById('ishlanma-content');

        // ======== HOLATNI BOSHQARISH (STATE MANAGEMENT) UCHUN YANGI BLOK ========
        
        const currentURL = new URL(window.location.href);
        const activeGroup = currentURL.searchParams.get('group') || '<?= $active_group_key ?>';
        const activeSection = currentURL.searchParams.get('section') || '<?= $active_section_code ?>';

        // Sahifa yangilanganda holatni tiklash funksiyasi
        function restoreStateOnReload() {
            // Agar sahifa qayta yuklanayotgan bo'lsa (masalan, o'chirishdan keyin)
            // va sessionStorage'da saqlangan holat bo'lsa
            const storedGroup = sessionStorage.getItem('activeGroup');
            const storedSection = sessionStorage.getItem('activeSection');
            const isReloading = sessionStorage.getItem('isReloading');

            if (isReloading && storedGroup && storedSection) {
                sessionStorage.removeItem('isReloading'); // Qayta yuklash loopini oldini olish
                
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('group', storedGroup);
                newUrl.searchParams.set('section', storedSection);
                
                // Agar joriy URL saqlangan holatdan farq qilsa, kerakli sahifaga o'tkazish
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
                
                // Kelajakdagi sahifa yangilanishi uchun holatni saqlash
                sessionStorage.setItem('activeGroup', groupKey);
                sessionStorage.setItem('activeSection', firstSectionKey);

                // Yangi URL'ga o'tish
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('group', groupKey);
                newUrl.searchParams.set('section', firstSectionKey);
                window.location.href = newUrl.href;
            });
        });

        // Bo'lim tabi bosilganda
        document.querySelectorAll('#ishlanma-tabs .nav-link').forEach(tab => {
            tab.addEventListener('click', e => {
                e.preventDefault();
                const sectionKey = tab.dataset.section;

                // Kelajakdagi sahifa yangilanishi uchun holatni saqlash
                sessionStorage.setItem('activeGroup', activeGroup);
                sessionStorage.setItem('activeSection', sectionKey);
                
                // Yangi URL'ga o'tish
                const newUrl = new URL(window.location.href);
                newUrl.searchParams.set('section', sectionKey);
                window.location.href = newUrl.href;
            });
        });

        // Ishlanma-manager'dan chaqiriladigan global funksiya
        window.refreshTable = () => {
            // Holatni saqlaymiz va sahifani qayta yuklash uchun belgi qo'yamiz
            sessionStorage.setItem('activeGroup', activeGroup);
            sessionStorage.setItem('activeSection', activeSection);
            sessionStorage.setItem('isReloading', 'true');
            location.reload();
        };

        initTooltips();
    });
    </script>
</body>
</html>
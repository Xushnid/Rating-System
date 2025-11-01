<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Mening ishlanmalarim - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container-fluid py-4 px-lg-5">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-collection"></i>Mening Ishlanmalarim</h2>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js"></script>
    <script>
        const sectionGroups = <?= json_encode($section_groups) ?>;
        const sections = <?= json_encode($sections) ?>;
        
        // YANGI: Rejected submissions counts for current user
        let rejectedCounts = {};
    </script>
    <script src="/assets/js/ishlanma-manager.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const manager = new IshlanmaManager('editItemModal', 'user');
        
        // ======== HOLATNI BOSHQARISH (STATE MANAGEMENT) ========
        const currentURL = new URL(window.location.href);
        const activeGroup = currentURL.searchParams.get('group') || '<?= $active_group_key ?>';
        const activeSection = currentURL.searchParams.get('section') || '<?= $active_section_code ?>';

        // ======== REJECTED SUBMISSIONS INDICATORS ========
        async function loadRejectedCounts() {
            console.log('Loading rejected counts...');
            try {
                const response = await fetch('/ajax/user/rejected-counts');
                console.log('Response received:', response.status);
                
                const result = await response.json();
                console.log('Result:', result);
                
                if (result.success) {
                    rejectedCounts = result.data.rejected_counts || {};
                    console.log('Rejected counts loaded:', rejectedCounts);
                    updateRejectedIndicators();
                } else {
                    console.error('Failed to load rejected counts:', result.message);
                }
            } catch (error) {
                console.error('Error loading rejected counts:', error);
            }
        }
        
        function updateRejectedIndicators() {
            console.log('Updating rejected indicators, rejectedCounts:', rejectedCounts);
            
            // Update section tabs with red numbered indicators
            document.querySelectorAll('#ishlanma-tabs .nav-link').forEach(tabLink => {
                const sectionCode = tabLink.dataset.section;
                const existingBadge = tabLink.querySelector('.badge.bg-danger');
                
                console.log(`Section ${sectionCode}: count = ${rejectedCounts[sectionCode] || 0}`);
                
                if (rejectedCounts[sectionCode] && rejectedCounts[sectionCode] > 0) {
                    if (!existingBadge) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-danger text-white rounded-pill ms-1';
                        badge.textContent = rejectedCounts[sectionCode];
                        tabLink.appendChild(badge);
                        console.log(`Added section badge for ${sectionCode}`);
                    } else {
                        existingBadge.textContent = rejectedCounts[sectionCode];
                    }
                } else if (existingBadge) {
                    existingBadge.remove();
                }
            });
            
            // Update group tabs with red dots if they have rejected submissions
            document.querySelectorAll('#group-tabs .nav-link').forEach(groupTabLink => {
                const groupKey = groupTabLink.getAttribute('data-group-key');
                console.log(`Checking group ${groupKey}`);
                
                if (!groupKey || !sectionGroups[groupKey]) {
                    console.log(`Group ${groupKey} not found in sectionGroups`);
                    return;
                }
                
                const groupSections = sectionGroups[groupKey].sections || [];
                console.log(`Group ${groupKey} sections:`, groupSections);
                
                let hasRejected = false;
                for (const sectionCode of groupSections) {
                    if (rejectedCounts[sectionCode] && rejectedCounts[sectionCode] > 0) {
                        hasRejected = true;
                        console.log(`Group ${groupKey} has rejected in section ${sectionCode}`);
                        break;
                    }
                }
                
                const existingDot = groupTabLink.querySelector('span[style*="background-color: #dc3545"]');
                if (hasRejected && !existingDot) {
                    const dot = document.createElement('span');
                    dot.className = 'ms-1';
                    dot.style.cssText = 'width: 10px; height: 10px; background-color: #dc3545; border-radius: 50%; display: inline-block; vertical-align: middle;';
                    groupTabLink.appendChild(dot);
                    console.log(`Added group dot for ${groupKey}`);
                } else if (!hasRejected && existingDot) {
                    existingDot.remove();
                    console.log(`Removed group dot for ${groupKey}`);
                }
            });
        }
        
        // Load rejected counts on page load
        loadRejectedCounts();

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
                window.location.href = newUrl.href;
            });
        });

        window.refreshTable = () => {
            sessionStorage.setItem('activeGroup', activeGroup);
            sessionStorage.setItem('activeSection', activeSection);
            sessionStorage.setItem('isReloading', 'true');
            // Reload rejected indicators too
            loadRejectedCounts();
            location.reload();
        };

        initTooltips();
    });
    </script>
</body>
</html>
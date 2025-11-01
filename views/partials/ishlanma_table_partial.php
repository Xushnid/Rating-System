<?php
// ... (faylning bosh qismi o'zgarishsiz qoladi) ...
$section_config = $sections[$section_code] ?? null;
if (!$section_config) {
    echo '<div class="alert alert-danger">Noto‘g‘ri bo‘lim konfiguratsiyasi.</div>';
    return;
}
$is_admin_view = ($page_type === 'admin_all' || $page_type === 'verify');
$status_icons = [
    'pending' => ['icon' => 'bi-clock-history', 'class' => 'text-warning', 'title' => 'Kutilmoqda'],
    'approved' => ['icon' => 'bi-check-circle-fill', 'class' => 'text-success', 'title' => 'Tasdiqlangan'],
    'rejected' => ['icon' => 'bi-x-circle-fill', 'class' => 'text-danger', 'title' => 'Rad etilgan']
];

// Controller'dan keladigan global o'zgaruvchini ishlatamiz. Agar u mavjud bo'lmasa, xatolikni oldini olish uchun false deb olamiz.
global $is_period_closed;
if (!isset($is_period_closed)) {
    $is_period_closed = false;
}
?>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <?php if ($is_admin_view || in_array(($page_type ?? ''), ['faculty_admin', 'department_admin'])): ?>
                    <th>F.I.Sh</th>
                <?php endif; ?>
                <?php foreach ($section_config['fields'] as $config): if ($config['in_table']): ?>
                    <th><?= htmlspecialchars($config['label']); ?></th>
                <?php endif; endforeach; ?>
                <?php if ($section_config['has_common_fields']): ?>
                    <th>Maqola/Ishlanma nomi</th>
                    <th>Nashr sanasi</th>
                    <th>Mualliflar soni / Ulush</th>
                <?php endif; ?>
                <th>Holati</th>
                <th style="width: 1%; white-space: nowrap;">Amallar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ishlanmalar)): ?>
                <tr>
                    <td colspan="15" class="text-center p-4">Ma'lumotlar topilmadi.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($ishlanmalar as $index => $ishlanma): ?>
                    <?php 
                    $data = json_decode($ishlanma['data'], true); 
                    $status_info = $status_icons[$ishlanma['status']] ?? ['icon' => 'bi-question-circle', 'class' => 'text-secondary', 'title' => 'Noma\'lum'];
                    
                    $title = $status_info['title'];
                    if ($ishlanma['status'] === 'rejected' && !empty($ishlanma['rejection_reason'])) {
                        $title .= ': ' . htmlspecialchars($ishlanma['rejection_reason']);
                    }
                    ?>
                    <tr>
                        <td><?= $index + 1; ?></td>
                        <?php if ($is_admin_view || in_array(($page_type ?? ''), ['faculty_admin', 'department_admin'])): ?>
                            <td><?= htmlspecialchars($ishlanma['user_name']); ?></td>
                        <?php endif; ?>
                        <?php foreach ($section_config['fields'] as $field => $config): if ($config['in_table']): ?>
                            <td><?= htmlspecialchars($data[$field] ?? ''); ?></td>
                        <?php endif; endforeach; ?>
                        <?php if ($section_config['has_common_fields']): ?>
                            <td><?= htmlspecialchars($data['article_name'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($data['publish_date'] ?? ''); ?></td>
                            <td><?= htmlspecialchars($data['authors_count'] ?? 1); ?> / <?= htmlspecialchars($data['share'] ?? 1); ?></td>
                        <?php endif; ?>
                        <td class="text-center">
                            <i class="bi <?= $status_info['icon'] ?> fs-4 <?= $status_info['class'] ?>" 
                               data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $title ?>">
                            </i>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <?php if ($section_config['has_file'] && !empty($ishlanma['file_path'])): ?>
                                    <a href="<?= BASE_URL . htmlspecialchars($ishlanma['file_path']); ?>" target="_blank" class="btn btn-outline-secondary" title="Faylni ko'rish"><i class="bi bi-file-earmark-pdf"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($data['url'])): ?>
    <a href="<?= htmlspecialchars($data['url']); ?>" target="_blank" class="btn btn-outline-info" title="Manbaga o'tish"><i class="bi bi-link-45deg"></i></a>
<?php endif; ?>

                                <?php // O'ZGARTIRILDI: Bu yerda endi $is_readonly emas, $is_period_closed'ga ham tekshiriladi
                                if (empty($is_readonly)): ?>
                                    <?php if ($page_type === 'verify'): ?>
                                        <button class="btn btn-success approve-ishlanma-btn" data-id="<?= $ishlanma['id']; ?>" title="Tasdiqlash" <?= $is_period_closed ? 'disabled' : '' ?>><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-danger reject-ishlanma-btn" data-id="<?= $ishlanma['id']; ?>" title="Rad etish" <?= $is_period_closed ? 'disabled' : '' ?>><i class="bi bi-x-lg"></i></button>
                                    <?php else: ?>
                                        <?php
                                            $can_edit = false;
                                            if ($is_period_closed) {
                                                $can_edit = false; // Yakunlangan davrda tahrirlash mumkin emas
                                                $edit_title = 'Yakunlangan davrni tahrirlab bo\'lmaydi';
                                            } else if (($page_type ?? '') === 'department_admin') {
                                                $can_edit = true;
                                                $edit_title = 'Tahrirlash';
                                            } else if ($ishlanma['status'] !== 'approved') {
                                                $can_edit = true;
                                                $edit_title = 'Tahrirlash';
                                            } else {
                                                $edit_title = 'Tasdiqlangan ishlanmani tahrirlashga ruxsat yo\'q';
                                            }
                                        ?>
                                        <button class="btn btn-outline-primary edit-ishlanma-btn" 
                                                data-id="<?= $ishlanma['id']; ?>" 
                                                title="<?= $edit_title ?>"
                                                <?= !$can_edit ? 'disabled' : '' ?>>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger delete-ishlanma-btn" data-id="<?= $ishlanma['id']; ?>" title="<?= $is_period_closed ? 'Yakunlangan davrdan o\'chirib bo\'lmaydi' : 'O\'chirish' ?>" <?= $is_period_closed ? 'disabled' : '' ?>>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
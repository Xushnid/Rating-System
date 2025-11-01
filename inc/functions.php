<?php
function log_error($message) {
    $log_file = __DIR__ . '/../logs/error.log';
    // Papka mavjudligini tekshirish
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    // Xabarni formatlash
    $formatted_message = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
    // Faylga yozish
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

/**
 * Rol nomini bazadagi ko'rinishidan chiroyli, o'qish uchun qulay holatga o'tkazadi.
 * @param string $role_key - bazadagi rol nomi (masalan, 'superadmin')
 * @return string - Chiroyli nom (masalan, 'Super Admin')
 */
/**
 * Rol nomini va unga mos rang klassini qaytaradi.
 * @param string $role_key - bazadagi rol nomi (masalan, 'superadmin')
 * @return array - ['name' => 'Super Admin', 'class' => 'bg-danger']
 */
function translate_role($role_key) {
    $roles = [
        'superadmin'      => ['name' => 'Super Admin',     'class' => 'bg-danger'],
        'facultyadmin'    => ['name' => 'Fakultet Admini', 'class' => 'bg-primary'],
        'departmentadmin' => ['name' => 'Kafedra Admini',  'class' => 'bg-info'],
        'user'            => ['name' => "O'qituvchi",        'class' => 'bg-success'],
    ];
    
    // Agar rol topilmasa, standart qiymat qaytarish
    if (isset($roles[$role_key])) {
        return $roles[$role_key];
    } else {
        return ['name' => ucfirst($role_key), 'class' => 'bg-secondary'];
    }
}
<?php

namespace App;

class Ishlanma {
    private $db;
    private $config; // O'zgaruvchi nomi o'zgartirildi

    public function __construct(Database $db) {
        $this->db = $db;
        $this->config = include __DIR__ . '/../config/sections.php';
    }

    // YANGI METOD: Guruhlar ro'yxatini qaytaradi
    public function getSectionGroups() {
        return $this->config['groups'] ?? [];
    }

    // Metod nomi o'zgardi va endi konfiguratsiyadan faqat bo'limlarni oladi
    public function getSections() {
        return $this->config['sections'] ?? [];
    }

    // O'ZGARTIRILDI: Endi davr bo'yicha hisoblaydi
    public function getPendingCountsBySection(int $period_id) {
        $query = "SELECT section_code, COUNT(*) as count FROM submissions WHERE status = 'pending' AND period_id = :period_id GROUP BY section_code";
        $stmt = $this->db->query($query, ['period_id' => $period_id]);
        $result = [];
        // O'zgartirildi: getSections() endi to'g'ri ishlaydi
        foreach ($this->getSections() as $code => $section) {
            $result[$code] = 0;
        }
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['section_code']])) {
                $result[$row['section_code']] = (int)$row['count'];
            }
        }
        return $result;
    }


    public function findById($id) {
        return $this->db->query("SELECT * FROM submissions WHERE id = :id", ['id' => $id])->fetch();
    }

    // O'ZGARTIRILDI: Endi davr bo'yicha ishlaydi
    public function findAllByUserId($user_id, $period_id, $section_code = null) {
        $query = "SELECT * FROM submissions WHERE user_id = :user_id AND period_id = :period_id";
        $params = ['user_id' => $user_id, 'period_id' => $period_id];
        if ($section_code) {
            $query .= " AND section_code = :section_code";
            $params['section_code'] = $section_code;
        }
        $query .= " ORDER BY created_at DESC";
        return $this->db->query($query, $params)->fetchAll();
    }

    // O'ZGARTIRILDI: $period_id parametri qo'shildi
    public function findAllForAdmin($section_code = null, $period_id = 0, $status = null, $user_id = null, $faculty_id = null, $department_id = null) {
        $query = "SELECT s.*, u.full_name as user_name FROM submissions s JOIN users u ON s.user_id = u.id";
        $conditions = [];
        $params = [];

        // YANGI SHART: Davr bo'yicha filtrlash
        if ($period_id > 0) {
            $conditions[] = "s.period_id = :period_id";
            $params['period_id'] = $period_id;
        }

        if ($section_code) {
            $conditions[] = "s.section_code = :section_code";
            $params['section_code'] = $section_code;
        }
        if ($status) {
            $conditions[] = "s.status = :status";
            $params['status'] = $status;
        }
        if ($department_id) {
            $conditions[] = "u.department_id = :department_id";
            $params['department_id'] = $department_id;
            if ($user_id) {
                 $conditions[] = "s.user_id = :user_id";
                 $params['user_id'] = $user_id;
            }
        } elseif ($faculty_id) {
            $conditions[] = "u.faculty_id = :faculty_id";
            $params['faculty_id'] = $faculty_id;
            if ($user_id) {
                 $conditions[] = "s.user_id = :user_id";
                 $params['user_id'] = $user_id;
            }
        } elseif ($user_id) {
            $conditions[] = "s.user_id = :user_id";
            $params['user_id'] = $user_id;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY s.created_at DESC";
        return $this->db->query($query, $params)->fetchAll();
    }

    // O'ZGARTIRILDI: Endi davr bo'yicha ishlaydi
    public function findPendingBySection($section_code, int $period_id) {
        $query = "SELECT s.*, u.full_name as user_name FROM submissions s JOIN users u ON s.user_id = u.id WHERE s.section_code = :section_code AND s.status = 'pending' AND s.period_id = :period_id ORDER BY s.created_at DESC";
        return $this->db->query($query, ['section_code' => $section_code, 'period_id' => $period_id])->fetchAll();
    }



    // Bu metod o'rniga getCountsByUserIdForPeriod ishlatiladi, lekin moslashuvchanlik uchun buni ham o'zgartiramiz
    public function getCountsByUserId($user_id, int $period_id) {
        $query = "SELECT status, COUNT(*) as count FROM submissions WHERE user_id = :user_id AND period_id = :period_id GROUP BY status";
        $stmt = $this->db->query($query, ['user_id' => $user_id, 'period_id' => $period_id]);
        $result = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int)$row['count'];
            $result['total'] += (int)$row['count'];
        }
        return $result;
    }
    
    // Bu metod o'rniga getCountsByStatusForPeriod ishlatiladi
    public function getGlobalCountsByStatus(int $period_id) {
        $query = "SELECT status, COUNT(*) as count FROM submissions WHERE period_id = :period_id GROUP BY status";
        $stmt = $this->db->query($query, ['period_id' => $period_id]);
        $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['count'];
            }
        }
        $result['total'] = array_sum($result);
        return $result;
    }

    // O'ZGARTIRILDI: Endi davr bo'yicha ishlaydi
    public function getCountsBySection(int $period_id) {
        $query = "SELECT section_code, COUNT(*) as count FROM submissions WHERE period_id = :period_id GROUP BY section_code";
        $stmt = $this->db->query($query, ['period_id' => $period_id]);
        $result = [];
        foreach ($this->getSections() as $code => $section) {
            $result[$code] = 0;
        }
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['section_code']])) {
                $result[$row['section_code']] = (int)$row['count'];
            }
        }
        return $result;
    }
    
    // O'ZGARTIRILDI: Endi davr bo'yicha ishlaydi
    public function findAllForFaculty($faculty_id, $period_id, $filters = []) {
        $query = "SELECT s.*, u.full_name as user_name, u.faculty_id 
                  FROM submissions s 
                  JOIN users u ON s.user_id = u.id";
        $conditions = ["u.faculty_id = :faculty_id", "s.period_id = :period_id"];
        $params = ['faculty_id' => $faculty_id, 'period_id' => $period_id];
        if (!empty($filters['status'])) {
            $conditions[] = "s.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $conditions[] = "s.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = "u.department_id = :department_id";
            $params['department_id'] = $filters['department_id'];
        }
        if (!empty($filters['section_code'])) {
            $conditions[] = "s.section_code = :section_code";
            $params['section_code'] = $filters['section_code'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY s.created_at DESC";
        return $this->db->query($query, $params)->fetchAll();
    }
    
	
	
	/**
     * Berilgan fakultetning berilgan davrdagi ishlanmalarini status bo'yicha sanab beradi.
     * @param int $facultyId
     * @param int $periodId
     * @return array
     */
    public function getCountsInFacultyForPeriod(int $facultyId, int $periodId): array
    {
        $query = "SELECT s.status, COUNT(*) as count 
                  FROM submissions s
                  JOIN users u ON s.user_id = u.id
                  WHERE u.faculty_id = :faculty_id AND s.period_id = :period_id
                  GROUP BY s.status";
        $stmt = $this->db->query($query, ['faculty_id' => $facultyId, 'period_id' => $periodId]);
        
        $result = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['count'];
                $result['total'] += (int)$row['count'];
            }
        }
        return $result;
    }
	
	
	/**
     * Berilgan kafedraning berilgan davrdagi ishlanmalarini status bo'yicha sanab beradi.
     * @param int $departmentId
     * @param int $periodId
     * @return array
     */
    public function getCountsInDepartmentForPeriod(int $departmentId, int $periodId): array
    {
        $query = "SELECT s.status, COUNT(*) as count 
                  FROM submissions s
                  JOIN users u ON s.user_id = u.id
                  WHERE u.department_id = :department_id AND s.period_id = :period_id
                  GROUP BY s.status";
        $stmt = $this->db->query($query, ['department_id' => $departmentId, 'period_id' => $periodId]);
        
        $result = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['count'];
                $result['total'] += (int)$row['count'];
            }
        }
        return $result;
    }
	
	
    // O'ZGARTIRILDI: Endi davr bo'yicha ishlaydi
    public function getPendingCountsBySectionInDepartment($department_id, int $period_id) {
        $query = "SELECT s.section_code, COUNT(*) as count 
                  FROM submissions s
                  JOIN users u ON s.user_id = u.id
                  WHERE u.department_id = :department_id AND s.status = 'pending' AND s.period_id = :period_id
                  GROUP BY s.section_code";
        $stmt = $this->db->query($query, ['department_id' => $department_id, 'period_id' => $period_id]);
        
        $result = [];
        foreach ($this->getSections() as $code => $section) {
            $result[$code] = 0;
        }
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['section_code']])) {
                $result[$row['section_code']] = (int)$row['count'];
            }
        }
        return $result;
    }

    // O'ZGARTIRILDI: Endi davr bo'yicha ishlaydi
    public function findAllForDepartment($department_id, $period_id, $filters = []) {
        $query = "SELECT s.*, u.full_name as user_name 
                  FROM submissions s 
                  JOIN users u ON s.user_id = u.id";
        $conditions = ["u.department_id = :department_id", "s.period_id = :period_id"];
        $params = ['department_id' => $department_id, 'period_id' => $period_id];
        if (!empty($filters['status'])) {
            $conditions[] = "s.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['user_id'])) {
            $conditions[] = "s.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['section_code'])) {
            $conditions[] = "s.section_code = :section_code";
            $params['section_code'] = $filters['section_code'];
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        $query .= " ORDER BY s.created_at DESC";
        return $this->db->query($query, $params)->fetchAll();
    }
    
    // O'ZGARTIRILDI: Endi davr bo'yicha ishlaydi
    public function findPendingInDepartment($department_id, $period_id, $section_code = null) {
        $query = "SELECT s.*, u.full_name as user_name 
                  FROM submissions s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE u.department_id = :department_id AND s.status = 'pending' AND s.period_id = :period_id";
        $params = ['department_id' => $department_id, 'period_id' => $period_id];

        if ($section_code) {
            $query .= " AND s.section_code = :section_code";
            $params['section_code'] = $section_code;
        }
        
        $query .= " ORDER BY s.created_at DESC";
        return $this->db->query($query, $params)->fetchAll();
    }

    /* Qolgan metodlar (create, update, delete, getCountsByStatusForPeriod va hokazo)
        o'zgarishsiz qoladi, chunki ular allaqachon to'g'ri ishlaydi yoki
        davrga bevosita bog'liq emas (bitta yozuv ustida amal bajaradi).
    */

    // ----- O'zgarishsiz qoladigan metodlar -----

    public function create($data, $file = null, $period_id = null) {
        $section_code = $data['section_code'];
        $section = $this->getSections()[$section_code]; // O'zgartirildi
        $json_data = $this->prepareJsonData($data, $section);
        $file_path = $this->handleFileUpload($section, $file, $data['full_name']);
        $stmt = $this->db->query(
        "INSERT INTO submissions (user_id, section_code, period_id, full_name, data, status, file_path) VALUES (:user_id, :section_code, :period_id, :full_name, :data, 'pending', :file_path)",
        [
            'user_id' => $data['user_id'],
            'section_code' => $section_code,
            'period_id' => $period_id,
            'full_name' => $data['full_name'],
            'data' => json_encode($json_data),
            'file_path' => $file_path,
        ]
    );
        return $stmt->rowCount() > 0;
    }

  // YANGILANDI: Tranzaksiya mantig'i bilan
    public function update($id, $user_id, $is_admin, $data, $file = null) {
        $submission = $this->findById($id);
        if (!$submission) {
            return false;
        }

        if (!$is_admin && $submission['user_id'] != $user_id) {
            return false;
        }

        $pdo = $this->db->getPdo();

        try {
            $pdo->beginTransaction();

            $section_code = $submission['section_code'];
            $section = $this->getSections()[$section_code];
            $json_data = $this->prepareJsonData($data, $section);
            $file_path = $submission['file_path'];

            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                if ($file_path && file_exists(__DIR__ . '/../' . $file_path)) {
                    unlink(__DIR__ . '/../' . $file_path);
                }
                $file_path = $this->handleFileUpload($section, $file, $submission['full_name']);
            }

            // 1-operatsiya: Asosiy ma'lumotlarni yangilash
            $stmt = $pdo->prepare("UPDATE submissions SET data = :data, file_path = :file_path WHERE id = :id");
            $stmt->execute([
                'id' => $id,
                'data' => json_encode($json_data),
                'file_path' => $file_path,
            ]);

            $was_updated = $stmt->rowCount() > 0;
            $file_was_updated = ($submission['file_path'] !== $file_path);

            // 2-operatsiya (shartli): Statusni 'rejected' dan 'pending' ga o'tkazish
            if (($was_updated || $file_was_updated) && $submission['status'] === 'rejected') {
                $statusStmt = $pdo->prepare("UPDATE submissions SET status = 'pending', rejection_reason = NULL WHERE id = :id");
                $statusStmt->execute(['id' => $id]);
            }

            // Barcha operatsiyalar muvaffaqiyatli bo'lsa, o'zgarishlarni tasdiqlash
            $pdo->commit();

            return $was_updated || $file_was_updated;

        } catch (\Exception $e) {
            // Agar biror xatolik bo'lsa, barcha o'zgarishlarni bekor qilish
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_error("Submission update transaction failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($id, $status, $reason = null) {
        if (!in_array($status, ['approved', 'rejected', 'pending'])) {
            return false;
        }

        $params = ['id' => $id, 'status' => $status];
        if ($status === 'rejected') {
            $sql = "UPDATE submissions SET status = :status, rejection_reason = :reason WHERE id = :id";
            $params['reason'] = $reason;
        } else {
            $sql = "UPDATE submissions SET status = :status, rejection_reason = NULL WHERE id = :id";
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete($id, $user_id, $is_admin) {
        $submission = $this->findById($id);
        if (!$submission) return false;

        if (!$is_admin && $submission['user_id'] != $user_id) {
            return false;
        }

        if ($submission['file_path'] && file_exists(__DIR__ . '/../' . $submission['file_path'])) {
            unlink(__DIR__ . '/../' . $submission['file_path']);
        }
        
        $stmt = $this->db->query("DELETE FROM submissions WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    private function prepareJsonData($data, $section) {
        $json_data = [];
        
        // Debug logging for section 2.6.1
        $section_code = array_search($section, $this->getSections(), true);
        if ($section_code === '2.6.1') {
            error_log("=== prepareJsonData Debug for Section 2.6.1 ===");
            error_log("Input data: " . print_r($data, true));
            error_log("Section config: " . print_r($section, true));
            error_log("has_common_fields: " . ($section['has_common_fields'] ? 'true' : 'false'));
        }
        
        // First, handle common fields if section has them
        if ($section['has_common_fields']) {
            $json_data['article_name'] = $data['article_name'] ?? '';
            $json_data['publish_date'] = $data['publish_date'] ?? '';
            $json_data['authors_count'] = $data['authors_count'] ?? 1;
            $json_data['share'] = $data['share'] ?? 1;
            $json_data['url'] = $data['url'] ?? '';
            
            if ($section_code === '2.6.1') {
                error_log("After processing common fields: " . print_r($json_data, true));
            }
        }
        
        // Then, process section-specific fields (these will override common fields if names conflict)
        foreach ($section['fields'] as $field => $config) {
            $old_value = $json_data[$field] ?? 'NOT_SET';
            $new_value = $data[$field] ?? '';
            $json_data[$field] = $new_value;
            
            if ($section_code === '2.6.1') {
                error_log("Field '$field': Old='$old_value', New='$new_value', Final='{$json_data[$field]}'" );
            }
        }
        
        if ($section_code === '2.6.1') {
            error_log("Final prepared data: " . print_r($json_data, true));
            
            // Check specific problematic fields
            $problemFields = ['publish_date', 'article_name', 'url'];
            foreach ($problemFields as $field) {
                $inputValue = $data[$field] ?? 'MISSING_FROM_INPUT';
                $outputValue = $json_data[$field] ?? 'MISSING_FROM_OUTPUT';
                $status = ($inputValue === $outputValue) ? 'OK' : 'MISMATCH';
                error_log("Problem field check '$field': Input='$inputValue', Output='$outputValue', Status=$status");
            }
        }
        
        return $json_data;
    }
    
    private function handleFileUpload($section, $file, $user_full_name) {
        if (!$section['has_file'] || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $section_code = array_search($section, $this->getSections(), true); // O'zgartirildi
        if ($section_code === false) {
            $section_code = 'unknown';
        }
        
        $relative_upload_dir = 'uploads/' . $section_code . '/';
        $absolute_upload_dir = __DIR__ . '/../' . $relative_upload_dir;
        if (!is_dir($absolute_upload_dir)) {
            mkdir($absolute_upload_dir, 0755, true);
        }
        
        $base_name = $this->transliterate($user_full_name);
        $timestamp = time();
        $new_filename = $base_name . '_' . $timestamp . '.pdf';
        
        $file_path = $relative_upload_dir . $new_filename;
        if (move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $file_path)) {
            return $file_path;
        }
        return null;
    }
    
    private function transliterate($text) {
        $cyr = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я','ў','Ў','ғ','Ғ','қ','Қ','ҳ','Ҳ',' ','\''];
        $lat = ['a','b','v','g','d','e','yo','j','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','ts','ch','sh','shch','','i','','e','yu','ya','A','B','V','G','D','E','Yo','J','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','H','Ts','Ch','Sh','Shch','','I','','E','Yu','Ya','u','U','g','G','q','Q','h','H','_',''];
        $text = str_replace($cyr, $lat, $text);
        return strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $text));
    }
    
    public function getCountsInFaculty($faculty_id) {
        $query = "SELECT s.status, COUNT(*) as count 
                  FROM submissions s
                  JOIN users u ON s.user_id = u.id
                  WHERE u.faculty_id = :faculty_id 
                   GROUP BY s.status";
        $stmt = $this->db->query($query, ['faculty_id' => $faculty_id]);
        $result = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['count'];
                $result['total'] += (int)$row['count'];
            }
        }
        return $result;
    }

    // ====================================================
    // KAFEDRA ADMINI UCHUN YANGI METODLAR
    // ====================================================
    public function getCountsInDepartment($department_id) {
        $query = "SELECT s.status, COUNT(*) as count 
                  FROM submissions s
                  JOIN users u ON s.user_id = u.id
                   WHERE u.department_id = :department_id 
                  GROUP BY s.status";
        $stmt = $this->db->query($query, ['department_id' => $department_id]);
        
        $result = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['count'];
                $result['total'] += (int)$row['count'];
            }
        }
        return $result;
    }

	public function getSubmissionCountsByFaculty() {
        $sql = "SELECT f.name as faculty_name, COUNT(s.id) as count 
                FROM submissions s 
                JOIN users u ON s.user_id = u.id
                JOIN faculties f ON u.faculty_id = f.id
                WHERE u.faculty_id IS NOT NULL
                GROUP BY f.id, f.name 
                ORDER BY count DESC";
        return $this->db->query($sql)->fetchAll();
    }
	
	
	
	
	/**
     * Bitta ishlanmaning qiymatini (ballini) uning bo'limi uchun belgilangan qoidaga asosan hisoblaydi.
     * @param array $submission Ishlanma ma'lumotlari
     * @return float Hisoblangan qiymat
     */
    public function calculateSubmissionValue(array $submission): float
    {
        $sectionCode = $submission['section_code'];
        $sectionConfig = $this->getSections()[$sectionCode] ?? null; // O'zgartirildi

        if (!$sectionConfig || !isset($sectionConfig['score_calculation'])) {
            return 0; // Agar qoida topilmasa, 0 qaytaramiz
        }

        $rule = $sectionConfig['score_calculation'];
        $data = json_decode($submission['data'], true);

        switch ($rule['method']) {
            case 'count':
                return (float)($rule['value'] ?? 1);
            case 'share':
                return (float)($data['share'] ?? 0);
            case 'field_value':
                $fieldName = $rule['field'] ?? '';
                return (float)($data[$fieldName] ?? 0);

            default:
                return 0;
        }
    }
	
	
	
	/**
     * Berilgan davr uchun ishlanmalarni status bo'yicha sanab beradi.
     * @param int $periodId
     * @return array
     */
    public function getCountsByStatusForPeriod(int $periodId): array
    {
        $query = "SELECT status, COUNT(*) as count FROM submissions WHERE period_id = :period_id GROUP BY status";
        $stmt = $this->db->query($query, ['period_id' => $periodId]);
        $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['count'];
                $result['total'] += (int)$row['count'];
            }
        }
        return $result;
    }

    /**
     * Berilgan davr uchun fakultetlar kesimida ishlanmalar sonini hisoblaydi.
     * @param int $periodId
     * @return array
     */
    public function getSubmissionCountsByFacultyForPeriod(int $periodId): array
    {
        $sql = "SELECT f.name as faculty_name, COUNT(s.id) as count 
                FROM submissions s 
                JOIN users u ON s.user_id = u.id
                 JOIN faculties f ON u.faculty_id = f.id
                WHERE u.faculty_id IS NOT NULL AND s.period_id = :period_id
                GROUP BY f.id, f.name 
                ORDER BY count DESC";
        return $this->db->query($sql, ['period_id' => $periodId])->fetchAll();
 
   }
   
   
   
    /**
     * Berilgan foydalanuvchining berilgan davrdagi ishlanmalarini status bo'yicha sanab beradi.
     * @param int $userId
     * @param int $periodId
     * @return array
     */
    public function getCountsByUserIdForPeriod(int $userId, int $periodId): array
    {
        $query = "SELECT status, COUNT(*) as count 
                  FROM submissions 
                  WHERE user_id = :user_id AND period_id = :period_id 
                   GROUP BY status";
        $stmt = $this->db->query($query, ['user_id' => $userId, 'period_id' => $periodId]);
        
        $result = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['status']])) {
                $result[$row['status']] = (int)$row['count'];
                $result['total'] += (int)$row['count'];
            }
        }
        return $result;
    }
    
    // ====================================================
    // NOTIFICATION INDICATOR METHODS
    // ====================================================
    
    /**
     * SuperAdmin va DepartmentAdmin uchun pending submissions borligini tekshiradi
     * @param string $role User role (superadmin, departmentadmin)
     * @param int $departmentId Department ID (departmentadmin uchun)
     * @param int $periodId Period ID
     * @return bool
     */
    public function hasPendingSubmissionsForAdmin(string $role, int $departmentId = null, int $periodId = 0): bool
    {
        error_log("hasPendingSubmissionsForAdmin called - Role: {$role}, Dept: {$departmentId}, Period: {$periodId}");
        
        if ($role === 'superadmin') {
            // SuperAdmin uchun barcha pending submissions
            $query = "SELECT COUNT(*) as count FROM submissions WHERE status = 'pending' AND period_id = :period_id";
            $params = ['period_id' => $periodId];
        } elseif ($role === 'departmentadmin' && $departmentId) {
            // DepartmentAdmin uchun faqat o'z kafedrasidagi pending submissions
            $query = "SELECT COUNT(*) as count 
                      FROM submissions s
                      JOIN users u ON s.user_id = u.id
                      WHERE s.status = 'pending' AND u.department_id = :department_id AND s.period_id = :period_id";
            $params = ['department_id' => $departmentId, 'period_id' => $periodId];
        } else {
            error_log("hasPendingSubmissionsForAdmin - Invalid role or missing department");
            return false;
        }
        
        $result = $this->db->query($query, $params)->fetch();
        $count = isset($result['count']) ? (int)$result['count'] : 0;
        $hasPending = $count > 0;
        
        error_log("hasPendingSubmissionsForAdmin - Query result count: {$count}, Has pending: " . ($hasPending ? 'true' : 'false'));
        return $hasPending;
    }
    
    /**
     * User uchun rejected submissions borligini tekshiradi
     * @param int $userId User ID
     * @param int $periodId Period ID
     * @return bool
     */
    public function hasRejectedSubmissionsForUser(int $userId, int $periodId = 0): bool
    {
        error_log("hasRejectedSubmissionsForUser called - User: {$userId}, Period: {$periodId}");
        
        $query = "SELECT COUNT(*) as count FROM submissions WHERE user_id = :user_id AND status = 'rejected' AND period_id = :period_id";
        $result = $this->db->query($query, ['user_id' => $userId, 'period_id' => $periodId])->fetch();
        
        $count = isset($result['count']) ? (int)$result['count'] : 0;
        $hasRejected = $count > 0;
        
        error_log("hasRejectedSubmissionsForUser - Query result count: {$count}, Has rejected: " . ($hasRejected ? 'true' : 'false'));
        return $hasRejected;
    }
    
    /**
     * User uchun bo'limlar bo'yicha rejected submissions sonini qaytaradi
     * @param int $userId User ID
     * @param int $periodId Period ID
     * @return array
     */
    public function getRejectedCountsBySectionForUser(int $userId, int $periodId = 0): array
    {
        error_log("getRejectedCountsBySectionForUser called - User: {$userId}, Period: {$periodId}");
        
        $query = "SELECT section_code, COUNT(*) as count 
                  FROM submissions 
                  WHERE user_id = :user_id AND status = 'rejected' AND period_id = :period_id 
                  GROUP BY section_code";
        $stmt = $this->db->query($query, ['user_id' => $userId, 'period_id' => $periodId]);
        
        $result = [];
        foreach ($this->getSections() as $code => $section) {
            $result[$code] = 0;
        }
        foreach ($stmt->fetchAll() as $row) {
            if (isset($result[$row['section_code']])) {
                $result[$row['section_code']] = (int)$row['count'];
            }
        }
        
        error_log("getRejectedCountsBySectionForUser - Result: " . json_encode($result));
        return $result;
    }
    
    /**
     * Kafedra admini uchun kafedrasidagi rad etilgan ishlanmalar sonini bo'limlar bo'yicha qaytaradi
     * @param int $departmentId Department ID
     * @param int $periodId Period ID
     * @return array
     */
    public function getRejectedCountsBySectionForDepartment(int $departmentId, int $periodId = 0): array
    {
        error_log("getRejectedCountsBySectionForDepartment called - Department: {$departmentId}, Period: {$periodId}");
        
        $query = "SELECT s.section_code, COUNT(*) as count 
                  FROM submissions s
                  JOIN users u ON s.user_id = u.id
                  WHERE u.department_id = :department_id AND s.status = 'rejected' AND s.period_id = :period_id 
                  GROUP BY s.section_code";
        $stmt = $this->db->query($query, ['department_id' => $departmentId, 'period_id' => $periodId]);
        
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[] = [
                'section_code' => $row['section_code'],
                'count' => (int)$row['count']
            ];
        }
        
        error_log("getRejectedCountsBySectionForDepartment - Result: " . json_encode($result));
        return $result;
    }
}
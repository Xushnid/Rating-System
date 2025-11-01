<?php

namespace App;

class Target {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    /**
     * Berilgan davr uchun barcha kafedra rejalarini oladi.
     */
    public function getDepartmentTargetsForPeriod(int $periodId): array
    {
        $stmt = $this->db->query(
            "SELECT department_id, section_code, target_value FROM department_section_targets WHERE period_id = :period_id",
            ['period_id' => $periodId]
        );
        $targets = [];
        foreach ($stmt->fetchAll() as $row) {
            $targets[$row['department_id']][$row['section_code']] = $row['target_value'];
        }
        return $targets;
    }

    /**
     * Kafedralar uchun reja-qiymatlarni ommaviy saqlaydi (yoki yangilaydi).
     * YANGILANDI: Tranzaksiya mantig'i bilan.
     */
    public function saveDepartmentTargets(int $periodId, array $targets): bool
    {
        if (empty($targets)) {
            return true;
        }

        $pdo = $this->db->getPdo();

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO department_section_targets (period_id, department_id, section_code, target_value) VALUES ";
            $params = [];
            $values = [];

            foreach ($targets as $departmentId => $sections) {
                foreach ($sections as $sectionCode => $value) {
                    $targetValue = is_numeric($value) ? (float)$value : 0;
                    $values[] = "(?, ?, ?, ?)";
                    $params[] = $periodId;
                    $params[] = $departmentId;
                    $params[] = $sectionCode;
                    $params[] = $targetValue;
                }
            }
            
            if (empty($values)) {
                $pdo->commit();
                return true;
            }

            $sql .= implode(', ', $values);
            $sql .= " ON DUPLICATE KEY UPDATE target_value = VALUES(target_value)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $pdo->commit();
            
            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_error("Department target save transaction failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Berilgan davrda, bir kafedradagi barcha o'qituvchilarning shaxsiy rejalarini oladi.
     */
    public function getUserTargetsForDepartment(int $periodId, int $departmentId): array
    {
        $stmt = $this->db->query(
            "SELECT ust.user_id, ust.section_code, ust.target_value 
             FROM user_section_targets ust
             JOIN users u ON ust.user_id = u.id
             WHERE ust.period_id = :period_id AND u.department_id = :department_id",
             ['period_id' => $periodId, 'department_id' => $departmentId]
        );
        $targets = [];
        foreach ($stmt->fetchAll() as $row) {
            $targets[$row['user_id']][$row['section_code']] = $row['target_value'];
        }
        return $targets;
    }

    /**
     * O'qituvchilar uchun reja-qiymatlarni ommaviy saqlaydi (yoki yangilaydi).
     * YANGILANDI: Tranzaksiya mantig'i bilan.
     */
    public function saveUserTargets(int $periodId, array $targets): bool
    {
        if (empty($targets)) {
            return true;
        }
        
        $pdo = $this->db->getPdo();

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO user_section_targets (period_id, user_id, section_code, target_value) VALUES ";
            $params = [];
            $values = [];

            foreach ($targets as $userId => $sections) {
                foreach ($sections as $sectionCode => $value) {
                    $targetValue = is_numeric($value) ? (float)$value : 0;
                    $values[] = "(?, ?, ?, ?)";
                    $params[] = $periodId;
                    $params[] = $userId;
                    $params[] = $sectionCode;
                    $params[] = $targetValue;
                }
            }
            
            if (empty($values)) {
                $pdo->commit();
                return true;
            }

            $sql .= implode(', ', $values);
            $sql .= " ON DUPLICATE KEY UPDATE target_value = VALUES(target_value)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $pdo->commit();
            
            return $stmt->rowCount() > 0;

        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_error("User target save transaction failed: " . $e->getMessage());
            return false;
        }
    }
	
/**
     * Foydalanuvchining berilgan davrdagi rejalari va ularning bajarilishini hisoblab beradi.
     * (Yaxshilangan versiya)
     * @param int $userId
     * @param int $periodId
     * @param \App\Ishlanma $ishlanmaRepo - Ishlanmalar bilan ishlash uchun repozitoriy
     * @return array
     */
    public function getUserProgress(int $userId, int $periodId, \App\Ishlanma $ishlanmaRepo): array
    {
        // 1. Tizimdagi barcha bo'limlar ro'yxatini olish
        $allSections = $ishlanmaRepo->getSections();
        $progressData = [];

        // 2. Har bir bo'lim uchun standart "reja=0, bajarildi=0" qiymatlarini yaratish
        // Bu o'qituvchiga hali reja biriktirilmagan bo'limlar ham ko'rinib turishi uchun kerak
        foreach ($allSections as $code => $section) {
             $progressData[$code] = [
                'target' => 0,
                'accomplished' => 0,
            ];
        }

        // 3. Foydalanuvchining shu davr uchun aniq rejalarini bazadan olish va yuqoridagi massivni yangilash
        $stmt = $this->db->query(
            "SELECT section_code, target_value FROM user_section_targets WHERE user_id = :user_id AND period_id = :period_id",
            ['user_id' => $userId, 'period_id' => $periodId]
        );
        
        $hasTargets = false;
        foreach ($stmt->fetchAll() as $row) {
            if (isset($progressData[$row['section_code']])) {
                $progressData[$row['section_code']]['target'] = (float)$row['target_value'];
                $hasTargets = true;
            }
        }
        
        // Agar o'qituvchiga umuman reja biriktirilmagan bo'lsa, bo'sh massiv qaytaramiz
        if (!$hasTargets) {
            return [];
        }

        // 4. Foydalanuvchining shu davrdagi TASDIQLANGAN ishlanmalarini olish
        $approvedSubmissions = $this->db->query(
            "SELECT * FROM submissions WHERE user_id = :user_id AND period_id = :period_id AND status = 'approved'",
            ['user_id' => $userId, 'period_id' => $periodId]
        )->fetchAll();

        // 5. Har bir ishlanmaning qiymatini hisoblash va yig'ib borish
        foreach ($approvedSubmissions as $submission) {
            $sectionCode = $submission['section_code'];
            if (isset($progressData[$sectionCode])) {
                $value = $ishlanmaRepo->calculateSubmissionValue($submission);
                $progressData[$sectionCode]['accomplished'] += $value;
            }
        }
        
        return $progressData;
    }
	
	
	
	/**
     * Kafedraning berilgan davrdagi umumiy va har bir o'qituvchi bo'yicha progressini hisoblab beradi.
     * @param int $departmentId
     * @param int $periodId
     * @param \App\Ishlanma $ishlanmaRepo
     * @param \App\User $userRepo
     * @return array
     */
    public function getDepartmentProgress(int $departmentId, int $periodId, \App\Ishlanma $ishlanmaRepo, \App\User $userRepo): array
    {
        // 1. Kafedradagi barcha o'qituvchilarni olish
        $users = $userRepo->findUsersByDepartment($departmentId);
        if (empty($users)) {
            return []; // Agar o'qituvchi bo'lmasa, bo'sh massiv qaytaramiz
        }
        $userIds = array_map(function($u) { return $u['id']; }, $users);

        // 2. Barcha o'qituvchilar uchun rejalarini bir so'rovda olish
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->query(
            "SELECT user_id, section_code, target_value FROM user_section_targets WHERE period_id = ? AND user_id IN ($placeholders)",
            array_merge([$periodId], $userIds)
        );
        $userTargets = [];
        foreach ($stmt->fetchAll() as $row) {
            $userTargets[$row['user_id']][$row['section_code']] = (float)$row['target_value'];
        }

        // 3. Barcha o'qituvchilarning tasdiqlangan ishlanmalarini bir so'rovda olish
        $approvedSubmissions = $this->db->query(
            "SELECT * FROM submissions WHERE period_id = ? AND status = 'approved' AND user_id IN ($placeholders)",
            array_merge([$periodId], $userIds)
        )->fetchAll();

        // 4. Natijalarni qayta ishlash uchun massiv tayyorlash
        $result = ['users' => [], 'summary' => ['target' => 0, 'accomplished' => 0]];
        foreach ($users as $user) {
            $result['users'][$user['id']] = [
                'full_name' => $user['full_name'],
                'progress' => []
            ];
            if (isset($userTargets[$user['id']])) {
                foreach ($userTargets[$user['id']] as $sectionCode => $targetValue) {
                    $result['users'][$user['id']]['progress'][$sectionCode] = [
                        'target' => $targetValue,
                        'accomplished' => 0
                    ];
                }
            }
        }

        // 5. Har bir ishlanma qiymatini hisoblash va o'qituvchining hisobiga qo'shish
        foreach ($approvedSubmissions as $submission) {
            $userId = $submission['user_id'];
            $sectionCode = $submission['section_code'];
            
            // Agar shu o'qituvchi va shu bo'lim uchun reja belgilangan bo'lsa
            if (isset($result['users'][$userId]['progress'][$sectionCode])) {
                $value = $ishlanmaRepo->calculateSubmissionValue($submission);
                $result['users'][$userId]['progress'][$sectionCode]['accomplished'] += $value;
            }
        }
        
        // 6. Kafedra bo'yicha umumiy natijani hisoblash (bo'limlar kesimida)
$result['summary'] = [];
foreach ($result['users'] as $userData) {
    foreach ($userData['progress'] as $sectionCode => $progress) {
        if (!isset($result['summary'][$sectionCode])) {
            $result['summary'][$sectionCode] = ['target' => 0, 'accomplished' => 0];
        }
        $result['summary'][$sectionCode]['target'] += $progress['target'];
        $result['summary'][$sectionCode]['accomplished'] += $progress['accomplished'];
    }
}

        return $result;
    }
	
	/**
     * Fakultetning berilgan davrdagi umumiy va har bir kafedra bo'yicha progressini hisoblab beradi.
     * @param int $facultyId
     * @param int $periodId
     * @param \App\Ishlanma $ishlanmaRepo
     * @param \App\Department $departmentRepo
     * @return array
     */
    public function getFacultyProgress(int $facultyId, int $periodId, \App\Ishlanma $ishlanmaRepo, \App\Department $departmentRepo): array
    {
        // 1. Fakultetdagi barcha kafedralarni olish
        $departments = $departmentRepo->findAllByFacultyId($facultyId);
        if (empty($departments)) {
            return [];
        }
        $departmentIds = array_map(fn($d) => $d['id'], $departments);
        $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));

        // 2. Barcha kafedralar uchun rejalarini bir so'rovda olish
        $stmt = $this->db->query(
            "SELECT department_id, section_code, target_value FROM department_section_targets WHERE period_id = ? AND department_id IN ($placeholders)",
            array_merge([$periodId], $departmentIds)
        );
        $departmentTargets = [];
        foreach ($stmt->fetchAll() as $row) {
            $departmentTargets[$row['department_id']][$row['section_code']] = (float)$row['target_value'];
        }

        // 3. Fakultetdagi barcha o'qituvchilarning tasdiqlangan ishlanmalarini olish
        $approvedSubmissions = $this->db->query(
            "SELECT s.*, u.department_id FROM submissions s JOIN users u ON s.user_id = u.id WHERE s.period_id = ? AND s.status = 'approved' AND u.faculty_id = ?",
            [$periodId, $facultyId]
        )->fetchAll();

        // 4. Natijalarni qayta ishlash uchun massiv tayyorlash
        $result = ['departments' => [], 'summary' => []];
        foreach ($departments as $department) {
            $deptId = $department['id'];
            $result['departments'][$deptId] = [
                'name' => $department['name'],
                'progress' => []
            ];
            // Har bir bo'lim uchun reja va bajarilishni 0 qilib belgilab olamiz
            foreach ($ishlanmaRepo->getSections() as $sectionCode => $section) {
                 $result['departments'][$deptId]['progress'][$sectionCode] = [
                    'target' => $departmentTargets[$deptId][$sectionCode] ?? 0,
                    'accomplished' => 0
                ];
            }
        }
        
        // 5. Har bir ishlanma qiymatini hisoblash va tegishli kafedra hisobiga qo'shish
        foreach ($approvedSubmissions as $submission) {
            $deptId = $submission['department_id'];
            $sectionCode = $submission['section_code'];

            if (isset($result['departments'][$deptId]['progress'][$sectionCode])) {
                $value = $ishlanmaRepo->calculateSubmissionValue($submission);
                $result['departments'][$deptId]['progress'][$sectionCode]['accomplished'] += $value;
            }
        }

        // 6. Fakultet bo'yicha umumiy natijani (summary) hisoblash
        foreach ($result['departments'] as $deptData) {
            foreach ($deptData['progress'] as $sectionCode => $progress) {
                if (!isset($result['summary'][$sectionCode])) {
                    $result['summary'][$sectionCode] = ['target' => 0, 'accomplished' => 0];
                }
                $result['summary'][$sectionCode]['target'] += $progress['target'];
                $result['summary'][$sectionCode]['accomplished'] += $progress['accomplished'];
            }
        }

        return $result;
    }
	
	
	/**
     * Butun tizim bo'yicha progressni fakultetlar va kafedralar kesimida hisoblab beradi.
     */
    public function getSystemWideProgress(int $periodId, \App\Ishlanma $ishlanmaRepo, \App\Faculty $facultyRepo, \App\Department $departmentRepo): array
    {
        // 1. Barcha fakultet va kafedralarni olish
        $faculties = $facultyRepo->findAll();
        $departments = $departmentRepo->findAll();
        if (empty($faculties) || empty($departments)) return [];

        $departmentIds = array_map(fn($d) => $d['id'], $departments);
        $placeholders = implode(',', array_fill(0, count($departmentIds), '?'));

        // 2. Barcha kafedralar uchun rejalarini bir so'rovda olish
        $stmt = $this->db->query(
            "SELECT department_id, section_code, target_value FROM department_section_targets WHERE period_id = ? AND department_id IN ($placeholders)",
            array_merge([$periodId], $departmentIds)
        );
        $departmentTargets = [];
        foreach ($stmt->fetchAll() as $row) {
            $departmentTargets[$row['department_id']][$row['section_code']] = (float)$row['target_value'];
        }

        // 3. Barcha tasdiqlangan ishlanmalarni bir so'rovda olish
        $approvedSubmissions = $this->db->query(
            "SELECT s.*, u.department_id FROM submissions s JOIN users u ON s.user_id = u.id WHERE s.period_id = ? AND s.status = 'approved'",
            [$periodId]
        )->fetchAll();

        // 4. Natijalarni qayta ishlash uchun boshlang'ich struktura yaratish
        $result = [];
        foreach ($faculties as $faculty) {
            $result[$faculty['id']] = [
                'name' => $faculty['name'],
                'departments' => [],
                'summary' => []
            ];
        }
        foreach ($departments as $department) {
            if (isset($result[$department['faculty_id']])) {
                $result[$department['faculty_id']]['departments'][$department['id']] = [
                    'name' => $department['name'],
                    'progress' => []
                ];
            }
        }
        
        // 5. Har bir ishlanma qiymatini hisoblash va tegishli kafedra hisobiga qo'shish
        foreach ($approvedSubmissions as $submission) {
            $userId = $submission['user_id'];
            $deptId = $submission['department_id'];
            $sectionCode = $submission['section_code'];

            // Foydalanuvchi qaysi fakultetda ekanligini topishimiz kerak bo'lishi mumkin, lekin department_id yetarli
            // Bizga kafedra qaysi fakultetdaligi kerak
            $facultyId = null;
            foreach($departments as $dept) {
                if ($dept['id'] == $deptId) {
                    $facultyId = $dept['faculty_id'];
                    break;
                }
            }
            
            if ($facultyId && isset($result[$facultyId]['departments'][$deptId])) {
                 if (!isset($result[$facultyId]['departments'][$deptId]['progress'][$sectionCode])) {
                     $result[$facultyId]['departments'][$deptId]['progress'][$sectionCode] = ['target' => 0, 'accomplished' => 0];
                 }
                $value = $ishlanmaRepo->calculateSubmissionValue($submission);
                $result[$facultyId]['departments'][$deptId]['progress'][$sectionCode]['accomplished'] += $value;
            }
        }
        
        // 6. Rejalarni qo'shish va yakuniy hisob-kitoblar
        foreach($result as $facultyId => &$facultyData) { // & orqali link bilan ishlaymiz
            $facultySummary = [];
            foreach($facultyData['departments'] as $deptId => &$deptData) { // &
                foreach($ishlanmaRepo->getSections() as $sectionCode => $section) {
                    if(!isset($deptData['progress'][$sectionCode])) {
                         $deptData['progress'][$sectionCode] = ['target' => 0, 'accomplished' => 0];
                    }
                    $deptData['progress'][$sectionCode]['target'] = $departmentTargets[$deptId][$sectionCode] ?? 0;

                    // Fakultetning umumiy hisoboti uchun
                    if(!isset($facultySummary[$sectionCode])) {
                         $facultySummary[$sectionCode] = ['target' => 0, 'accomplished' => 0];
                    }
                    $facultySummary[$sectionCode]['target'] += $deptData['progress'][$sectionCode]['target'];
                    $facultySummary[$sectionCode]['accomplished'] += $deptData['progress'][$sectionCode]['accomplished'];
                }
            }
            $facultyData['summary'] = $facultySummary;
        }

        return $result;
    }
}
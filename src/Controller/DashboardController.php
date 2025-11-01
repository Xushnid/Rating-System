<?php

namespace App\Controller;

use App\Auth;
use App\Ishlanma;
use App\User;
use App\Period;
use App\Target;

class DashboardController
{
    private $auth;
    private $ishlanmaRepo;
    private $userRepo;
    private $periodRepo;
    private $targetRepo;

    public function __construct(Auth $auth, Ishlanma $ishlanmaRepo, User $userRepo, Period $periodRepo, Target $targetRepo)
    {
        $this->auth = $auth;
        $this->ishlanmaRepo = $ishlanmaRepo;
        $this->userRepo = $userRepo;
        $this->periodRepo = $periodRepo;
        $this->targetRepo = $targetRepo;
    }

    private function jsonResponse($success, $message, $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }

   public function index()
    {
        $this->auth->requireUser();
        $currentUser = $this->auth->getCurrentUser();
        $auth = $this->auth;
        $current_page = 'user_index';
        global $all_periods_for_header, $selectedPeriod;

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        // View uchun kerak bo'ladigan o'zgaruvchilarni e'lon qilamiz
        $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        $userProgressData = [];
        $departmentProgressData = [];
        $teachers_in_department = [];
        $filter_teacher_id = 0;
        $sections = $this->ishlanmaRepo->getSections(); // Bu sahifada ham sections kerak

        if ($selectedPeriodId > 0) {
            // ROLGA QARAB STATISTIKANI YIG'AMIZ
            if ($currentUser['role'] === 'departmentadmin') {
                // Kafedra admini uchun - butun kafedra statistikasi
                $stats = $this->ishlanmaRepo->getCountsInDepartmentForPeriod($currentUser['department_id'], $selectedPeriodId);
            } else {
                // Oddiy o'qituvchi uchun - shaxsiy statistika
                $stats = $this->ishlanmaRepo->getCountsByUserIdForPeriod($currentUser['id'], $selectedPeriodId);
            }

            // Shaxsiy progress har doim olinadi
            $userProgressData = $this->targetRepo->getUserProgress($currentUser['id'], $selectedPeriodId, $this->ishlanmaRepo);
            // Agar KAFEDRA ADMINI bo'lsa, qo'shimcha ma'lumotlarni olamiz
            if ($currentUser['role'] === 'departmentadmin') {
                $departmentProgressData = $this->targetRepo->getDepartmentProgress($currentUser['department_id'], $selectedPeriodId, $this->ishlanmaRepo, $this->userRepo);
                // Filtr uchun o'qituvchilar ro'yxati
                $teachers_in_department = $this->userRepo->findUsersByDepartment($currentUser['department_id']);
                // GET so'rovidan filtrni o'qiymiz
                $filter_teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
            }
        }
        
        require_once __DIR__ . '/../../views/user/index.php';
    }

    public function ishlanmalar()
    {
        $this->auth->requireUser();
        $currentUser = $this->auth->getCurrentUser();
        $auth = $this->auth;
        $current_page = 'ishlanmalar';
        global $all_periods_for_header, $selectedPeriod;

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        
        // YANGILANDI: Guruhlar va bo'limlar yangi metodlar orqali olinadi
        $section_groups = $this->ishlanmaRepo->getSectionGroups();
        $sections = $this->ishlanmaRepo->getSections();

        // GET parametrlaridan joriy guruh va bo'limni o'qish
        $active_group_key = $_GET['group'] ?? array_key_first($section_groups);
        if (!isset($section_groups[$active_group_key])) {
            $active_group_key = array_key_first($section_groups);
        }
        $default_section_code = $section_groups[$active_group_key]['sections'][0] ?? array_key_first($sections);
        $active_section_code = $_GET['section'] ?? $default_section_code;
        if(!in_array($active_section_code, $section_groups[$active_group_key]['sections'])) {
            $active_section_code = $default_section_code;
        }

        $page_type = 'user';
        $ishlanmalar = $this->ishlanmaRepo->findAllByUserId($currentUser['id'], $selectedPeriodId, $active_section_code);
        
        require_once __DIR__ . '/../../views/user/ishlanmalar.php';
    }

    public function addIshlanma()
    {
        $this->auth->requireUser();
        $currentUser = $this->auth->getCurrentUser();
        // YANGILANDI: Endi guruhlar ham olinadi
        $section_groups = $this->ishlanmaRepo->getSectionGroups();
        $sections = $this->ishlanmaRepo->getSections();
        $current_page = 'add_ishlanma';
        $auth = $this->auth;
        
        $allPeriods = $this->periodRepo->findAll();
        $activePeriods = array_filter($allPeriods, function($p) { return $p['status'] === 'active'; });
        
        global $all_periods_for_header, $selectedPeriod;
        require_once __DIR__ . '/../../views/user/add_ishlanma.php';
    }

    // --- AJAX METODLARI ---
    
    public function ajaxGetIshlanmaTable()
    {
        $this->auth->requireUser();
        $currentUser = $this->auth->getCurrentUser();
        $sections = $this->ishlanmaRepo->getSections();
        $section_code = $_GET['section'] ?? '';
        if (empty($section_code) || !isset($sections[$section_code])) {
            $this->jsonResponse(false, 'Noto‘g‘ri bo‘lim tanlandi.');
        }

        $page_type = 'user';
        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        $ishlanmalar = $this->ishlanmaRepo->findAllByUserId($currentUser['id'], $selectedPeriodId, $section_code);

        global $is_period_closed;
        ob_start();
        include __DIR__ . '/../../views/partials/ishlanma_table_partial.php';
        $html = ob_get_clean();

        $this->jsonResponse(true, 'Jadval yuklandi.', ['html' => $html]);
    }

    // Qolgan AJAX metodlari (getSubmission, createSubmission, updateSubmission, deleteSubmission, updateProfile) o'zgarishsiz qoladi.
    // ... (o'zgarishsiz metodlar)
	public function ajaxGetSubmission()
    {
        $this->auth->requireUser();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_GET['id'] ?? 0);
        $submission = $this->ishlanmaRepo->findById($id);

        if (!$submission || $submission['user_id'] != $currentUser['id']) {
            $this->jsonResponse(false, 'Ishlanma topilmadi yoki ruxsat yo\'q.');
        }

        $submission_data = json_decode($submission['data'], true);
        $response_data = array_merge($submission, ['data' => $submission_data]);
        $this->jsonResponse(true, 'Ma\'lumotlar yuklandi', $response_data);
    }
    
    public function ajaxCreateSubmission()
{
    $this->auth->requireUser();
    $this->auth->validateCsrfToken();
    $currentUser = $this->auth->getCurrentUser();
    $data = $_POST;
    $data['user_id'] = $currentUser['id'];
    $data['section_code'] = $data['section']; 
    $file = $_FILES['file'] ?? null;

    $period_id = (int)($_POST['period_id'] ?? 0);
    if (empty($period_id)) {
        $this->jsonResponse(false, 'Iltimos, ishlanma topshirilayotgan davrni tanlang.');
        return;
    }
    
    if ($this->ishlanmaRepo->create($data, $file, $period_id)) {
        $this->jsonResponse(true, 'Ishlanma muvaffaqiyatli qo\'shildi va ko\'rib chiqish uchun yuborildi.');
    } else {
        $this->jsonResponse(false, 'Ishlanma qo\'shishda xatolik yuz berdi.');
    }
}

    public function ajaxUpdateSubmission()
    {
        $this->auth->requireUser();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_POST['id'] ?? 0);
        $data = $_POST;
        $file = $_FILES['file'] ?? null;

        // Debug logging
        error_log("Update submission request - ID: $id, User: {$currentUser['id']}");
        error_log("POST data: " . print_r($data, true));
        if ($file) {
            error_log("File data: " . print_r($file, true));
        }

        // Validate required data
        if (empty($id)) {
            $this->jsonResponse(false, 'Ishlanma ID topilmadi.');
            return;
        }

        // Check if submission exists and belongs to user
        $submission = $this->ishlanmaRepo->findById($id);
        if (!$submission) {
            $this->jsonResponse(false, 'Ishlanma topilmadi.');
            return;
        }

        if ($submission['user_id'] != $currentUser['id']) {
            $this->jsonResponse(false, 'Bu ishlanmani tahrirlashga ruxsat yo\'q.');
            return;
        }

        // Special debugging for section 2.6.1 (FIXED: moved after $submission is loaded)
        if ($submission['section_code'] === '2.6.1') {
            error_log("=== Section 2.6.1 Update Debug ===");
            error_log("Current data in DB: " . print_r(json_decode($submission['data'], true), true));
            error_log("New POST data for 2.6.1: " . print_r($data, true));
            
            // Log specific fields that are problematic
            $currentData = json_decode($submission['data'], true);
            $problemFields = ['publish_date', 'article_name', 'url'];
            foreach ($problemFields as $field) {
                $currentValue = isset($currentData[$field]) ? $currentData[$field] : 'NULL';
                $newValue = isset($data[$field]) ? $data[$field] : 'NULL';
                error_log("Field '$field': Current='$currentValue', New='$newValue'");
            }
        }

        try {
            $result = $this->ishlanmaRepo->update($id, $currentUser['id'], false, $data, $file);
            error_log("Update result: " . ($result ? 'success' : 'failure'));
            
            // Additional debugging for section 2.6.1 after update
            if ($submission['section_code'] === '2.6.1' && $result) {
                $updatedSubmission = $this->ishlanmaRepo->findById($id);
                $updatedData = json_decode($updatedSubmission['data'], true);
                error_log("=== Section 2.6.1 After Update ===");
                error_log("Updated data in DB: " . print_r($updatedData, true));
                
                $problemFields = ['publish_date', 'article_name', 'url'];
                foreach ($problemFields as $field) {
                    $expected = isset($data[$field]) ? $data[$field] : 'NULL';
                    $actual = isset($updatedData[$field]) ? $updatedData[$field] : 'NULL';
                    $status = ($expected === $actual) ? 'OK' : 'FAILED';
                    error_log("Field '$field': Expected='$expected', Actual='$actual', Status=$status");
                }
            }
            
            if ($result) {
                $this->jsonResponse(true, 'Ishlanma muvaffaqiyatli yangilandi.');
            } else {
                $this->jsonResponse(false, 'Ishlanmani yangilashda xatolik yuz berdi yoki hech narsa o\'zgartirilmadi.');
            }
        } catch (\Exception $e) {
            error_log("Update submission error: " . $e->getMessage());
            $this->jsonResponse(false, 'Ishlanmani yangilashda server xatosi yuz berdi.');
        }
    }

    public function ajaxDeleteSubmission()
    {
        $this->auth->requireUser();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_POST['id'] ?? 0);

        if ($this->ishlanmaRepo->delete($id, $currentUser['id'], false)) {
            $this->jsonResponse(true, 'Ishlanma muvaffaqiyatli o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Ishlanmani o\'chirishda xatolik yuz berdi.');
        }
    }

    public function ajaxUpdateProfile()
    {
        $this->auth->requireUser();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
        ];
        $password_confirm = trim($_POST['password_confirm'] ?? '');

        if (empty($data['username'])) {
            $this->jsonResponse(false, 'Login bo\'sh bo\'lishi mumkin emas.');
        }
        if (!empty($data['password'])) {
            if(strlen($data['password']) < 6) {
                $this->jsonResponse(false, 'Yangi parol kamida 6 ta belgidan iborat bo\'lishi kerak.');
            }
            if ($data['password'] !== $password_confirm) {
                $this->jsonResponse(false, 'Kiritilgan yangi parollar bir-biriga mos kelmadi.');
            }
        }

        if ($this->userRepo->updateProfile($currentUser['id'], $data)) {
            $this->jsonResponse(true, 'Profilingiz muvaffaqiyatli yangilandi.');
        } else {
            $this->jsonResponse(false, 'Ma\'lumotlarni yangilashda xatolik yuz berdi. Ehtimol, bu login boshqa foydalanuvchi tomonidan band qilingan.');
        }
    }
    
    public function ajaxGetRejectedCounts()
    {
        $this->auth->requireUser();
        $currentUser = $this->auth->getCurrentUser();
        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        
        error_log("ajaxGetRejectedCounts called - User ID: {$currentUser['id']}, Period: {$selectedPeriodId}");
        
        if ($selectedPeriodId <= 0) {
            error_log("ajaxGetRejectedCounts - No period selected");
            $this->jsonResponse(false, 'Joriy davr tanlanmagan.');
            return;
        }
        
        try {
            $rejectedCounts = $this->ishlanmaRepo->getRejectedCountsBySectionForUser($currentUser['id'], $selectedPeriodId);
            error_log("ajaxGetRejectedCounts - Counts: " . json_encode($rejectedCounts));
            $this->jsonResponse(true, 'Rad etilgan ishlanmalar sonlari yuklandi.', ['rejected_counts' => $rejectedCounts]);
        } catch (\Exception $e) {
            error_log('Error fetching rejected counts: ' . $e->getMessage());
            $this->jsonResponse(false, 'Xatolik yuz berdi.');
        }
    }
}
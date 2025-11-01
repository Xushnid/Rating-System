<?php

namespace App\Controller;

use App\Auth;
use App\User;
use App\Ishlanma;
use App\Department;
use App\Period;
use App\Target;

class FacultyAdminController
{
    private $auth;
    private $userRepo;
    private $ishlanmaRepo;
    private $departmentRepo;
    private $periodRepo;
    private $targetRepo;
    
    
    public function __construct(Auth $auth, User $userRepo, Ishlanma $ishlanmaRepo, Department $departmentRepo, Period $periodRepo, Target $targetRepo)
    {
        $this->auth = $auth;
        $this->userRepo = $userRepo;
        $this->ishlanmaRepo = $ishlanmaRepo;
        $this->departmentRepo = $departmentRepo;
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
        $this->auth->requireFacultyAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $auth = $this->auth;
        $current_page = 'faculty_admin_index';
        $faculty_id = $currentUser['faculty_id'];
        global $all_periods_for_header, $selectedPeriod;

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        $filter_department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
        
        $facultyProgressData = [];
        $facultyStats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0]; // Standart qiymat

        if ($selectedPeriodId > 0) {
            // YANGI QATOR: Statistik kartochkalar uchun ma'lumotlarni chaqirish
            $facultyStats = $this->ishlanmaRepo->getCountsInFacultyForPeriod($faculty_id, $selectedPeriodId);
            // Fakultetdagi barcha kafedralarning progressini hisoblaymiz
            $facultyProgressData = $this->targetRepo->getFacultyProgress($faculty_id, $selectedPeriodId, $this->ishlanmaRepo, $this->departmentRepo);
        }
        
        $departments_in_faculty = $this->departmentRepo->findAllByFacultyId($faculty_id);
        $sections = $this->ishlanmaRepo->getSections();

        require_once __DIR__ . '/../../views/faculty_admin/index.php';
    }

    public function submissions()
    {
        $this->auth->requireFacultyAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $faculty_id = $currentUser['faculty_id'];
        $auth = $this->auth;
        $current_page = 'faculty_admin_submissions';
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
        
        $filters = [
            'status' => $_GET['status'] ?? null,
            'user_id' => $_GET['user_id'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
        ];
        
        $ishlanmalar = $this->ishlanmaRepo->findAllForFaculty($faculty_id, $selectedPeriodId, array_merge(['section_code' => $active_section_code], $filters));
        $departments_in_faculty = $this->departmentRepo->findAllByFacultyId($faculty_id);
        $users_in_faculty = $this->userRepo->findUsersByFaculty($faculty_id);

        require_once __DIR__ . '/../../views/faculty_admin/submissions.php';
    }

    public function departmentAdmins()
    {
        $this->auth->requireFacultyAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $faculty_id = $currentUser['faculty_id'];
        $auth = $this->auth;
        $current_page = 'faculty_admin_dept_admins';
        // Bu sahifadagi ma'lumotlar davrga bog'liq emas, lekin header ishlashi uchun global o'zgaruvchilar kerak
        global $all_periods_for_header, $selectedPeriod;
        $departments = $this->departmentRepo->findAllByFacultyId($faculty_id);
        $users = $this->userRepo->findUsersByFaculty($faculty_id);

        $department_admins = [];
        foreach ($users as $user) {
            if ($user['role'] === 'departmentadmin' && $user['department_id']) {
                $department_admins[$user['department_id']] = $user;
            }
        }
        
        require_once __DIR__ . '/../../views/faculty_admin/department_admins.php';
    }

    public function ajaxGetIshlanmaTable()
    {
        $this->auth->requireFacultyAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $faculty_id = $currentUser['faculty_id'];

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;

        $sections = $this->ishlanmaRepo->getSections();
        $section_code = $_GET['section'] ?? '';

        if (empty($section_code) || !isset($sections[$section_code])) {
            $this->jsonResponse(false, 'Noto‘g‘ri bo‘lim tanlandi.');
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'user_id' => $_GET['user_id'] ?? null,
            'department_id' => $_GET['department_id'] ?? null,
            'section_code' => $section_code,
        ];
        // Model metodiga $selectedPeriodId uzatiladi
        $ishlanmalar = $this->ishlanmaRepo->findAllForFaculty($faculty_id, $selectedPeriodId, $filters);
        // Fakultet admini faqat ko'ra oladi, tahrirlay olmaydi
        $is_readonly = true;
        $page_type = 'faculty_admin';

        // Shablonga tahrirlashni bloklash uchun o'zgaruvchini uzatamiz
        global $is_period_closed;

        ob_start();
        include __DIR__ . '/../../views/partials/ishlanma_table_partial.php';
        $html = ob_get_clean();

        $this->jsonResponse(true, 'Jadval yuklandi.', ['html' => $html]);
    }
    
    // Bu AJAX metod davrga bog'liq emas, o'zgarishsiz qoladi
     public function ajaxAssignDepartmentAdmin()
    {
        $this->auth->requireFacultyAdmin();
        $this->auth->validateCsrfToken();
        
        $currentUser = $this->auth->getCurrentUser();
        $faculty_id = $currentUser['faculty_id'];
        
        $user_id = (int)($_POST['user_id'] ?? 0);
        $department_id = (int)($_POST['department_id'] ?? 0);
        
        if ($user_id !== 0 && $user_id === $currentUser['id']) {
            $this->jsonResponse(false, 'Fakultet admini o\'zini o\'zi kafedra admini qilib tayinlay olmaydi.');
        }
        
        $user_to_assign = $this->userRepo->findById($user_id);
        $department_to_assign = $this->departmentRepo->findById($department_id);

        if ($user_id !== 0 && (!$user_to_assign || $user_to_assign['faculty_id'] != $faculty_id)) {
            $this->jsonResponse(false, 'Ruxsat yo\'q yoki foydalanuvchi ma\'lumotlari xato.');
        }

        if (!$department_to_assign || $department_to_assign['faculty_id'] != $faculty_id) {
             $this->jsonResponse(false, 'Ruxsat yo\'q yoki kafedra ma\'lumotlari xato.');
        }

        // Tranzaksiyani boshqarish uchun PDO obyektini olamiz
        $pdo = $this->userRepo->getDb()->getPdo();

        try {
            // Tranzaksiyani boshlaymiz
            $pdo->beginTransaction();

            // 1-operatsiya: Bu kafedraning avvalgi adminini roldan ozod qilish
            $users_in_faculty = $this->userRepo->findUsersByFaculty($faculty_id);
            foreach ($users_in_faculty as $u) {
                if ($u['role'] === 'departmentadmin' && $u['department_id'] == $department_id && $u['id'] != $user_id) {
                    $this->userRepo->setDepartmentAdmin($u['id'], null);
                }
            }
            
            // 2-operatsiya: Yangi adminni tayinlash (yoki olib tashlash, agar user_id=0 bo'lsa)
            $this->userRepo->setDepartmentAdmin($user_id, $department_id);

            // Agar ikkala operatsiya ham muvaffaqiyatli bo'lsa, o'zgarishlarni tasdiqlaymiz
            $pdo->commit();

            $this->jsonResponse(true, 'Kafedra admini muvaffaqiyatli tayinlandi.');

        } catch (\Exception $e) {
            // Agar biror xatolik yuz bersa, barcha o'zgarishlarni bekor qilamiz
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_error("Assign department admin failed: " . $e->getMessage());
            $this->jsonResponse(false, 'Amalni bajarishda kutilmagan xatolik yuz berdi.');
        }
    }
}
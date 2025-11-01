<?php

namespace App\Controller;

use App\Auth;
use App\User;
use App\Ishlanma;
use App\Faculty;
use App\Department;
use App\Period;
use App\Target;

class AdminController
{
    private $auth;
    private $authorization;
    private $userRepo;
    private $ishlanmaRepo;
    private $facultyRepo;
    private $departmentRepo;
    private $periodRepo;
    private $targetRepo;

    public function __construct(Auth $auth, User $userRepo, Ishlanma $ishlanmaRepo, Faculty $facultyRepo, Department $departmentRepo, Period $periodRepo, Target $targetRepo)
    {
        $this->auth = $auth;
        $this->authorization = $auth->getAuthorization();
        $this->userRepo = $userRepo;
        $this->ishlanmaRepo = $ishlanmaRepo;
        $this->facultyRepo = $facultyRepo;
        $this->departmentRepo = $departmentRepo;
        $this->periodRepo = $periodRepo;
        $this->targetRepo = $targetRepo;
    }

    /**
     * Centralized superadmin requirement check
     */
    private function requireSuperAdmin()
    {
        $this->authorization->requireRole('superadmin');
    }

    private function jsonResponse($success, $message, $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
        exit;
    }

    public function index()
    {
        $this->requireSuperAdmin();
        $auth = $this->auth;
        $current_page = 'admin_index';
        $currentUser = $this->auth->getCurrentUser();
        global $all_periods_for_header, $selectedPeriod, $is_period_closed; // Global o'zgaruvchilarni olamiz

        // Sessiyadan joriy davrni olamiz
        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;

        // Boshqaruv paneli uchun kerakli barcha ma'lumotlarni tayyorlaymiz
        $sections = $this->ishlanmaRepo->getSections();
        $faculties = $this->facultyRepo->findAll();
        $departments = $this->departmentRepo->findAll();

        // Filtr uchun GET-parametrlarni olamiz
        $filter_faculty_id = isset($_GET['faculty_id']) ? (int)$_GET['faculty_id'] : 0;
        $filter_department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
        // Standart qiymatlar
        $globalStats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        $submissionsByFaculty = [];
        $systemProgressData = [];
        $instituteSummary = [];

        if ($selectedPeriodId > 0) {
            // Asosiy statistikalar (yuqoridagi kartochkalar va grafik uchun)
            $globalStats = $this->ishlanmaRepo->getCountsByStatusForPeriod($selectedPeriodId);
            $submissionsByFaculty = $this->ishlanmaRepo->getSubmissionCountsByFacultyForPeriod($selectedPeriodId);
            
            // Barcha fakultetlar va kafedralar bo'yicha progressni olamiz
            $systemProgressData = $this->targetRepo->getSystemWideProgress(
                $selectedPeriodId, $this->ishlanmaRepo, $this->facultyRepo, $this->departmentRepo
            );
            // "Umumiy Institut" hisoboti uchun ma'lumotlarni yig'amiz
            foreach ($sections as $code => $section) {
                $instituteSummary[$code] = ['target' => 0, 'accomplished' => 0];
            }
            foreach ($systemProgressData as $facultyData) {
                foreach ($facultyData['summary'] as $code => $summary) {
                    if (isset($instituteSummary[$code])) {
                        $instituteSummary[$code]['target'] += $summary['target'];
                        $instituteSummary[$code]['accomplished'] += $summary['accomplished'];
                    }
                }
            }
        }
        
        require_once __DIR__ . '/../../views/admin/index.php';
    }

   public function users()
    {
        $this->requireSuperAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $current_page = 'users';
        $auth = $this->auth;
        global $all_periods_for_header, $selectedPeriod;
        // Filtr parametrlari
        $filters = [
            'faculty_id' => (int)($_GET['faculty_id'] ?? 0),
            'department_id' => (int)($_GET['department_id'] ?? 0),
            'role' => trim($_GET['role'] ?? ''),
        ];
        // Ma'lumotlarni yangi metod orqali filterlab olamiz
        $users = $this->userRepo->findFilteredUsers($filters);
        // Filtr maydonlari uchun ma'lumotlar
        $faculties = $this->facultyRepo->findAll();
        $departments = $this->departmentRepo->findAll();
        require_once __DIR__ . '/../../views/admin/users.php';
    }
    
    public function faculties()
    {
        $this->requireSuperAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $faculties = $this->facultyRepo->findAll();
        $current_page = 'faculties';
        $auth = $this->auth;
        // YANGI QATORLAR: Header uchun
        global $all_periods_for_header, $selectedPeriod;

        require_once __DIR__ . '/../../views/admin/faculties.php';
    }

    public function departments()
    {
        $this->requireSuperAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $departments = $this->departmentRepo->findAllWithFacultyName();
        $faculties = $this->facultyRepo->findAll(); // Forma uchun
        $current_page = 'departments';
        $auth = $this->auth;

        // YANGI QATORLAR: Header uchun
        global $all_periods_for_header, $selectedPeriod;
        require_once __DIR__ . '/../../views/admin/departments.php';
    }

   public function allIshlanmalar()
    {
        $this->requireSuperAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $auth = $this->auth;
        $current_page = 'admin_ishlanma';
        global $all_periods_for_header, $selectedPeriod;
        
        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        
        // YANGILANDI: Guruhlar va bo'limlar yangi metodlar orqali olinadi
        $section_groups = $this->ishlanmaRepo->getSectionGroups();
        $sections = $this->ishlanmaRepo->getSections();
        
        // GET parametrlaridan joriy guruh va bo'limni o'qish
        $active_group_key = $_GET['group'] ?? array_key_first($section_groups);
        
        // Agar guruh mavjud bo'lmasa, birinchisiga qaytaramiz
        if (!isset($section_groups[$active_group_key])) {
            $active_group_key = array_key_first($section_groups);
        }
        
        // Joriy guruhdagi birinchi bo'limni standart qilib belgilash
        $default_section_code = $section_groups[$active_group_key]['sections'][0] ?? array_key_first($sections);
        $active_section_code = $_GET['section'] ?? $default_section_code;

        // Agar tanlangan bo'lim joriy guruhda bo'lmasa, standartiga qaytaramiz
        if(!in_array($active_section_code, $section_groups[$active_group_key]['sections'])) {
            $active_section_code = $default_section_code;
        }

        // Qolgan filtrlar
        $faculties = $this->facultyRepo->findAll();
        $departments = $this->departmentRepo->findAll();
        $users = $this->userRepo->findFilteredUsers(); // Filtr uchun barcha foydalanuvchilar ro'yxati

        $status_filter = $_GET['status'] ?? '';
        $faculty_id_filter = $_GET['faculty_id'] ?? '';
        $department_id_filter = $_GET['department_id'] ?? '';
        $user_id_filter = $_GET['user_id'] ?? '';
        $page_type = 'admin_all';
        $ishlanmalar = $this->ishlanmaRepo->findAllForAdmin($active_section_code, $selectedPeriodId, $status_filter, $user_id_filter, $faculty_id_filter, $department_id_filter);
        
        require_once __DIR__ . '/../../views/admin/admin_ishlanma.php';
    }

    public function verify()
    {
        $this->requireSuperAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $auth = $this->auth;
        $current_page = 'verify';
        global $all_periods_for_header, $selectedPeriod;
        
        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;

        // YANGILANDI: Guruhlar va bo'limlar yangi metodlar orqali olinadi
        $section_groups = $this->ishlanmaRepo->getSectionGroups();
        $sections = $this->ishlanmaRepo->getSections();

        // GET parametrlaridan joriy guruh va bo'limni o'qish (allIshlanmalar bilan bir xil)
        $active_group_key = $_GET['group'] ?? array_key_first($section_groups);
        if (!isset($section_groups[$active_group_key])) {
            $active_group_key = array_key_first($section_groups);
        }
        $default_section_code = $section_groups[$active_group_key]['sections'][0] ?? array_key_first($sections);
        $active_section_code = $_GET['section'] ?? $default_section_code;
        if(!in_array($active_section_code, $section_groups[$active_group_key]['sections'])) {
            $active_section_code = $default_section_code;
        }
        
        $page_type = 'verify';
        
        $pending_counts = $this->ishlanmaRepo->getPendingCountsBySection($selectedPeriodId);
        $ishlanmalar = $this->ishlanmaRepo->findPendingBySection($active_section_code, $selectedPeriodId);

        // YANGI: Guruhlar uchun kutilayotgan ishlanmalar borligini tekshirish (indikator uchun)
        $group_pending_status = [];
        foreach ($section_groups as $g_key => $group) {
            $group_has_pending = false;
            foreach ($group['sections'] as $s_code) {
                if (!empty($pending_counts[$s_code])) {
                    $group_has_pending = true;
                    break;
                }
            }
            $group_pending_status[$g_key] = $group_has_pending;
        }

        require_once __DIR__ . '/../../views/admin/verify.php';
    }
    
    public function periods()
    {
        $this->requireSuperAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $periods = $this->periodRepo->findAll();
        $current_page = 'periods';
        $auth = $this->auth;
        // YANGI QATORLAR: Header uchun
        global $all_periods_for_header, $selectedPeriod;

        require_once __DIR__ . '/../../views/admin/periods.php';
    }
    
    public function targets()
    {
        $this->requireSuperAdmin();
        $currentUser = $this->auth->getCurrentUser(); 
        $auth = $this->auth;
        $current_page = 'targets';
        global $all_periods_for_header, $selectedPeriod, $is_period_closed;

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        // Filtr parametrlari
        $filters = [
            'faculty_id' => (int)($_GET['faculty_id'] ?? 0),
            'department_id' => (int)($_GET['department_id'] ?? 0),
        ];
        // Filtr uchun barcha fakultet va kafedralarni olamiz
        $faculties = $this->facultyRepo->findAll();
        $all_departments = $this->departmentRepo->findAllWithFacultyName();

        // Ko'rsatish uchun faqat filterlangan kafedralarni olamiz
        $departments_to_show = $this->departmentRepo->findFiltered($filters);
        $sections = $this->ishlanmaRepo->getSections();
        $targets = [];
        if ($selectedPeriodId > 0) {
            $targets = $this->targetRepo->getDepartmentTargetsForPeriod($selectedPeriodId);
        }

        require_once __DIR__ . '/../../views/admin/targets.php';
    }


    // ===================================
    //  AJAX METODLARI (O'zgarishsiz qoladiganlar ham to'liq yozilgan)
    // ===================================

    public function ajaxCreatePeriod() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? ''),
            'created_by' => $currentUser['id'],
        ];

        if (empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            $this->jsonResponse(false, 'Barcha maydonlar to\'ldirilishi shart.');
        }

        if ($this->periodRepo->create($data)) {
            $this->jsonResponse(true, 'Yangi davr muvaffaqiyatli yaratildi.');
        } else {
            $this->jsonResponse(false, 'Davr yaratishda xatolik yuz berdi.');
        }
    }

    public function ajaxUpdatePeriod() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'start_date' => trim($_POST['start_date'] ?? ''),
            'end_date' => trim($_POST['end_date'] ?? ''),
            'status' => trim($_POST['status'] ?? 'active'),
        ];
        
        if (empty($id) || empty($data['name']) || empty($data['start_date']) || empty($data['end_date'])) {
            $this->jsonResponse(false, 'Noto‘g‘ri so‘rov. Barcha maydonlar to\'ldirilishi shart.');
        }

        if ($this->periodRepo->update($id, $data)) {
            $this->jsonResponse(true, 'Davr ma\'lumotlari muvaffaqiyatli yangilandi.');
        } else {
            $this->jsonResponse(false, 'Hech narsa o\'zgartirilmadi yoki xatolik yuz berdi.');
        }
    }

    public function ajaxDeletePeriod() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        
        $id = (int)($_POST['id'] ?? 0);
        if (empty($id)) {
            $this->jsonResponse(false, 'ID topilmadi.');
        }

        if ($this->periodRepo->delete($id)) {
            $this->jsonResponse(true, 'Davr o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Davrni o\'chirishda xatolik yuz berdi.');
        }
    }

    public function ajaxGetIshlanmaTable()
    {
        $this->requireSuperAdmin();
        // YANGILANDI: Bo'limlar ro'yxati endi to'g'ri olinadi
        $sections = $this->ishlanmaRepo->getSections();
        $section_code = $_GET['section'] ?? '';

        if (empty($section_code) || !isset($sections[$section_code])) {
            $this->jsonResponse(false, 'Noto‘g‘ri bo‘lim tanlandi.');
        }

        // O'ZGARTIRILDI: Davr sessiyadan olinadi
        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;

        $page_type = $_GET['page_type'] ?? 'admin_all';

        if ($page_type === 'verify') {
            $ishlanmalar = $this->ishlanmaRepo->findPendingBySection($section_code, $selectedPeriodId);
        } else {
            $status = $_GET['status'] ?? null;
            $faculty_id = $_GET['faculty_id'] ?? null;
            $department_id = $_GET['department_id'] ?? null;
            $user_id_filter = $_GET['user_id'] ?? null;
            $ishlanmalar = $this->ishlanmaRepo->findAllForAdmin($section_code, $selectedPeriodId, $status, $user_id_filter, $faculty_id, $department_id);
        }
        
        $auth = $this->auth;
        // YANGI QATOR: Shablonga $is_period_closed o'zgaruvchisini o'tkazish
        global $is_period_closed;

        ob_start();
        include __DIR__ . '/../../views/partials/ishlanma_table_partial.php';
        $html = ob_get_clean();

        $this->jsonResponse(true, 'Jadval yuklandi.', ['html' => $html]);
    }

    public function ajaxGetSubmission()
    {
        $this->requireSuperAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $submission = $this->ishlanmaRepo->findById($id);

        if (!$submission) {
            $this->jsonResponse(false, 'Ishlanma topilmadi.');
        }

        $submission_data = json_decode($submission['data'], true);
        $response_data = array_merge($submission, ['data' => $submission_data]);
        $this->jsonResponse(true, 'Ma\'lumotlar yuklandi', $response_data);
    }

    public function ajaxUpdateSubmission()
    {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_POST['id'] ?? 0);
        $data = $_POST;
        $file = $_FILES['file'] ?? null;

        if ($this->ishlanmaRepo->update($id, $currentUser['id'], true, $data, $file)) {
            $this->jsonResponse(true, 'Ishlanma muvaffaqiyatli yangilandi.');
        } else {
            $this->jsonResponse(false, 'Ishlanmani yangilashda xatolik yuz berdi yoki hech narsa o\'zgartirilmadi.');
        }
    }

    public function ajaxDeleteSubmission()
    {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_POST['id'] ?? 0);

        if ($this->ishlanmaRepo->delete($id, $currentUser['id'], true)) {
            $this->jsonResponse(true, 'Ishlanma muvaffaqiyatli o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Ishlanmani o\'chirishda xatolik yuz berdi.');
        }
    }

    public function ajaxUpdateStatus()
    {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (empty($id) || !in_array($status, ['approved', 'rejected'])) {
            $this->jsonResponse(false, 'Noto‘g‘ri so‘rov.');
        }
        
        if ($status === 'rejected' && empty($reason)) {
            $this->jsonResponse(false, 'Rad etish sababini kiritish majburiy.');
        }

        $message = ($status === 'approved') ? 'tasdiqlandi' : 'rad etildi';

        if ($this->ishlanmaRepo->updateStatus($id, $status, $reason)) {
            $new_token = $this->auth->generateCsrfToken();
            
            // O'ZGARTIRILDI: Sanaladigan davr sessiyadan olinadi
            $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
            $new_counts = $this->ishlanmaRepo->getPendingCountsBySection($selectedPeriodId);

            $this->jsonResponse(true, "Ishlanma muvaffaqiyatli $message.", ['new_token' => $new_token, 'pending_counts' => $new_counts]);
        } else {
            $this->jsonResponse(false, "Ishlanmani $message xatolik yuz berdi.");
        }
    }

    public function ajaxCreateUser()
    {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'role' => $_POST['role'] ?? 'user',
            'faculty_id' => !empty($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null,
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
        ];
        $password_confirm = trim($_POST['password_confirm'] ?? '');

        if (empty($data['full_name']) || empty($data['username']) || empty($data['password']) || !in_array($data['role'], ['superadmin', 'facultyadmin', 'departmentadmin', 'user'])) {
            $this->jsonResponse(false, 'Barcha majburiy maydonlar to\'ldirilishi kerak.');
            return;
        }
        if (strlen($data['password']) < 6) {
            $this->jsonResponse(false, 'Parol kamida 6 ta belgidan iborat bo\'lishi kerak.');
            return;
        }
        if ($data['password'] !== $password_confirm) {
            $this->jsonResponse(false, 'Kiritilgan parollar bir-biriga mos kelmadi.');
            return;
        }
        if ($this->userRepo->create($data)) {
            $this->jsonResponse(true, 'Foydalanuvchi muvaffaqiyatli qo\'shildi.');
        } else {
            $this->jsonResponse(false, 'Foydalanuvchi qo\'shishda xatolik yuz berdi. Bunday login mavjud bo\'lishi mumkin.');
        }
    }

    public function ajaxUpdateUser()
    {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'role' => $_POST['role'] ?? 'user',
            'faculty_id' => !empty($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : null,
            'department_id' => !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null,
        ];
        $password_confirm = trim($_POST['password_confirm'] ?? '');

        if (empty($id) || empty($data['full_name']) || empty($data['username']) || !in_array($data['role'], ['superadmin', 'facultyadmin', 'departmentadmin', 'user'])) {
            $this->jsonResponse(false, 'Barcha majburiy maydonlar to\'ldirilishi kerak.');
            return;
        }
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $this->jsonResponse(false, 'Yangi parol kamida 6 ta belgidan iborat bo\'lishi kerak.');
                return;
            }
            if ($data['password'] !== $password_confirm) {
                $this->jsonResponse(false, 'Kiritilgan yangi parollar bir-biriga mos kelmadi.');
                return;
            }
        }
        if ($this->userRepo->update($id, $data)) {
            $this->jsonResponse(true, 'Foydalanuvchi ma\'lumotlari muvaffaqiyatli yangilandi.');
        } else {
            $this->jsonResponse(false, 'Foydalanuvchi ma\'lumotlarini yangilashda xatolik yuz berdi yoki hech narsa o\'zgartirilmadi.');
        }
    }
    
    public function ajaxDeleteUser() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_POST['id'] ?? 0);
        if (empty($id)) {
            $this->jsonResponse(false, 'Foydalanuvchi ID si topilmadi.');
        }
        if ($id === $currentUser['id']) {
             $this->jsonResponse(false, 'O\'zingizni o\'chira olmaysiz.');
        }

        if ($this->userRepo->delete($id)) {
            $this->jsonResponse(true, 'Foydalanuvchi muvaffaqiyatli o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Foydalanuvchini o\'chirishda xatolik yuz berdi (Bosh adminni o\'chirib bo\'lmaydi).');
        }
    }
	
	public function ajaxCreateFaculty() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        if (empty(trim($_POST['name']))) {
            $this->jsonResponse(false, 'Fakultet nomi bo\'sh bo\'lishi mumkin emas.');
        }
        if ($this->facultyRepo->create($_POST)) {
            $this->jsonResponse(true, 'Fakultet muvaffaqiyatli qo\'shildi.');
        } else {
            $this->jsonResponse(false, 'Xatolik yuz berdi.');
        }
    }
    public function ajaxUpdateFaculty() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $id = (int)($_POST['id'] ?? 0);
        if (empty($id) || empty(trim($_POST['name']))) {
            $this->jsonResponse(false, 'Noto‘g‘ri so‘rov.');
        }
        if ($this->facultyRepo->update($id, $_POST)) {
            $this->jsonResponse(true, 'Fakultet nomi yangilandi.');
        } else {
            $this->jsonResponse(false, 'Hech narsa o\'zgartirilmadi yoki xatolik yuz berdi.');
        }
    }
    public function ajaxDeleteFaculty() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $id = (int)($_POST['id'] ?? 0);
        if ($this->facultyRepo->delete($id)) {
            $this->jsonResponse(true, 'Fakultet o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Xatolik yuz berdi.');
        }
    }

    public function ajaxCreateDepartment() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        if (empty(trim($_POST['name'])) || empty($_POST['faculty_id'])) {
            $this->jsonResponse(false, 'Barcha maydonlar to\'ldirilishi shart.');
        }
        if ($this->departmentRepo->create($_POST)) {
            $this->jsonResponse(true, 'Kafedra muvaffaqiyatli qo\'shildi.');
        } else {
            $this->jsonResponse(false, 'Xatolik yuz berdi.');
        }
    }
    public function ajaxUpdateDepartment() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $id = (int)($_POST['id'] ?? 0);
        if (empty($id) || empty(trim($_POST['name'])) || empty($_POST['faculty_id'])) {
            $this->jsonResponse(false, 'Noto‘g‘ri so‘rov.');
        }
        if ($this->departmentRepo->update($id, $_POST)) {
            $this->jsonResponse(true, 'Kafedra ma\'lumotlari yangilandi.');
        } else {
            $this->jsonResponse(false, 'Hech narsa o\'zgartirilmadi yoki xatolik yuz berdi.');
        }
    }
    public function ajaxDeleteDepartment() {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();
        $id = (int)($_POST['id'] ?? 0);
        if ($this->departmentRepo->delete($id)) {
            $this->jsonResponse(true, 'Kafedra o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Xatolik yuz berdi.');
        }
    }
	
    public function ajaxSaveDepartmentTargets()
    {
        $this->requireSuperAdmin();
        $this->auth->validateCsrfToken();

        // O'ZGARTIRILDI: period_id endi sessiyadan olinadi, POST so'rovidan emas
        $periodId = $_SESSION['selected_period_id'] ?? 0;
        
        if (empty($periodId)) {
            $this->jsonResponse(false, 'Joriy davr tanlanmagan. Iltimos, sahifani yangilang.');
            return;
        }
        $targets = $_POST['targets'] ?? [];

        try {
            $result = $this->targetRepo->saveDepartmentTargets($periodId, $targets);

            if ($result) {
                $this->jsonResponse(true, 'Reja-qiymatlar muvaffaqiyatli saqlandi.');
            } else {
                $this->jsonResponse(true, 'Ma\'lumotlar o\'zgartirilmadi. Barcha qiymatlar avvalgidek edi.');
            }
        } catch (\PDOException $e) {
            log_error("Target save database error: " . $e->getMessage());
            $this->jsonResponse(false, 'Ma\'lumotlar bazasida xatolik yuz berdi. Iltimos, administrator bilan bog\'laning.');
        } catch (\Exception $e) {
            log_error("Target save general error: " . $e->getMessage());
            $this->jsonResponse(false, 'Tizimda kutilmagan xatolik yuz berdi. Iltimos, administrator bilan bog\'laning.');
        }
    }
}
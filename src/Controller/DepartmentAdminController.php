<?php

namespace App\Controller;

use App\Auth;
use App\User;
use App\Ishlanma;
use App\Department;
use App\Period;
use App\Target;
// PhpSpreadsheet uchun yangi use'lar
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; // <--- MANA SHU QATORNI QO'SHING

class DepartmentAdminController
{
    private $auth;
    private $authorization;
    private $userRepo;
    private $ishlanmaRepo;
    private $departmentRepo;
    private $periodRepo;
    private $targetRepo;
    
    public function __construct(Auth $auth, User $userRepo, Ishlanma $ishlanmaRepo, Department $departmentRepo, Period $periodRepo, Target $targetRepo)
    {
        $this->auth = $auth;
        $this->authorization = $auth->getAuthorization();
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
        $this->auth->requireDepartmentAdmin();
        header('Location: /user');
        exit;
    }

    public function submissions()
    {
        // Use centralized authorization
        $this->authorization->requireRole('departmentadmin');
        
        $currentUser = $this->auth->getCurrentUser();
        $department_id = $currentUser['department_id'];
        $auth = $this->auth;
        $current_page = 'dept_admin_submissions';
        global $all_periods_for_header, $selectedPeriod;

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;

        $section_groups = $this->ishlanmaRepo->getSectionGroups();
        $sections = $this->ishlanmaRepo->getSections();

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
        ];
        
        $ishlanmalar = $this->ishlanmaRepo->findAllForDepartment($department_id, $selectedPeriodId, array_merge(['section_code' => $active_section_code], $filters));
        $users_in_department = $this->userRepo->findUsersByDepartment($department_id);

        require_once __DIR__ . '/../../views/department_admin/submissions.php';
    }
    
    public function verify()
    {
        $this->auth->requireDepartmentAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $department_id = $currentUser['department_id'];
        $auth = $this->auth;
        $current_page = 'dept_admin_verify';
        global $all_periods_for_header, $selectedPeriod;

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;

        $section_groups = $this->ishlanmaRepo->getSectionGroups();
        $sections = $this->ishlanmaRepo->getSections();

        $active_group_key = $_GET['group'] ?? array_key_first($section_groups);
        if (!isset($section_groups[$active_group_key])) {
            $active_group_key = array_key_first($section_groups);
        }
        $default_section_code = $section_groups[$active_group_key]['sections'][0] ?? array_key_first($sections);
        $active_section_code = $_GET['section'] ?? $default_section_code;
        if(!in_array($active_section_code, $section_groups[$active_group_key]['sections'])) {
            $active_section_code = $default_section_code;
        }
        
        $ishlanmalar = $this->ishlanmaRepo->findPendingInDepartment($department_id, $selectedPeriodId, $active_section_code);
        $pending_counts = $this->ishlanmaRepo->getPendingCountsBySectionInDepartment($department_id, $selectedPeriodId);
        
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
        
        require_once __DIR__ . '/../../views/department_admin/verify.php';
    }

    public function addUser()
    {
        $this->auth->requireDepartmentAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $auth = $this->auth;
        $current_page = 'dept_admin_add_user';
        
        $users = $this->userRepo->findUsersByDepartment($currentUser['department_id']);
        global $all_periods_for_header, $selectedPeriod;

        require_once __DIR__ . '/../../views/department_admin/add_user.php';
    }
    
    public function targets()
    {
        $this->auth->requireDepartmentAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $auth = $this->auth;
        $current_page = 'dept_admin_targets';
        $departmentId = $currentUser['department_id'];
        global $all_periods_for_header, $selectedPeriod, $is_period_closed;

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        
        $section_groups = $this->ishlanmaRepo->getSectionGroups();
        $sections = $this->ishlanmaRepo->getSections();

        $active_group_key = $_GET['group'] ?? 'all'; 
        if ($active_group_key !== 'all' && !isset($section_groups[$active_group_key])) {
            $active_group_key = 'all';
        }

        $usersInDepartment = $this->userRepo->findUsersByDepartment($departmentId);

        $departmentTargets = [];
        $userTargets = [];

        if ($selectedPeriodId > 0) {
            $departmentTargets = $this->targetRepo->getDepartmentTargetsForPeriod($selectedPeriodId);
            $userTargets = $this->targetRepo->getUserTargetsForDepartment($selectedPeriodId, $departmentId);
        }

        require_once __DIR__ . '/../../views/department_admin/targets.php';
    }
    
    // --- AJAX METODLARI ---

    public function ajaxGetIshlanmaTable()
    {
        $this->auth->requireDepartmentAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $department_id = $currentUser['department_id'];
        $sections = $this->ishlanmaRepo->getSections();
        $section_code = $_GET['section'] ?? '';
        if (empty($section_code) || !isset($sections[$section_code])) {
            $this->jsonResponse(false, 'Noto‘g‘ri bo‘lim tanlandi.');
        }

        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        
        $page_type = $_GET['page_type'] ?? 'department_admin';
        
        if ($page_type === 'verify') {
            $ishlanmalar = $this->ishlanmaRepo->findPendingInDepartment($department_id, $selectedPeriodId, $section_code);
        } else {
            $filters = [
                'status' => $_GET['status'] ?? null,
                'user_id' => $_GET['user_id'] ?? null,
                'section_code' => $section_code,
            ];
            $ishlanmalar = $this->ishlanmaRepo->findAllForDepartment($department_id, $selectedPeriodId, $filters);
        }
        
        $auth = $this->auth;
        global $is_period_closed;

        ob_start();
        include __DIR__ . '/../../views/partials/ishlanma_table_partial.php';
        $html = ob_get_clean();
        $this->jsonResponse(true, 'Jadval yuklandi.', ['html' => $html]);
    }

    public function ajaxGetSubmission()
    {
        $this->auth->requireDepartmentAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_GET['id'] ?? 0);
        
        $submission = $this->ishlanmaRepo->findById($id);
        if (!$submission) {
             $this->jsonResponse(false, 'Ishlanma topilmadi.');
        }
        $submission_owner = $this->userRepo->findById($submission['user_id']);

        if ($submission_owner['department_id'] != $currentUser['department_id']) {
            $this->jsonResponse(false, 'Ishlanma topilmadi yoki ruxsat yo\'q.');
        }

        $submission_data = json_decode($submission['data'], true);
        $response_data = array_merge($submission, ['data' => $submission_data]);
        $this->jsonResponse(true, 'Ma\'lumotlar yuklandi', $response_data);
    }

    public function ajaxUpdateSubmission()
    {
        // Check basic permission
        $this->authorization->requirePermission('edit_department_submissions');
        $this->auth->validateCsrfToken();
        
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_POST['id'] ?? 0);
        $data = $_POST;
        $file = $_FILES['file'] ?? null;

        $submission = $this->ishlanmaRepo->findById($id);
        if (!$submission) {
             $this->jsonResponse(false, 'Ishlanma topilmadi.');
        }
        
        $submission_owner = $this->userRepo->findById($submission['user_id']);
        
        // Check resource-specific access
        if (!$this->authorization->canAccessResource('submission', $id, ['submission_owner' => $submission_owner])) {
            $this->jsonResponse(false, 'Bu ishlanmani tahrirlashga ruxsat yo\'q.');
        }

        if ($this->ishlanmaRepo->update($id, $currentUser['id'], true, $data, $file)) {
            $this->jsonResponse(true, 'Ishlanma muvaffaqiyatli yangilandi.');
        } else {
            $this->jsonResponse(false, 'Ishlanmani yangilashda xatolik yuz berdi yoki hech narsa o\'zgartirilmadi.');
        }
    }

    public function ajaxDeleteSubmission()
    {
        $this->auth->requireDepartmentAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        $id = (int)($_POST['id'] ?? 0);
        
        $submission = $this->ishlanmaRepo->findById($id);
         if (!$submission) {
             $this->jsonResponse(false, 'Ishlanma topilmadi.');
        }
        $submission_owner = $this->userRepo->findById($submission['user_id']);
        if ($submission_owner['department_id'] != $currentUser['department_id']) {
            $this->jsonResponse(false, 'Bu ishlanmani o\'chirishga ruxsat yo\'q.');
        }
        
        if ($this->ishlanmaRepo->delete($id, $currentUser['id'], true)) {
            $this->jsonResponse(true, 'Ishlanma muvaffaqiyatli o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Ishlanmani o\'chirishda xatolik yuz berdi.');
        }
    }
    
    public function ajaxUpdateStatus()
    {
        $this->auth->requireDepartmentAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (empty($id) || !in_array($status, ['approved', 'rejected'])) {
            $this->jsonResponse(false, 'Noto‘g‘ri so‘rov.');
        }
        
        if ($status === 'rejected' && empty($reason)) {
            $this->jsonResponse(false, 'Rad etish sababini kiritish majburiy.');
        }
        
        $submission = $this->ishlanmaRepo->findById($id);
         if (!$submission) {
             $this->jsonResponse(false, 'Ishlanma topilmadi.');
        }
        $submission_owner = $this->userRepo->findById($submission['user_id']);
        if ($submission_owner['department_id'] != $currentUser['department_id']) {
            $this->jsonResponse(false, 'Bu amalni bajarishga ruxsat yo\'q.');
        }
        
        $message = ($status === 'approved') ? 'tasdiqlandi' : 'rad etildi';

        if ($this->ishlanmaRepo->updateStatus($id, $status, $reason)) {
            $new_token = $this->auth->generateCsrfToken();
            
            $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
            $new_counts = $this->ishlanmaRepo->getPendingCountsBySectionInDepartment($currentUser['department_id'], $selectedPeriodId);
            
            $this->jsonResponse(true, "Ishlanma muvaffaqiyatli $message.", ['new_token' => $new_token, 'pending_counts' => $new_counts]);
        } else {
            $this->jsonResponse(false, "Ishlanmani $message xatolik yuz berdi.");
        }
    }

    public function ajaxCreateUser()
    {
        $this->auth->requireDepartmentAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();

        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'role' => 'user',
            'faculty_id' => $currentUser['faculty_id'],
            'department_id' => $currentUser['department_id'],
        ];
        $password_confirm = trim($_POST['password_confirm'] ?? '');

        if (empty($data['full_name']) || empty($data['username']) || empty($data['password'])) {
            $this->jsonResponse(false, 'Barcha majburiy maydonlar to\'ldirilishi kerak.');
        }
        if (strlen($data['password']) < 6) {
            $this->jsonResponse(false, 'Parol kamida 6 ta belgidan iborat bo\'lishi kerak.');
        }
        if ($data['password'] !== $password_confirm) {
            $this->jsonResponse(false, 'Kiritilgan parollar bir-biriga mos kelmadi.');
        }

        if ($this->userRepo->create($data)) {
            $this->jsonResponse(true, 'O\'qituvchi muvaffaqiyatli qo\'shildi.');
        } else {
            $this->jsonResponse(false, 'O\'qituvchi qo\'shishda xatolik yuz berdi. Bunday login mavjud bo\'lishi mumkin.');
        }
    }
    
    public function ajaxUpdateUserInDepartment()
    {
        $this->auth->requireDepartmentAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'role' => 'user', 
            'faculty_id' => $currentUser['faculty_id'],
            'department_id' => $currentUser['department_id'],
        ];
        
        $user_to_edit = $this->userRepo->findById($id);
        if (!$user_to_edit || $user_to_edit['department_id'] != $currentUser['department_id']) {
            $this->jsonResponse(false, 'Bu foydalanuvchini tahrirlashga ruxsat yo\'q.');
        }

        if ($this->userRepo->update($id, $data)) {
            $this->jsonResponse(true, 'Foydalanuvchi ma\'lumotlari yangilandi.');
        } else {
            $this->jsonResponse(false, 'Ma\'lumotlarni yangilashda xatolik yuz berdi yoki hech narsa o\'zgartirilmadi.');
        }
    }

    public function ajaxDeleteUserInDepartment()
    {
        $this->auth->requireDepartmentAdmin();
        $this->auth->validateCsrfToken();
        $currentUser = $this->auth->getCurrentUser();
        
        $id = (int)($_POST['id'] ?? 0);
        if (empty($id)) {
            $this->jsonResponse(false, 'Foydalanuvchi ID si topilmadi.');
        }
        if ($id === $currentUser['id']) {
            $this->jsonResponse(false, 'O\'zingizni o\'chira olmaysiz.');
        }

        $user_to_delete = $this->userRepo->findById($id);
        if (!$user_to_delete || $user_to_delete['department_id'] != $currentUser['department_id']) {
            $this->jsonResponse(false, 'Bu foydalanuvchini o\'chirishga ruxsat yo\'q.');
        }

        if ($this->userRepo->delete($id)) {
            $this->jsonResponse(true, 'Foydalanuvchi muvaffaqiyatli o\'chirildi.');
        } else {
            $this->jsonResponse(false, 'Foydalanuvchini o\'chirishda xatolik yuz berdi.');
        }
    }
    
    public function ajaxSaveUserTargets()
    {
        $this->auth->requireDepartmentAdmin();
        $this->auth->validateCsrfToken();

        $periodId = $_SESSION['selected_period_id'] ?? 0;
        $targets = $_POST['targets'] ?? [];

        if (empty($periodId)) {
            $this->jsonResponse(false, 'Iltimos, avval davrni tanlang.');
            return;
        }

        if ($this->targetRepo->saveUserTargets($periodId, $targets)) {
            $this->jsonResponse(true, 'O\'qituvchilar uchun rejalar muvaffaqiyatli saqlandi.');
        } else {
            $this->jsonResponse(true, 'Rejalar o\'zgartirilmadi. Barcha qiymatlar avvalgidek edi.');
        }
    }
    
    /**
     * Kafedra admini uchun kafedrasidagi rad etilgan ishlanmalar sonini bo'limlar bo'yicha qaytaradi
     */
    public function ajaxGetRejectedCounts()
    {
        $this->auth->requireDepartmentAdmin();
        $currentUser = $this->auth->getCurrentUser();
        $selectedPeriodId = $_SESSION['selected_period_id'] ?? 0;
        
        error_log("Department ajaxGetRejectedCounts called - Department ID: {$currentUser['department_id']}, Period: {$selectedPeriodId}");
        
        if ($selectedPeriodId <= 0) {
            error_log("Department ajaxGetRejectedCounts - No period selected");
            $this->jsonResponse(false, 'Joriy davr tanlanmagan.');
            return;
        }
        
        try {
            $rejectedCounts = $this->ishlanmaRepo->getRejectedCountsBySectionForDepartment($currentUser['department_id'], $selectedPeriodId);
            error_log("Department ajaxGetRejectedCounts - Counts: " . json_encode($rejectedCounts));
            $this->jsonResponse(true, 'Rad etilgan ishlanmalar sonlari yuklandi.', ['rejected_counts' => $rejectedCounts]);
        } catch (\Exception $e) {
            error_log('Error fetching department rejected counts: ' . $e->getMessage());
            $this->jsonResponse(false, 'Xatolik yuz berdi.');
        }
    }

   
   
   
   
  public function exportSection11ToExcel()
{
    $this->auth->requireDepartmentAdmin();
    $currentUser = $this->auth->getCurrentUser();
    $departmentId = $currentUser['department_id'];

    global $selectedPeriod;
    if (!$selectedPeriod || empty($selectedPeriod['id'])) {
        die("Hisobotni yuklab olish uchun avval davrni tanlang.");
    }
    $periodId = $selectedPeriod['id'];

    // --- Ma'lumotlarni yig'ish ---
    $department = $this->departmentRepo->findById($departmentId);
    $departmentName = $department['name'] ?? 'Noma\'lum';

    // Section 1.1 va 1.3 uchun ma'lumotlarni olish
    $section11Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '1.1', 'status' => 'approved']);
    $section13Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '1.3', 'status' => 'approved']);
    
    // Section 2.1.x, 2.2, 2.3, 2.4, 2.5, 2.6.1 va 2.6.2 uchun ma'lumotlarni olish
    $section211Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.1.1', 'status' => 'approved']);
    $section212Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.1.2', 'status' => 'approved']);
    $section213Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.1.3', 'status' => 'approved']);
    $section214Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.1.4', 'status' => 'approved']);
    $section22Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.2', 'status' => 'approved']);
    $section23Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.3', 'status' => 'approved']);
    $section24Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.4', 'status' => 'approved']);
    $section25Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.5', 'status' => 'approved']);
    $section261Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.6.1', 'status' => 'approved']);
    $section262Submissions = $this->ishlanmaRepo->findAllForDepartment($departmentId, $periodId, ['section_code' => '2.6.2', 'status' => 'approved']);
    
    // --- Excel faylini yaratish ---
    $spreadsheet = new Spreadsheet();
    
    // ===== 1.1 BO'LIM (Birinchi sheet) =====
    $sheet1 = $spreadsheet->getActiveSheet();
    $sheet1->setTitle('1.1');

    // 1-qator: "1.1-jadval" o'ng tomonda
    $sheet1->setCellValue('I1', '1.1-jadval');
    $sheet1->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet1->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha
    $sheet1->mergeCells('B2:H2');
    $sheet1->setCellValue('B2', "Professor-o'qituvchilarning doktorlik dissertatsiyasini himoya qilish samaradorligi haqidagi");
    $sheet1->getStyle('B2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet1->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet1->getRowDimension(2)->setRowHeight(20);

    // 3-qator: "M A ' L U M O T"
    $sheet1->mergeCells('B3:H3');
    $sheet1->setCellValue('B3', "M A ' L U M O T");
    $sheet1->getStyle('B3')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet1->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet1->getRowDimension(3)->setRowHeight(20);

    // 4-qator: Bo'sh qator
    $sheet1->getRowDimension(4)->setRowHeight(5);

    // 5-7 qatorlar: Murakkab sarlavhalar
    
    // A ustuni - t/r
    $sheet1->mergeCells('A5:A7');
    $sheet1->setCellValue('A5', 't/r');
    
    // B ustuni - Dissertantning F.I.Sh
    $sheet1->mergeCells('B5:B7');
    $sheet1->setCellValue('B5', "Dissertantning F.I.Sh (qovli tartibida to'liq yoziladi)");
    
    // C ustuni - Ish joyi
    $sheet1->mergeCells('C5:C7');
    $sheet1->setCellValue('C5', "Ish joyi (OTM nomi)");
    
    // D-E ustunlari - Ixtisoslik
    $sheet1->mergeCells('D5:E5');
    $sheet1->setCellValue('D5', 'Ixtisoslik');
    $sheet1->mergeCells('D6:E6');
    $sheet1->setCellValue('D6', 'M A \'  L U M O T');
    $sheet1->setCellValue('D7', 'shifri');
    $sheet1->setCellValue('E7', 'nomi');
    
    // F ustuni - Dissertatsiya mavzusi
    $sheet1->mergeCells('F5:F7');
    $sheet1->setCellValue('F5', 'Dissertatsiya mavzusi');
    
    // G ustuni - Maxsus kengash shifri
    $sheet1->mergeCells('G5:G7');
    $sheet1->setCellValue('G5', 'Maxsus kengash shifri');
    
    // H ustuni - Ilmiy daraja berish to'g'risidagi...
    $sheet1->mergeCells('H5:H7');
    $sheet1->setCellValue('H5', "Ilmiy daraja berish to'g'risidagi Maxsus kengash qarorining OAK tomonidan tasdiqlangan sanasi");

    // Sarlavha stilini qo'llash
    $headerStyle = [
        'font' => ['name' => 'Times New Roman', 'size' => 12, 'bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER, 
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];
    $sheet1->getStyle('A5:H7')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet1->getColumnDimension('A')->setWidth(8);
    $sheet1->getColumnDimension('B')->setWidth(30);
    $sheet1->getColumnDimension('C')->setWidth(15);
    $sheet1->getColumnDimension('D')->setWidth(15);
    $sheet1->getColumnDimension('E')->setWidth(20);
    $sheet1->getColumnDimension('F')->setWidth(25);
    $sheet1->getColumnDimension('G')->setWidth(18);
    $sheet1->getColumnDimension('H')->setWidth(25);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet1->getRowDimension(5)->setRowHeight(60);
    $sheet1->getRowDimension(6)->setRowHeight(20);
    $sheet1->getRowDimension(7)->setRowHeight(30);

    // 1.1 bo'lim ma'lumotlarini to'ldirish
    $row = 8;
    $index = 1;
    
    foreach ($section11Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        // specialty_code_name ni shifri va nomi qismlariga ajratish
        $specialtyCodeName = $data['specialty_code_name'] ?? '';
        $shifri = '';
        $nomi = '';
        
        if (!empty($specialtyCodeName)) {
            // Raqamli qismni (shifri) va harfli qismni (nomi) ajratish
            if (preg_match('/^([0-9\.\-]+)\\s*(.*)$/', trim($specialtyCodeName), $matches)) {
                $shifri = trim($matches[1]);
                $nomi = trim($matches[2]);
            } else {
                // Agar pattern mos kelmasa, to'liq matnni nomiga qo'yamiz
                $nomi = $specialtyCodeName;
            }
        }
        
        $sheet1->setCellValue('A' . $row, $index++);
        $sheet1->setCellValue('B' . $row, $submission['user_name']);
        $sheet1->setCellValue('C' . $row, 'NamDTU'); // Avtomatik NamDTU
        $sheet1->setCellValue('D' . $row, $shifri); // Faqat raqamli qism
        $sheet1->setCellValue('E' . $row, $nomi); // Faqat harfli qism
        $sheet1->setCellValue('F' . $row, $data['dissertation_topic'] ?? '');
        $sheet1->setCellValue('G' . $row, $data['council_code'] ?? '');
        $sheet1->setCellValue('H' . $row, $data['decision_date'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet1->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataRowStyle);
        
        $row++;
    }

    // ===== 1.3 BO'LIM (Ikkinchi sheet) =====
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('1.3');

    // 1-qator: "1.5-jadval" o'ng tomonda
    $sheet2->setCellValue('G1', '1.3-jadval');
    $sheet2->getStyle('G1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet2->getStyle('G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha
    $sheet2->mergeCells('B2:F2');
    $sheet2->setCellValue('B2', "$departmentName da \"Scopus\" bo'yicha Xirsh indeksi");
    $sheet2->getStyle('B2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet2->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet2->getRowDimension(2)->setRowHeight(20);

    // 3-qator: Qo'shimcha sarlavha
    $sheet2->mergeCells('B3:F3');
    $sheet2->setCellValue('B3', "(h-indeks) ≥5 ega bo'lgan professor-o'qituvchilar soni");
    $sheet2->getStyle('B3')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet2->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet2->getRowDimension(3)->setRowHeight(20);

    // 4-qator: "M A ' L U M O T"
    $sheet2->mergeCells('B4:F4');
    $sheet2->setCellValue('B4', "M A ' L U M O T");
    $sheet2->getStyle('B4')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet2->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet2->getRowDimension(4)->setRowHeight(20);

    // 5-qator: Bo'sh qator
    $sheet2->getRowDimension(5)->setRowHeight(5);

    // 6-7 qatorlar: Jadval sarlavhalari
    $sheet2->mergeCells('A6:A7');
    $sheet2->setCellValue('A6', 'T/r');
    
    $sheet2->mergeCells('B6:B7');
    $sheet2->setCellValue('B6', "Xirsh indeksi (h-indeks) ≥5 ega bo'lgan professor-o'qituvchi (lar) F.I.Sh. (alfavit tartibida to'liq yoziladi)");
    
    $sheet2->mergeCells('C6:C7');
    $sheet2->setCellValue('C6', 'Ishlayotgan muassasa');
    
    $sheet2->mergeCells('D6:D7');
    $sheet2->setCellValue('D6', 'Xirsh indeksi (h-indeks)');
    
    $sheet2->mergeCells('E6:E7');
    $sheet2->setCellValue('E6', "Ma'lumotlar olingan davr");
    
    $sheet2->mergeCells('F6:F7');
    $sheet2->setCellValue('F6', 'Elektron internet manzili (giper havola)');

    // Sarlavha stilini qo'llash
    $sheet2->getStyle('A6:F7')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet2->getColumnDimension('A')->setWidth(8);
    $sheet2->getColumnDimension('B')->setWidth(35);
    $sheet2->getColumnDimension('C')->setWidth(18);
    $sheet2->getColumnDimension('D')->setWidth(15);
    $sheet2->getColumnDimension('E')->setWidth(18);
    $sheet2->getColumnDimension('F')->setWidth(25);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet2->getRowDimension(6)->setRowHeight(60);
    $sheet2->getRowDimension(7)->setRowHeight(30);

    // 1.3 bo'lim ma'lumotlarini to'ldirish
    $row = 8;
    $index = 1;
    
    foreach ($section13Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet2->setCellValue('A' . $row, $index++);
        $sheet2->setCellValue('B' . $row, $submission['user_name']);
        $sheet2->setCellValue('C' . $row, 'NamDTU'); // Avtomatik NamDTU
        $sheet2->setCellValue('D' . $row, $data['h_index'] ?? '');
        $sheet2->setCellValue('E' . $row, $data['data_period'] ?? '');
        $sheet2->setCellValue('F' . $row, $data['url'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle2 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet2->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataRowStyle2);
        
        $row++;
    }

    // ===== 2.1.1 BO'LIM (Uchinchi sheet) =====
    $sheet3 = $spreadsheet->createSheet();
    $sheet3->setTitle('2.1.1');

// 1-qator: "2.1.1- jadval" o'ng tomonda (I ustuni)
$sheet3->setCellValue('I1', '2.1.1- jadval');
$sheet3->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
$sheet3->getStyle('I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);




// 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:I2)
$sheet3->mergeCells('A2:I2');
$sheet3->setCellValue('A2', "$departmentName kafedrasida reytingni aniqlash yilida Q2 kvartilda qayd etilgan jurnallarda chop etilgan maqolalar soni haqida\nM A ' L U M O T");
$sheet3->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
$sheet3->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
$sheet3->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)->setWrapText(true);
$sheet3->getRowDimension(2)->setRowHeight(50);
$sheet3->getRowDimension(5)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet3->mergeCells('A3:A3');
    $sheet3->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet3->mergeCells('B3:B3');
    $sheet3->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Xorijiy ilmiy jurnal
    $sheet3->mergeCells('C3:C3');
    $sheet3->setCellValue('C3', "Xorijiy ilmiy jurnal nashr etilgan davlat nomi");
    
    // D ustuni - Ilmiy jurnal nomi
    $sheet3->mergeCells('D3:D3');
    $sheet3->setCellValue('D3', "Ilmiy jurnal nomi");
    
    // E ustuni - Ilmiy maqola nomi
    $sheet3->mergeCells('E3:E3');
    $sheet3->setCellValue('E3', "Ilmiy maqola nomi");
    
    // F ustuni - Nashr yili
    $sheet3->mergeCells('F3:F3');
    $sheet3->setCellValue('F3', "Nashr yili, betlari (2025 yil)");
    
    // G ustuni - Elektron
    $sheet3->mergeCells('G3:G3');
    $sheet3->setCellValue('G3', "Elektron maqolalarning internet manzili (giper xavola)");
    
    // H ustuni - Mualliflar soni
    $sheet3->mergeCells('H3:H3');
    $sheet3->setCellValue('H3', "Mualliflar soni**");

    // I ustuni - ulushi
    $sheet3->mergeCells('I3:I3');
    $sheet3->setCellValue('I3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-9)
    $sheet3->setCellValue('A4', '1');
    $sheet3->setCellValue('B4', '2');
    $sheet3->setCellValue('C4', '3');
    $sheet3->setCellValue('D4', '4');
    $sheet3->setCellValue('E4', '5');
    $sheet3->setCellValue('F4', '6');
    $sheet3->setCellValue('G4', '7');
    $sheet3->setCellValue('H4', '8');
    $sheet3->setCellValue('I4', '9');

    // Sarlavha stilini qo'llash
    $sheet3->getStyle('A3:I4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet3->getColumnDimension('A')->setWidth(8);
    $sheet3->getColumnDimension('B')->setWidth(25);
    $sheet3->getColumnDimension('C')->setWidth(20);
    $sheet3->getColumnDimension('D')->setWidth(20);
    $sheet3->getColumnDimension('E')->setWidth(25);
    $sheet3->getColumnDimension('F')->setWidth(18);
    $sheet3->getColumnDimension('G')->setWidth(20);
    $sheet3->getColumnDimension('H')->setWidth(15);
    $sheet3->getColumnDimension('I')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet3->getRowDimension(3)->setRowHeight(60);
    $sheet3->getRowDimension(4)->setRowHeight(30);

    // 2.1.1 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section211Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet3->setCellValue('A' . $row, $index++);
        $sheet3->setCellValue('B' . $row, $submission['user_name']);
        $sheet3->setCellValue('C' . $row, $data['journal_country'] ?? '');
        $sheet3->setCellValue('D' . $row, $data['journal_name'] ?? '');
        $sheet3->setCellValue('E' . $row, $data['article_name'] ?? '');
        $sheet3->setCellValue('F' . $row, $data['publish_date'] ?? '');
        $sheet3->setCellValue('G' . $row, $data['url'] ?? '');
        $sheet3->setCellValue('H' . $row, $data['authors_count'] ?? '');
        $sheet3->setCellValue('I' . $row, $data['share'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle3 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet3->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle3);
        
        $row++;
    }

    // ===== 2.1.2 BO'LIM (To'rtinchi sheet) =====
    $sheet4 = $spreadsheet->createSheet();
    $sheet4->setTitle('2.1.2');

    // 1-qator: "2.1.2- jadval" o'ng tomonda (I ustuni)
    $sheet4->setCellValue('I1', '2.1.2- jadval');
    $sheet4->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet4->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:I2)
    $sheet4->mergeCells('A2:I2');
    $sheet4->setCellValue('A2', "$departmentName kafedrasida reyitingni aniqlash yilida Q3 kvartilda qayd etilgan jurnallarda chop etilgan maqolalar soni haqida\nM A ' L U M O T");
    $sheet4->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet4->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet4->getRowDimension(2)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet4->mergeCells('A3:A3');
    $sheet4->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet4->mergeCells('B3:B3');
    $sheet4->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Xorijiy ilmiy jurnal
    $sheet4->mergeCells('C3:C3');
    $sheet4->setCellValue('C3', "Xorijiy ilmiy jurnal nashr etilgan davlat nomi");
    
    // D ustuni - Ilmiy jurnal nomi
    $sheet4->mergeCells('D3:D3');
    $sheet4->setCellValue('D3', "Ilmiy jurnal nomi");
    
    // E ustuni - Ilmiy maqola nomi
    $sheet4->mergeCells('E3:E3');
    $sheet4->setCellValue('E3', "Ilmiy maqola nomi");
    
    // F ustuni - Nashr yili
    $sheet4->mergeCells('F3:F3');
    $sheet4->setCellValue('F3', "Nashr yili, betlari (2025 yil)");
    
    // G ustuni - Elektron
    $sheet4->mergeCells('G3:G3');
    $sheet4->setCellValue('G3', "Elektron maqolalarning internet manzili (giper xavola)");
    
    // H ustuni - Mualliflar soni
    $sheet4->mergeCells('H3:H3');
    $sheet4->setCellValue('H3', "Mualliflar soni**");

    // I ustuni - ulushi
    $sheet4->mergeCells('I3:I3');
    $sheet4->setCellValue('I3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-9)
    $sheet4->setCellValue('A4', '1');
    $sheet4->setCellValue('B4', '2');
    $sheet4->setCellValue('C4', '3');
    $sheet4->setCellValue('D4', '4');
    $sheet4->setCellValue('E4', '5');
    $sheet4->setCellValue('F4', '6');
    $sheet4->setCellValue('G4', '7');
    $sheet4->setCellValue('H4', '8');
    $sheet4->setCellValue('I4', '9');

    // Sarlavha stilini qo'llash
    $sheet4->getStyle('A3:I4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet4->getColumnDimension('A')->setWidth(8);
    $sheet4->getColumnDimension('B')->setWidth(25);
    $sheet4->getColumnDimension('C')->setWidth(20);
    $sheet4->getColumnDimension('D')->setWidth(20);
    $sheet4->getColumnDimension('E')->setWidth(25);
    $sheet4->getColumnDimension('F')->setWidth(18);
    $sheet4->getColumnDimension('G')->setWidth(20);
    $sheet4->getColumnDimension('H')->setWidth(15);
    $sheet4->getColumnDimension('I')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet4->getRowDimension(3)->setRowHeight(60);
    $sheet4->getRowDimension(4)->setRowHeight(30);

    // 2.1.2 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section212Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet4->setCellValue('A' . $row, $index++);
        $sheet4->setCellValue('B' . $row, $submission['user_name']);
        $sheet4->setCellValue('C' . $row, $data['journal_country'] ?? '');
        $sheet4->setCellValue('D' . $row, $data['journal_name'] ?? '');
        $sheet4->setCellValue('E' . $row, $data['article_name'] ?? '');
        $sheet4->setCellValue('F' . $row, $data['publish_date'] ?? '');
        $sheet4->setCellValue('G' . $row, $data['url'] ?? '');
        $sheet4->setCellValue('H' . $row, $data['authors_count'] ?? '');
        $sheet4->setCellValue('I' . $row, $data['share'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle4 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet4->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle4);
        
        $row++;
    }

    // ===== 2.1.3 BO'LIM (Beshinchi sheet) =====
    $sheet5 = $spreadsheet->createSheet();
    $sheet5->setTitle('2.1.3');

    // 1-qator: "2.1.3- jadval" o'ng tomonda (I ustuni)
    $sheet5->setCellValue('I1', '2.1.3- jadval');
    $sheet5->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet5->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:I2)
    $sheet5->mergeCells('A2:I2');
    $sheet5->setCellValue('A2', "$departmentName kafedrasida reyitingni aniqlash yilida Q4 kvartilda qayd etilgan jurnallarda chop etilgan maqolalar soni haqida\nM A ' L U M O T");
    $sheet5->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet5->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet5->getRowDimension(2)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet5->mergeCells('A3:A3');
    $sheet5->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet5->mergeCells('B3:B3');
    $sheet5->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Xorijiy ilmiy jurnal
    $sheet5->mergeCells('C3:C3');
    $sheet5->setCellValue('C3', "Xorijiy ilmiy jurnal nashr etilgan davlat nomi");
    
    // D ustuni - Ilmiy jurnal nomi
    $sheet5->mergeCells('D3:D3');
    $sheet5->setCellValue('D3', "Ilmiy jurnal nomi");
    
    // E ustuni - Ilmiy maqola nomi
    $sheet5->mergeCells('E3:E3');
    $sheet5->setCellValue('E3', "Ilmiy maqola nomi");
    
    // F ustuni - Nashr yili
    $sheet5->mergeCells('F3:F3');
    $sheet5->setCellValue('F3', "Nashr yili, betlari (2025 yil)");
    
    // G ustuni - Elektron
    $sheet5->mergeCells('G3:G3');
    $sheet5->setCellValue('G3', "Elektron maqolalarning internet manzili (giper xavola)");
    
    // H ustuni - Mualliflar soni
    $sheet5->mergeCells('H3:H3');
    $sheet5->setCellValue('H3', "Mualliflar soni**");

    // I ustuni - ulushi
    $sheet5->mergeCells('I3:I3');
    $sheet5->setCellValue('I3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-9)
    $sheet5->setCellValue('A4', '1');
    $sheet5->setCellValue('B4', '2');
    $sheet5->setCellValue('C4', '3');
    $sheet5->setCellValue('D4', '4');
    $sheet5->setCellValue('E4', '5');
    $sheet5->setCellValue('F4', '6');
    $sheet5->setCellValue('G4', '7');
    $sheet5->setCellValue('H4', '8');
    $sheet5->setCellValue('I4', '9');

    // Sarlavha stilini qo'llash
    $sheet5->getStyle('A3:I4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet5->getColumnDimension('A')->setWidth(8);
    $sheet5->getColumnDimension('B')->setWidth(25);
    $sheet5->getColumnDimension('C')->setWidth(20);
    $sheet5->getColumnDimension('D')->setWidth(20);
    $sheet5->getColumnDimension('E')->setWidth(25);
    $sheet5->getColumnDimension('F')->setWidth(18);
    $sheet5->getColumnDimension('G')->setWidth(20);
    $sheet5->getColumnDimension('H')->setWidth(15);
    $sheet5->getColumnDimension('I')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet5->getRowDimension(3)->setRowHeight(60);
    $sheet5->getRowDimension(4)->setRowHeight(30);

    // 2.1.3 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section213Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet5->setCellValue('A' . $row, $index++);
        $sheet5->setCellValue('B' . $row, $submission['user_name']);
        $sheet5->setCellValue('C' . $row, $data['journal_country'] ?? '');
        $sheet5->setCellValue('D' . $row, $data['journal_name'] ?? '');
        $sheet5->setCellValue('E' . $row, $data['article_name'] ?? '');
        $sheet5->setCellValue('F' . $row, $data['publish_date'] ?? '');
        $sheet5->setCellValue('G' . $row, $data['url'] ?? '');
        $sheet5->setCellValue('H' . $row, $data['authors_count'] ?? '');
        $sheet5->setCellValue('I' . $row, $data['share'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle5 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet5->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle5);
        
        $row++;
    }

    // ===== 2.1.4 BO'LIM (Oltinchi sheet) =====
    $sheet6 = $spreadsheet->createSheet();
    $sheet6->setTitle('2.1.4');

    // 1-qator: "2.1.4- jadval" o'ng tomonda (I ustuni)
    $sheet6->setCellValue('I1', '2.1.4- jadval');
    $sheet6->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet6->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:I2)
    $sheet6->mergeCells('A2:I2');
    $sheet6->setCellValue('A2', "$departmentName kafedrasida reytingni aniqlash yilida \"Web of Science\", \"Scopus\"da indekslanuvchi konferensiyalarda chop etilgan maqolalar soni haqida\nM A ' L U M O T");
    $sheet6->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet6->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet6->getRowDimension(2)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet6->mergeCells('A3:A3');
    $sheet6->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet6->mergeCells('B3:B3');
    $sheet6->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Xorijiy ilmiy jurnal
    $sheet6->mergeCells('C3:C3');
    $sheet6->setCellValue('C3', "Xorijiy ilmiy jurnal nashr etilgan davlat nomi");
    
    // D ustuni - Ilmiy jurnal nomi
    $sheet6->mergeCells('D3:D3');
    $sheet6->setCellValue('D3', "Ilmiy jurnal nomi");
    
    // E ustuni - Ilmiy maqola nomi
    $sheet6->mergeCells('E3:E3');
    $sheet6->setCellValue('E3', "Ilmiy maqola nomi");
    
    // F ustuni - Nashr yili
    $sheet6->mergeCells('F3:F3');
    $sheet6->setCellValue('F3', "Nashr yili, betlari (2025 yil)");
    
    // G ustuni - Elektron
    $sheet6->mergeCells('G3:G3');
    $sheet6->setCellValue('G3', "Elektron maqolalarning internet manzili (giper xavola)");
    
    // H ustuni - Mualliflar soni
    $sheet6->mergeCells('H3:H3');
    $sheet6->setCellValue('H3', "Mualliflar soni**");

    // I ustuni - ulushi
    $sheet6->mergeCells('I3:I3');
    $sheet6->setCellValue('I3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-9)
    $sheet6->setCellValue('A4', '1');
    $sheet6->setCellValue('B4', '2');
    $sheet6->setCellValue('C4', '3');
    $sheet6->setCellValue('D4', '4');
    $sheet6->setCellValue('E4', '5');
    $sheet6->setCellValue('F4', '6');
    $sheet6->setCellValue('G4', '7');
    $sheet6->setCellValue('H4', '8');
    $sheet6->setCellValue('I4', '9');

    // Sarlavha stilini qo'llash
    $sheet6->getStyle('A3:I4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet6->getColumnDimension('A')->setWidth(8);
    $sheet6->getColumnDimension('B')->setWidth(25);
    $sheet6->getColumnDimension('C')->setWidth(20);
    $sheet6->getColumnDimension('D')->setWidth(20);
    $sheet6->getColumnDimension('E')->setWidth(25);
    $sheet6->getColumnDimension('F')->setWidth(18);
    $sheet6->getColumnDimension('G')->setWidth(20);
    $sheet6->getColumnDimension('H')->setWidth(15);
    $sheet6->getColumnDimension('I')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet6->getRowDimension(3)->setRowHeight(60);
    $sheet6->getRowDimension(4)->setRowHeight(30);

    // 2.1.4 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section214Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet6->setCellValue('A' . $row, $index++);
        $sheet6->setCellValue('B' . $row, $submission['user_name']);
        $sheet6->setCellValue('C' . $row, $data['journal_country'] ?? '');
        $sheet6->setCellValue('D' . $row, $data['journal_name'] ?? '');
        $sheet6->setCellValue('E' . $row, $data['article_name'] ?? '');
        $sheet6->setCellValue('F' . $row, $data['publish_date'] ?? '');
        $sheet6->setCellValue('G' . $row, $data['url'] ?? '');
        $sheet6->setCellValue('H' . $row, $data['authors_count'] ?? '');
        $sheet6->setCellValue('I' . $row, $data['share'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle6 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet6->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle6);
        
        $row++;
    }

    // ===== 2.2 BO'LIM (Yettinchi sheet) =====
    $sheet7 = $spreadsheet->createSheet();
    $sheet7->setTitle('2.2');

    // 1-qator: "2.2- jadval" o'ng tomonda (I ustuni)
    $sheet7->setCellValue('I1', '2.2- jadval');
    $sheet7->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet7->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:I2)
    $sheet7->mergeCells('A2:I2');
    $sheet7->setCellValue('A2', "$departmentName kafedrasida reytingni aniqlash yilida Xorijiy (OAK) jurnallarda chop etilgan ilmiy maqolalar haqida\nM A ' L U M O T");
    $sheet7->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet7->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet7->getRowDimension(2)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet7->mergeCells('A3:A3');
    $sheet7->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet7->mergeCells('B3:B3');
    $sheet7->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Xorijiy ilmiy jurnal
    $sheet7->mergeCells('C3:C3');
    $sheet7->setCellValue('C3', "Xorijiy ilmiy jurnal nashr etilgan davlat nomi");
    
    // D ustuni - Ilmiy jurnal nomi
    $sheet7->mergeCells('D3:D3');
    $sheet7->setCellValue('D3', "Ilmiy jurnal nomi");
    
    // E ustuni - Ilmiy maqola nomi
    $sheet7->mergeCells('E3:E3');
    $sheet7->setCellValue('E3', "Ilmiy maqola nomi");
    
    // F ustuni - Nashr yili
    $sheet7->mergeCells('F3:F3');
    $sheet7->setCellValue('F3', "Nashr yili, betlari (2025 yil)");
    
    // G ustuni - Elektron
    $sheet7->mergeCells('G3:G3');
    $sheet7->setCellValue('G3', "Elektron maqolalarning internet manzili (giper xavola)");
    
    // H ustuni - Mualliflar soni
    $sheet7->mergeCells('H3:H3');
    $sheet7->setCellValue('H3', "Mualliflar soni**");

    // I ustuni - ulushi
    $sheet7->mergeCells('I3:I3');
    $sheet7->setCellValue('I3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-9)
    $sheet7->setCellValue('A4', '1');
    $sheet7->setCellValue('B4', '2');
    $sheet7->setCellValue('C4', '3');
    $sheet7->setCellValue('D4', '4');
    $sheet7->setCellValue('E4', '5');
    $sheet7->setCellValue('F4', '6');
    $sheet7->setCellValue('G4', '7');
    $sheet7->setCellValue('H4', '8');
    $sheet7->setCellValue('I4', '9');

    // Sarlavha stilini qo'llash
    $sheet7->getStyle('A3:I4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet7->getColumnDimension('A')->setWidth(8);
    $sheet7->getColumnDimension('B')->setWidth(25);
    $sheet7->getColumnDimension('C')->setWidth(20);
    $sheet7->getColumnDimension('D')->setWidth(20);
    $sheet7->getColumnDimension('E')->setWidth(25);
    $sheet7->getColumnDimension('F')->setWidth(18);
    $sheet7->getColumnDimension('G')->setWidth(20);
    $sheet7->getColumnDimension('H')->setWidth(15);
    $sheet7->getColumnDimension('I')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet7->getRowDimension(3)->setRowHeight(60);
    $sheet7->getRowDimension(4)->setRowHeight(30);

    // 2.2 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section22Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet7->setCellValue('A' . $row, $index++);
        $sheet7->setCellValue('B' . $row, $submission['user_name']);
        $sheet7->setCellValue('C' . $row, $data['country'] ?? '');
        $sheet7->setCellValue('D' . $row, $data['journal_name'] ?? '');
        $sheet7->setCellValue('E' . $row, $data['article_name'] ?? '');
        $sheet7->setCellValue('F' . $row, $data['publish_date'] ?? '');
        $sheet7->setCellValue('G' . $row, $data['url'] ?? '');
        $sheet7->setCellValue('H' . $row, $data['authors_count'] ?? '');
        $sheet7->setCellValue('I' . $row, $data['share'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle7 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet7->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle7);
        
        $row++;
    }

    // ===== 2.3 BO'LIM (Sakkizinchi sheet) =====
    $sheet8 = $spreadsheet->createSheet();
    $sheet8->setTitle('2.3');

    // 1-qator: "2.3- jadval" o'ng tomonda (H ustuni)
    $sheet8->setCellValue('H1', '2.3- jadval');
    $sheet8->getStyle('H1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet8->getStyle('H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:H2)
    $sheet8->mergeCells('A2:H2');
    $sheet8->setCellValue('A2', "$departmentName kafedrasida reytingni aniqlash yilida Respublika (OAK) jurnallarda chop etilgan ilmiy maqolalar haqida\nM A ' L U M O T");
    $sheet8->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet8->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet8->getRowDimension(2)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet8->mergeCells('A3:A3');
    $sheet8->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet8->mergeCells('B3:B3');
    $sheet8->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Ilmiy jurnal nomi (davlat nomi olib tashlandi)
    $sheet8->mergeCells('C3:C3');
    $sheet8->setCellValue('C3', "Ilmiy jurnal nomi");
    
    // D ustuni - Ilmiy maqola nomi
    $sheet8->mergeCells('D3:D3');
    $sheet8->setCellValue('D3', "Ilmiy maqola nomi");
    
    // E ustuni - Nashr yili
    $sheet8->mergeCells('E3:E3');
    $sheet8->setCellValue('E3', "Nashr yili, betlari (2025 yil)");
    
    // F ustuni - Elektron
    $sheet8->mergeCells('F3:F3');
    $sheet8->setCellValue('F3', "Elektron maqolalarning internet manzili (giper xavola)");
    
    // G ustuni - Mualliflar soni
    $sheet8->mergeCells('G3:G3');
    $sheet8->setCellValue('G3', "Mualliflar soni**");

    // H ustuni - ulushi
    $sheet8->mergeCells('H3:H3');
    $sheet8->setCellValue('H3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-8, davlat ustuni yo'q)
    $sheet8->setCellValue('A4', '1');
    $sheet8->setCellValue('B4', '2');
    $sheet8->setCellValue('C4', '3');
    $sheet8->setCellValue('D4', '4');
    $sheet8->setCellValue('E4', '5');
    $sheet8->setCellValue('F4', '6');
    $sheet8->setCellValue('G4', '7');
    $sheet8->setCellValue('H4', '8');

    // Sarlavha stilini qo'llash
    $sheet8->getStyle('A3:H4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet8->getColumnDimension('A')->setWidth(8);
    $sheet8->getColumnDimension('B')->setWidth(25);
    $sheet8->getColumnDimension('C')->setWidth(25);
    $sheet8->getColumnDimension('D')->setWidth(25);
    $sheet8->getColumnDimension('E')->setWidth(18);
    $sheet8->getColumnDimension('F')->setWidth(20);
    $sheet8->getColumnDimension('G')->setWidth(15);
    $sheet8->getColumnDimension('H')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet8->getRowDimension(3)->setRowHeight(60);
    $sheet8->getRowDimension(4)->setRowHeight(30);

    // 2.3 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section23Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet8->setCellValue('A' . $row, $index++);
        $sheet8->setCellValue('B' . $row, $submission['user_name']);
        $sheet8->setCellValue('C' . $row, $data['journal_name'] ?? ''); // Davlat nomi yo'q
        $sheet8->setCellValue('D' . $row, $data['article_name'] ?? '');
        $sheet8->setCellValue('E' . $row, $data['publish_date'] ?? '');
        $sheet8->setCellValue('F' . $row, $data['url'] ?? '');
        $sheet8->setCellValue('G' . $row, $data['authors_count'] ?? '');
        $sheet8->setCellValue('H' . $row, $data['share'] ?? '');
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle8 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet8->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataRowStyle8);
        
        $row++;
    }

    // ===== 2.4 BO'LIM (To'qqizinchi sheet) =====
    $sheet9 = $spreadsheet->createSheet();
    $sheet9->setTitle('2.4');

    // 1-qator: "2.4- jadval" o'ng tomonda (I ustuni)
    $sheet9->setCellValue('I1', '2.4- jadval');
    $sheet9->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet9->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:I2)
    $sheet9->mergeCells('A2:I2');
    $sheet9->setCellValue('A2', "$departmentName kafedrasida reytingni aniqlash yilida Xalqaro miqyosdagi konferensiyalarda chop etilgan maqolalar soni haqida\nM A ' L U M O T");
    $sheet9->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet9->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet9->getRowDimension(2)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet9->mergeCells('A3:A3');
    $sheet9->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet9->mergeCells('B3:B3');
    $sheet9->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Xalqaro miqyosdagi ilmiy konferensiya o'tkazilgan davlat nomi
    $sheet9->mergeCells('C3:C3');
    $sheet9->setCellValue('C3', "Xalqaro miqyosdagi ilmiy konferensiya o'tkazilgan davlat nomi");
    
    // D ustuni - Xalqaro miqyosdagi ilmiy konferensiya nomi
    $sheet9->mergeCells('D3:D3');
    $sheet9->setCellValue('D3', "Xalqaro miqyosdagi ilmiy konferensiya nomi");
    
    // E ustuni - Ilmiy maqola yoki tezis nomi
    $sheet9->mergeCells('E3:E3');
    $sheet9->setCellValue('E3', "Ilmiy maqola yoki tezis nomi");
    
    // F ustuni - Nashr yili
    $sheet9->mergeCells('F3:F3');
    $sheet9->setCellValue('F3', "Nashr yili, betlari (2024 yil)");
    
    // G ustuni - Elektron
    $sheet9->mergeCells('G3:G3');
    $sheet9->setCellValue('G3', "Elektron maqola va tezislarning internet manzili (giper xavola)");
    
    // H ustuni - Mualliflar soni
    $sheet9->mergeCells('H3:H3');
    $sheet9->setCellValue('H3', "Mualliflar soni**");

    // I ustuni - ulushi
    $sheet9->mergeCells('I3:I3');
    $sheet9->setCellValue('I3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-9)
    $sheet9->setCellValue('A4', '1');
    $sheet9->setCellValue('B4', '2');
    $sheet9->setCellValue('C4', '3');
    $sheet9->setCellValue('D4', '4');
    $sheet9->setCellValue('E4', '5');
    $sheet9->setCellValue('F4', '6');
    $sheet9->setCellValue('G4', '7');
    $sheet9->setCellValue('H4', '8');
    $sheet9->setCellValue('I4', '9');

    // Sarlavha stilini qo'llash
    $sheet9->getStyle('A3:I4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet9->getColumnDimension('A')->setWidth(8);
    $sheet9->getColumnDimension('B')->setWidth(25);
    $sheet9->getColumnDimension('C')->setWidth(25);
    $sheet9->getColumnDimension('D')->setWidth(25);
    $sheet9->getColumnDimension('E')->setWidth(25);
    $sheet9->getColumnDimension('F')->setWidth(18);
    $sheet9->getColumnDimension('G')->setWidth(20);
    $sheet9->getColumnDimension('H')->setWidth(15);
    $sheet9->getColumnDimension('I')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet9->getRowDimension(3)->setRowHeight(60);
    $sheet9->getRowDimension(4)->setRowHeight(30);

    // 2.4 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section24Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet9->setCellValue('A' . $row, $index++);
        $sheet9->setCellValue('B' . $row, $submission['user_name']);
        $sheet9->setCellValue('C' . $row, $data['country'] ?? ''); // Davlat nomi
        $sheet9->setCellValue('D' . $row, $data['conference_name'] ?? ''); // Konferensiya nomi
        $sheet9->setCellValue('E' . $row, $data['article_name'] ?? ''); // Maqola yoki tezis nomi
        $sheet9->setCellValue('F' . $row, $data['publish_date'] ?? ''); // Nashr yili
        $sheet9->setCellValue('G' . $row, $data['url'] ?? ''); // Internet manzili
        $sheet9->setCellValue('H' . $row, $data['authors_count'] ?? ''); // Mualliflar soni
        $sheet9->setCellValue('I' . $row, $data['share'] ?? ''); // Ulushi
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle9 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet9->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle9);
        
        $row++;
    }

    // ===== 2.5 BO'LIM (O'ninchi sheet) =====
    $sheet10 = $spreadsheet->createSheet();
    $sheet10->setTitle('2.5');

    // 1-qator: "2.5- jadval" o'ng tomonda (I ustuni)
    $sheet10->setCellValue('I1', '2.5- jadval');
    $sheet10->getStyle('I1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet10->getStyle('I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "M A ' L U M O T" bitta yacheykada (A2:I2)
    $sheet10->mergeCells('A2:I2');
    $sheet10->setCellValue('A2', "$departmentName kafedrasida reytingni aniqlash yilida Respublika miqyosdagi konferensiyalarda chop etilgan maqolalar soni haqida\nM A ' L U M O T");
    $sheet10->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet10->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet10->getRowDimension(2)->setRowHeight(50);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet10->mergeCells('A3:A3');
    $sheet10->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallifning F.I.Sh.
    $sheet10->mergeCells('B3:B3');
    $sheet10->setCellValue('B3', "Muallifning F.I.Sh.* (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Respublika miqyosdagi ilmiy konferensiya o'tkazilgan joy nomi
    $sheet10->mergeCells('C3:C3');
    $sheet10->setCellValue('C3', "Respublika miqyosdagi ilmiy konferensiya o'tkazilgan joy nomi");
    
    // D ustuni - Respublika miqyosdagi ilmiy konferensiya nomi
    $sheet10->mergeCells('D3:D3');
    $sheet10->setCellValue('D3', "Respublika miqyosdagi ilmiy konferensiya nomi");
    
    // E ustuni - Ilmiy maqola yoki tezis nomi
    $sheet10->mergeCells('E3:E3');
    $sheet10->setCellValue('E3', "Ilmiy maqola yoki tezis nomi");
    
    // F ustuni - Nashr yili
    $sheet10->mergeCells('F3:F3');
    $sheet10->setCellValue('F3', "Nashr yili, betlari (2025 yil)");
    
    // G ustuni - Elektron
    $sheet10->mergeCells('G3:G3');
    $sheet10->setCellValue('G3', "Elektron maqola va tezislarning internet manzili (giper xavola)");
    
    // H ustuni - Mualliflar soni
    $sheet10->mergeCells('H3:H3');
    $sheet10->setCellValue('H3', "Mualliflar soni**");

    // I ustuni - ulushi
    $sheet10->mergeCells('I3:I3');
    $sheet10->setCellValue('I3', 'ulushi');

    // 4-qator: Ustun raqamlari (1-9)
    $sheet10->setCellValue('A4', '1');
    $sheet10->setCellValue('B4', '2');
    $sheet10->setCellValue('C4', '3');
    $sheet10->setCellValue('D4', '4');
    $sheet10->setCellValue('E4', '5');
    $sheet10->setCellValue('F4', '6');
    $sheet10->setCellValue('G4', '7');
    $sheet10->setCellValue('H4', '8');
    $sheet10->setCellValue('I4', '9');

    // Sarlavha stilini qo'llash
    $sheet10->getStyle('A3:I4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet10->getColumnDimension('A')->setWidth(8);
    $sheet10->getColumnDimension('B')->setWidth(25);
    $sheet10->getColumnDimension('C')->setWidth(25);
    $sheet10->getColumnDimension('D')->setWidth(25);
    $sheet10->getColumnDimension('E')->setWidth(25);
    $sheet10->getColumnDimension('F')->setWidth(18);
    $sheet10->getColumnDimension('G')->setWidth(20);
    $sheet10->getColumnDimension('H')->setWidth(15);
    $sheet10->getColumnDimension('I')->setWidth(10);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet10->getRowDimension(3)->setRowHeight(60);
    $sheet10->getRowDimension(4)->setRowHeight(30);

    // 2.5 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section25Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet10->setCellValue('A' . $row, $index++);
        $sheet10->setCellValue('B' . $row, $submission['user_name']);
        $sheet10->setCellValue('C' . $row, $data['location'] ?? ''); // Konferensiya o'tkazilgan joy nomi
        $sheet10->setCellValue('D' . $row, $data['conference_name'] ?? ''); // Konferensiya nomi
        $sheet10->setCellValue('E' . $row, $data['article_name'] ?? ''); // Maqola yoki tezis nomi
        $sheet10->setCellValue('F' . $row, $data['publish_date'] ?? ''); // Nashr yili
        $sheet10->setCellValue('G' . $row, $data['url'] ?? ''); // Internet manzili
        $sheet10->setCellValue('H' . $row, $data['authors_count'] ?? ''); // Mualliflar soni
        $sheet10->setCellValue('I' . $row, $data['share'] ?? ''); // Ulushi
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle10 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet10->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataRowStyle10);
        
        $row++;
    }

    // ===== 2.6.1 BO'LIM (O'n birinchi sheet) =====
    $sheet11 = $spreadsheet->createSheet();
    $sheet11->setTitle('2.6.1');

    // 1-qator: "2.6.1- jadval" o'ng tomonda (H ustuni)
    $sheet11->setCellValue('H1', '2.6.1- jadval');
    $sheet11->getStyle('H1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet11->getStyle('H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "MA'LUMOT" bitta yacheykada (A2:H2)
    $sheet11->mergeCells('A2:H2');
    $sheet11->setCellValue('A2', "$departmentName kafedrasida Xalqaro ko'rsatkichlarga ko'ra professor-o'qituvchilarning ilmiy maqolalariga (\"Web of Science\", \"Scopus\" ilmiy-texnik bazalarida oliy ta'lim tashkiloti afiliatsiya manzili bo'yicha aniqlangan) iqtiboslar haqida\nMA'LUMOT");
    $sheet11->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet11->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet11->getRowDimension(2)->setRowHeight(60);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet11->mergeCells('A3:A3');
    $sheet11->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallif(lar) F.I.Sh.
    $sheet11->mergeCells('B3:B3');
    $sheet11->setCellValue('B3', "Muallif (lar) F.I.Sh. (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Jurnalning nomi
    $sheet11->mergeCells('C3:C3');
    $sheet11->setCellValue('C3', "Jurnalning nomi");
    
    // D ustuni - Jurnalning nashr etilgan yili va oyi
    $sheet11->mergeCells('D3:D3');
    $sheet11->setCellValue('D3', "Jurnalning nashr etilgan yili va oyi");
    
    // E ustuni - Maqolaning nomi
    $sheet11->mergeCells('E3:E3');
    $sheet11->setCellValue('E3', "Maqolaning nomi");
    
    // F ustuni - Maqolaning qaysi tilda chop etilganligi
    $sheet11->mergeCells('F3:F3');
    $sheet11->setCellValue('F3', "Maqolaning qaysi tilda chop etilganligi");
    
    // G ustuni - Internet manzili
    $sheet11->mergeCells('G3:G3');
    $sheet11->setCellValue('G3', "Chop etilgan materiallarning \"Web of Science\", \"Scopus\" xalqaro e'tirof etilgan qidiruv tizimlardagi internet manzili (giper xavolasi)");
    
    // H ustuni - Iqtiboslar soni
    $sheet11->mergeCells('H3:H3');
    $sheet11->setCellValue('H3', "«Web of Science», «Scopus», xalqaro e'tirof etilgan bazalarda mavjud bo'lgan ushbu materiallarga iqtiboslar soni*");

    // 4-qator: Ustun raqamlari (1-8)
    $sheet11->setCellValue('A4', '1');
    $sheet11->setCellValue('B4', '2');
    $sheet11->setCellValue('C4', '3');
    $sheet11->setCellValue('D4', '4');
    $sheet11->setCellValue('E4', '5');
    $sheet11->setCellValue('F4', '6');
    $sheet11->setCellValue('G4', '7');
    $sheet11->setCellValue('H4', '8');

    // Sarlavha stilini qo'llash
    $sheet11->getStyle('A3:H4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet11->getColumnDimension('A')->setWidth(8);
    $sheet11->getColumnDimension('B')->setWidth(30);
    $sheet11->getColumnDimension('C')->setWidth(25);
    $sheet11->getColumnDimension('D')->setWidth(20);
    $sheet11->getColumnDimension('E')->setWidth(30);
    $sheet11->getColumnDimension('F')->setWidth(15);
    $sheet11->getColumnDimension('G')->setWidth(25);
    $sheet11->getColumnDimension('H')->setWidth(20);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet11->getRowDimension(3)->setRowHeight(80);
    $sheet11->getRowDimension(4)->setRowHeight(30);

    // 2.6.1 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section261Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet11->setCellValue('A' . $row, $index++);
        $sheet11->setCellValue('B' . $row, $submission['user_name']);
        $sheet11->setCellValue('C' . $row, $data['journal_name'] ?? ''); // Jurnalning nomi
        $sheet11->setCellValue('D' . $row, $data['publish_date'] ?? ''); // Jurnalning nashr etilgan yili va oyi
        $sheet11->setCellValue('E' . $row, $data['article_name'] ?? ''); // Maqolaning nomi
        $sheet11->setCellValue('F' . $row, $data['publish_lang'] ?? ''); // Maqolaning qaysi tilda chop etilganligi
        $sheet11->setCellValue('G' . $row, $data['url'] ?? ''); // Internet manzili
        $sheet11->setCellValue('H' . $row, $data['citation_count'] ?? ''); // Iqtiboslar soni
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle11 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet11->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataRowStyle11);
        
        $row++;
    }

    // ===== 2.6.2 BO'LIM (O'n ikkinchi sheet) =====
    $sheet12 = $spreadsheet->createSheet();
    $sheet12->setTitle('2.6.2');

    // 1-qator: "2.6.2- jadval" o'ng tomonda (H ustuni)
    $sheet12->setCellValue('H1', '2.6.2- jadval');
    $sheet12->getStyle('H1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet12->getStyle('H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

    // 2-qator: Asosiy sarlavha va "MA'LUMOT" bitta yacheykada (A2:H2)
    $sheet12->mergeCells('A2:H2');
    $sheet12->setCellValue('A2', "$departmentName kafedrasida Xalqaro ko'rsatkichlarga ko'ra professor-o'qituvchilarning ilmiy maqolalariga (\"Web of Science\", \"Scopus\" ilmiy-texnik bazalarida oliy ta'lim tashkiloti afiliatsiya manzili bo'yicha aniqlangan) iqtiboslar haqida\nMA'LUMOT");
    $sheet12->getStyle('A2')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet12->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    $sheet12->getRowDimension(2)->setRowHeight(60);

    // 3-4 qatorlar: Murakkab jadval sarlavhalari
    
    // A ustuni - T/r
    $sheet12->mergeCells('A3:A3');
    $sheet12->setCellValue('A3', 'T/r');
    
    // B ustuni - Muallif(lar) F.I.Sh.
    $sheet12->mergeCells('B3:B3');
    $sheet12->setCellValue('B3', "Muallif (lar) F.I.Sh. (alfavit tartibida to'liq yoziladi)");
    
    // C ustuni - Jurnalning nomi
    $sheet12->mergeCells('C3:C3');
    $sheet12->setCellValue('C3', "Jurnalning nomi");
    
    // D ustuni - Jurnalning nashr etilgan yili va oyi
    $sheet12->mergeCells('D3:D3');
    $sheet12->setCellValue('D3', "Jurnalning nashr etilgan yili va oyi");
    
    // E ustuni - Maqolaning nomi
    $sheet12->mergeCells('E3:E3');
    $sheet12->setCellValue('E3', "Maqolaning nomi");
    
    // F ustuni - Maqolaning qaysi tilda chop etilganligi
    $sheet12->mergeCells('F3:F3');
    $sheet12->setCellValue('F3', "Maqolaning qaysi tilda chop etilganligi");
    
    // G ustuni - Internet manzili
    $sheet12->mergeCells('G3:G3');
    $sheet12->setCellValue('G3', "Chop etilgan materiallarning \"Web of Science\" \"Scopus\" xalqaro e'tirof etilgan qidiruv tizimlardagi internet manzili (giper xavolasi)");
    
    // H ustuni - Iqtiboslar soni
    $sheet12->mergeCells('H3:H3');
    $sheet12->setCellValue('H3', "«Web of Science», «Scopus», xalqaro e'tirof etilgan bazalarda mavjud bo'lgan ushbu materiallarga iqtiboslar soni*");

    // 4-qator: Ustun raqamlari (1-8)
    $sheet12->setCellValue('A4', '1');
    $sheet12->setCellValue('B4', '2');
    $sheet12->setCellValue('C4', '3');
    $sheet12->setCellValue('D4', '4');
    $sheet12->setCellValue('E4', '5');
    $sheet12->setCellValue('F4', '6');
    $sheet12->setCellValue('G4', '7');
    $sheet12->setCellValue('H4', '8');

    // Sarlavha stilini qo'llash
    $sheet12->getStyle('A3:H4')->applyFromArray($headerStyle);

    // Ustun kengliklari
    $sheet12->getColumnDimension('A')->setWidth(8);
    $sheet12->getColumnDimension('B')->setWidth(30);
    $sheet12->getColumnDimension('C')->setWidth(25);
    $sheet12->getColumnDimension('D')->setWidth(20);
    $sheet12->getColumnDimension('E')->setWidth(30);
    $sheet12->getColumnDimension('F')->setWidth(15);
    $sheet12->getColumnDimension('G')->setWidth(25);
    $sheet12->getColumnDimension('H')->setWidth(20);

    // Sarlavha qatorlarini balandligini sozlash
    $sheet12->getRowDimension(3)->setRowHeight(80);
    $sheet12->getRowDimension(4)->setRowHeight(30);

    // 2.6.2 bo'lim ma'lumotlarini to'ldirish
    $row = 5;
    $index = 1;
    
    foreach ($section262Submissions as $submission) {
        $data = json_decode($submission['data'], true);
        
        $sheet12->setCellValue('A' . $row, $index++);
        $sheet12->setCellValue('B' . $row, $submission['user_name']);
        $sheet12->setCellValue('C' . $row, $data['journal_name'] ?? ''); // Jurnalning nomi
        $sheet12->setCellValue('D' . $row, $data['publish_date'] ?? ''); // Jurnalning nashr etilgan yili va oyi
        $sheet12->setCellValue('E' . $row, $data['article_name'] ?? ''); // Maqolaning nomi
        $sheet12->setCellValue('F' . $row, $data['publish_lang'] ?? ''); // Maqolaning qaysi tilda chop etilganligi
        $sheet12->setCellValue('G' . $row, $data['url'] ?? ''); // Internet manzili
        $sheet12->setCellValue('H' . $row, $data['citation_count'] ?? ''); // Iqtiboslar soni
        
        // Har bir qator uchun stil (wrap text bilan va markazda)
        $dataRowStyle12 = [
            'font' => ['name' => 'Times New Roman', 'size' => 12],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ]
        ];
        $sheet12->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataRowStyle12);
        
        $row++;
    }

    // Faylni brauzerga yuklash uchun yuborish
    $filename = str_replace(' ', '_', $departmentName) . '_TSNQB_malumot.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

   
   
   
   
  public function exportToExcel()
{
    $this->auth->requireDepartmentAdmin();
    $currentUser = $this->auth->getCurrentUser();
    $departmentId = $currentUser['department_id'];

    global $selectedPeriod;
    if (!$selectedPeriod || empty($selectedPeriod['id'])) {
        die("Hisobotni yuklab olish uchun avval davrni tanlang.");
    }
    $periodId = $selectedPeriod['id'];

    // --- Ma'lumotlarni yig'ish ---
    $department = $this->departmentRepo->findById($departmentId);
    $departmentName = $department['name'] ?? 'Noma\'lum';

    $progressData = $this->targetRepo->getDepartmentProgress($departmentId, $periodId, $this->ishlanmaRepo, $this->userRepo);
    $userTargets = $this->targetRepo->getUserTargetsForDepartment($periodId, $departmentId);
    
    // --- Excel andozasi tuzilmasini aniqlash ---
    $reportStructure = [
        // ... (reportStructure massivi o'zgarishsiz qoladi)
        ['header' => 'Himoya samaradorligi', 'code' => '1.1'],
        ['header' => '"TOP-500" ilmiy daraja yoki unvoniga ega professor-o\'qituvchilar ulushi', 'code' => '1.2.1'],
        ['header' => '"TOP-1000" ilmiy daraja yoki unvoniga ega professor-o\'qituvchilar ulushi', 'code' => '1.2.2'],
        ['header' => '"TOP-1000" magistratura diplomiga ega professor-o\'qituvchilar ulushi', 'code' => '1.2.3'],
        ['header' => '"Scopus" bo\'yicha Xirsh indeksi (h-indeks) ≥5 ega bo\'lgan professor-o\'qituvchilar ulushi', 'code' => '1.3'],
        ['header' => 'Q2 kvartilda qayd etilgan jurnallarda nashr etilgan maqolalar ulushi', 'code' => '2.1.1'],
        ['header' => 'Q3 kvartilda qayd etilgan jurnallarda nashr etilgan maqolalar ulushi', 'code' => '2.1.2'],
        ['header' => 'Q4 kvartilda qayd etilgan jurnallarda nashr etilgan maqolalar ulushi', 'code' => '2.1.3'],
        ['header' => '"Web of Science", "Scopus"da indekslanuvchi konferensiyalarda nashr etilgan maqolalar ulushi', 'code' => '2.1.4'],
        ['header' => 'Xorijiy (OAK) jurnallarda nashr etilgan maqolalar ulushi', 'code' => '2.2'],
        ['header' => 'Respublika (OAK) jurnallarda nashr etilgan maqolalar ulushi', 'code' => '2.3'],
        ['header' => 'Xalqaro miqyosdagi konferensiyalarda nashr etilgan maqolalar ulushi', 'code' => '2.4'],
        ['header' => 'Respublika miqyosidagi konferensiyalarda nashr etilgan maqolalar ulushi', 'code' => '2.5'],
        ['header' => '"Web of Science", "Scopus" ilmiy texnik bazalarda ... iqtiboslar sonini oshirish', 'code' => '2.6.1'],
        ['header' => '"Google Scholar" ilmiy texnik bazasida ... iqtiboslar sonini oshirish', 'code' => '2.6.2'],
        ['header' => '2024 yilda nashr etilgan darsliklar soni', 'code' => '3.1.1'],
        ['header' => '2024 yilda nashr etilgan o\'quv qo\'llanmalar soni', 'code' => '3.1.2'],
        ['header' => '2024 yilda nashr etilgan monografiyalar soni', 'code' => '3.1.3'],
        ['header' => 'Ixtiro, foydali model, sanoat namunalari va seleksiya yutuqlari uchun olingan patentlar soni', 'code' => '3.2.1'],
        ['header' => 'Axborot kommunikatsiya texnologiyalariga oid dasturlar ... uchun olingan guvohnomalar soni', 'code' => '3.2.2'],
        ['header' => 'Xorijiy grant (jumladan, "soft" komponentli), xorijiy jamg\'arma mablag\'lari soni', 'code' => '3.3'],
        ['header' => 'Soha buyurtmalari asosida ilmiy (ijodiy) tadqiqotlardan olingan mablag\'lar', 'code' => '3.4'],
        ['header' => 'Soha buyurtmalari asosida ilmiy (ijodiy) tadqiqotlardan olingan mablag\'lar 2023 yil qarzdorlik', 'code' => '3.4.1'],
        ['header' => 'Davlat ilmiy loyihalari doirasida tadqiqotlardan olingan mablag\'lar soni', 'code' => '3.5'],
        ['header' => 'Jalb qilingan xorijiy professor-o\'qituvchilar soni', 'code' => '4.1'],
        ['header' => 'Jalb qilingan xorijiy talaba, magistrant yoki doktorantlar soni', 'code' => '4.2'],
        ['header' => 'Xorijiy ilmiy stajirovka va malaka oshirish kurslarida ishtirok etgan professor-o\'qituvchilar soni', 'code' => '4.3'],
        ['header' => 'Akademik almashinuv dasturlarida ishtirok etgan jami talabalar soni', 'code' => '4.4'],
        ['header' => 'Xalqaro sport musobaqalarida sovrinli o\'rinlarni egallagan talabalar soni', 'code' => '5.1'],
        ['header' => 'Respublika sport musobaqalarida sovrinli o\'rinlarni egallagan talabalar soni', 'code' => '5.2']
    ];

    // --- Excel faylini yaratish ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('NamDTU ' . date('Y') . ' reytingi');

    // Oxirgi ustunni dinamik hisoblash
    $lastDataColumnIndex = 5 + count($reportStructure) * 2; 
    $lastDataColLetter = Coordinate::stringFromColumnIndex($lastDataColumnIndex);

    // Sarlavha (1-qator)
    $sheet->mergeCells('A1:Z1');
    $sheet->setCellValue('A1', "Namangan davlat texnika universiteti $departmentName kafedrasining Milliy reyting – " . date('Y') . " bo'yicha yillik hisoboti");
    $sheet->getStyle('A1')->getFont()->setName('Times New Roman')->setSize(12)->setBold(true);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(30);

    // Sarlavhalarni chizish (2, 3, 4-qatorlar)
    $sheet->setCellValue('A2', '№'); $sheet->mergeCells('A2:A4');
    $sheet->setCellValue('B2', 'Kafedra PO\''); $sheet->mergeCells('B2:B4');
    $sheet->setCellValue('C2', 'Kafedra shtat birligi'); $sheet->mergeCells('C2:C4');
    $sheet->setCellValue('D2', 'Professor o\'qituvchilar soni'); $sheet->mergeCells('D2:E3');
    $sheet->setCellValue('D4', 'asosiy'); $sheet->setCellValue('E4', 'o\'rindosh');

    $currentColumnIndex = 6;
    foreach ($reportStructure as $item) {
        $startColLetter = Coordinate::stringFromColumnIndex($currentColumnIndex);
        $endColLetter = Coordinate::stringFromColumnIndex($currentColumnIndex + 1);
        
        $sheet->mergeCells($startColLetter . '2:' . $endColLetter . '2');
        $sheet->setCellValue($startColLetter . '2', $item['header']);
        
        $sheet->mergeCells($startColLetter . '3:' . $endColLetter . '3');
        $sheet->setCellValue($startColLetter . '3', $item['code']);

        $sheet->setCellValue($startColLetter . '4', 'reja');
        $sheet->setCellValue($endColLetter . '4', 'ijro');
        $currentColumnIndex += 2;
    }

    // Stillarni aniqlash
    $headerStyle = [
        'font' => ['name' => 'Times New Roman', 'size' => 10, 'bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
    ];
    $blueFill = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']]]; // Och havorang
	 $whiteFill = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']]]; // Och havorang
    $lightGreenFill = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9EAD3']]]; // Och yashil

    // Sarlavhalarni stillash
    $sheet->getRowDimension(2)->setRowHeight(150);
    $sheet->getStyle('A2:' . $lastDataColLetter . '4')->applyFromArray($headerStyle);
    
    // Sarlavha fonlarini bo'yash (3-QATOR OQ RANGDA)
    $sheet->getStyle('A2:' . $lastDataColLetter . '2')->applyFromArray($blueFill);
    $sheet->getStyle('A4:' . $lastDataColLetter . '4')->applyFromArray($blueFill);
    
    // Ustun kengliklari
    $sheet->getColumnDimension('A')->setWidth(4);
    $sheet->getColumnDimension('B')->setWidth(35);
    $sheet->getColumnDimension('C')->setWidth(10);
    $sheet->getColumnDimension('D')->setWidth(10);
    $sheet->getColumnDimension('E')->setWidth(10);
    for ($i = 6; $i <= $lastDataColumnIndex; $i++) {
        $sheet->getColumnDimensionByColumn($i)->setWidth(8);
    }

    // Ma'lumotlarni to'ldirish
    $row = 5;
    // Kafedra umumiy qatori
    $sheet->mergeCells('A5:E5');
    $sheet->setCellValue('A5', 'Kafedra umumiy rejasi va bajarilishi');

    $currentColumnIndex = 6;
    foreach ($reportStructure as $item) {
        $reja = $progressData['summary'][$item['code']]['target'] ?? 0;
        $ijro = $progressData['summary'][$item['code']]['accomplished'] ?? 0;
        $sheet->setCellValueByColumnAndRow($currentColumnIndex, $row, $reja > 0 ? $reja : '');
        $sheet->setCellValueByColumnAndRow($currentColumnIndex + 1, $row, $ijro > 0 ? $ijro : '');
        $currentColumnIndex += 2;
    }
    
    // A5 dan oxirgi ustungacha och yashil rangga bo'yash
    $summaryRowStyle = [
        'font' => ['name' => 'Times New Roman', 'size' => 10, 'bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => $lightGreenFill['fill']
    ];
    $sheet->getStyle('A5:' . $lastDataColLetter . '5')->applyFromArray($summaryRowStyle);
    
    // O'qituvchilar qatorlari
    $row++;
    if (!empty($progressData['users'])) {
        $startUserRow = $row;
        $endUserRow = $startUserRow + count($progressData['users']) - 1;

        // Barcha o'qituvchilar hududini och havorangga bo'yash
        $sheet->getStyle('A' . $startUserRow . ':' . $lastDataColLetter . $endUserRow)->applyFromArray($whiteFill);

        $userIndex = 1;
        foreach ($progressData['users'] as $userId => $userData) {
            $sheet->setCellValue('A' . $row, $userIndex++);
            $sheet->setCellValue('B' . $row, $userData['full_name']);
            
            $currentColumnIndex = 6;
            foreach ($reportStructure as $item) {
                $reja = $userTargets[$userId][$item['code']] ?? 0;
                $ijro = $userData['progress'][$item['code']]['accomplished'] ?? 0;
                $sheet->setCellValueByColumnAndRow($currentColumnIndex, $row, $reja > 0 ? $reja : '');
                $sheet->setCellValueByColumnAndRow($currentColumnIndex + 1, $row, $ijro > 0 ? $ijro : '');
                $currentColumnIndex += 2;
            }
            // Har bir qator uchun uslub
            $userRowStyle = [
                'font' => ['name' => 'Times New Roman', 'size' => 10],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];
            $sheet->getStyle('A' . $row . ':' . $lastDataColLetter . $row)->applyFromArray($userRowStyle);
            
            $row++;
        }
    }

    // Faylni brauzerga yuklash uchun yuborish
    $filename = str_replace(' ', '_', $departmentName) . '_' . date('Y') . '_hisoboti.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
   
   
   

}

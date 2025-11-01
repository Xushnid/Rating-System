<?php

// 1. Ilovani ishga tushirish (autoloader, .env, vaqt mintaqasi va h.k.)
require_once __DIR__ . '/bootstrap.php';

// 2. Asosiy bog'liqliklarni (Dependencies) yaratish
// Katta loyihalarda bu ishni DI Container bajaradi, hozircha qo'lda yozamiz.
try {
    $db = new App\Database();
    // Make database globally available for header notifications
    $GLOBALS['db'] = $db;
    
    $auth = new App\Auth($db);
    $authorization = new App\Authorization($auth);

    // Repositories (Models)
    $userRepo = new App\User($db);
    $facultyRepo = new App\Faculty($db);
    $departmentRepo = new App\Department($db);
    $ishlanmaRepo = new App\Ishlanma($db);
    $periodRepo = new App\Period($db);
    $targetRepo = new App\Target($db);

    // Controllers
    $authController = new App\Controller\AuthController($auth);
    $dashboardController = new App\Controller\DashboardController($auth, $ishlanmaRepo, $userRepo, $periodRepo, $targetRepo);
    $adminController = new App\Controller\AdminController($auth, $userRepo, $ishlanmaRepo, $facultyRepo, $departmentRepo, $periodRepo, $targetRepo);
    $facultyAdminController = new App\Controller\FacultyAdminController($auth, $userRepo, $ishlanmaRepo, $departmentRepo, $periodRepo, $targetRepo);
    $departmentAdminController = new App\Controller\DepartmentAdminController($auth, $userRepo, $ishlanmaRepo, $departmentRepo, $periodRepo, $targetRepo);

} catch (PDOException $e) {
    // Agar ma'lumotlar bazasiga ulanishda xatolik bo'lsa
    App\ErrorHandler::handleDatabaseError($e);
} catch (Exception $e) {
    // Boshqa xatoliklar
    App\ErrorHandler::handleException($e);
}


// 3. Global Davrni O'rnatish va Boshqarish (YANGI BLOK)
// -------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Barcha davrlarni bazadan bir marta olamiz (headerda ishlatish uchun)
$all_periods_for_header = $periodRepo->findAll();

// Agar POST so'rov orqali headerdan yangi davr tanlansa, uni sessiyaga yozamiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['global_period_id'])) {
    $_SESSION['selected_period_id'] = (int)$_POST['global_period_id'];
    // Qayta o'sha sahifaga yo'naltiramiz (Post/Redirect/Get pattern)
    // Agar referer bo'lmasa, asosiy sahifaga
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? '/';
    header('Location: ' . $redirect_url);
    exit;
}

// Sessiyadan joriy tanlangan davrni olamiz. Agar yo'q bo'lsa, eng oxirgi faol davrni olamiz
$selectedPeriodId = $_SESSION['selected_period_id'] ?? null;
$selectedPeriod = null;

if ($selectedPeriodId) {
    // Barcha davrlar orasidan tanlanganini topamiz
    foreach ($all_periods_for_header as $p) {
        if ($p['id'] == $selectedPeriodId) {
            $selectedPeriod = $p;
            break;
        }
    }
}

// Agar sessiyada davr bo'lmasa yoki topilmasa (masalan, o'chirib yuborilgan bo'lsa), standart davrni belgilaymiz
if (!$selectedPeriod) {
    // Eng birinchi faol davrni topamiz
    foreach ($all_periods_for_header as $p) {
        if ($p['status'] === 'active') {
            $selectedPeriod = $p;
            $selectedPeriodId = $p['id'];
            $_SESSION['selected_period_id'] = $p['id'];
            break;
        }
    }
}
// Agar umuman davr topilmasa (masalan, baza bo'sh bo'lsa), bo'sh qiymatlar beramiz
if (!$selectedPeriod) {
    $selectedPeriod = ['id' => 0, 'status' => 'closed', 'name' => 'Davr mavjud emas']; 
    $selectedPeriodId = 0;
    $_SESSION['selected_period_id'] = 0;
}

// Yakunlangan davrlar uchun tahrirlashni bloklashda ishlatiladigan o'zgaruvchi
$is_period_closed = ($selectedPeriod['status'] === 'closed');
// -------------------------------------------------------------------
// (YANGI BLOK TUGADI)


// 4. Ilovaning barcha marshrutlarini (Routes) aniqlash
$routes = [
    // GET so'rovlari (sahifalarni ochish)
    'GET' => [
        // Asosiy va autentifikatsiya
        '/' => [$dashboardController, 'index'], // Foydalanuvchi tizimga kirgan bo'lsa
        '/login' => [$authController, 'login'],
        '/logout' => [$authController, 'logout'],

        // User & Department Admin (Shaxsiy kabinet)
        '/user' => [$dashboardController, 'index'],
        '/user/ishlanmalar' => [$dashboardController, 'ishlanmalar'],
        '/user/add-ishlanma' => [$dashboardController, 'addIshlanma'],
        '/ajax/user/get-table' => [$dashboardController, 'ajaxGetIshlanmaTable'],
        '/ajax/user/submission/get' => [$dashboardController, 'ajaxGetSubmission'],
        '/ajax/user/rejected-counts' => [$dashboardController, 'ajaxGetRejectedCounts'],

        // Department Admin (Boshqaruv paneli)
        '/department-admin' => [$departmentAdminController, 'index'],
        '/department-admin/submissions' => [$departmentAdminController, 'submissions'],
        '/department-admin/verify' => [$departmentAdminController, 'verify'],
        '/department-admin/add-user' => [$departmentAdminController, 'addUser'],
		'/department-admin/targets' => [$departmentAdminController, 'targets'],
        '/department-admin/export-excel-11' => [$departmentAdminController, 'exportSection11ToExcel'], // <--- YANGI QATOR (Section 1.1)
        '/department-admin/export-excel' => [$departmentAdminController, 'exportToExcel'], // <--- YANGI QATOR

        '/ajax/department-admin/get-table' => [$departmentAdminController, 'ajaxGetIshlanmaTable'],
        '/ajax/department-admin/submission/get' => [$departmentAdminController, 'ajaxGetSubmission'],
        '/ajax/department-admin/rejected-counts' => [$departmentAdminController, 'ajaxGetRejectedCounts'],


        // Faculty Admin
        '/faculty-admin' => [$facultyAdminController, 'index'],
        '/faculty-admin/submissions' => [$facultyAdminController, 'submissions'],
        '/faculty-admin/department-admins' => [$facultyAdminController, 'departmentAdmins'],
        '/ajax/faculty-admin/get-table' => [$facultyAdminController, 'ajaxGetIshlanmaTable'],

        // SuperAdmin
        '/admin' => [$adminController, 'index'],
        '/admin/faculties' => [$adminController, 'faculties'],
        '/admin/departments' => [$adminController, 'departments'],
        '/admin/users' => [$adminController, 'users'],
        '/admin/ishlanmalar' => [$adminController, 'allIshlanmalar'],
        '/admin/verify' => [$adminController, 'verify'],
        '/ajax/admin/get-table' => [$adminController, 'ajaxGetIshlanmaTable'],
        '/ajax/admin/submission/get' => [$adminController, 'ajaxGetSubmission'],
		'/admin/periods' => [$adminController, 'periods'],
		'/admin/targets' => [$adminController, 'targets'],
       
    ],

    // POST so'rovlari (formalar va AJAX so'rovlarini qayta ishlash)
    'POST' => [
        '/login' => [$authController, 'login'],
        
        // User AJAX
        '/ajax/user/submission/create' => [$dashboardController, 'ajaxCreateSubmission'],
        '/ajax/user/submission/update' => [$dashboardController, 'ajaxUpdateSubmission'],
        '/ajax/user/submission/delete' => [$dashboardController, 'ajaxDeleteSubmission'],
        '/ajax/user/profile/update' => [$dashboardController, 'ajaxUpdateProfile'],
        
        // Department Admin AJAX
        '/ajax/department-admin/submission/update' => [$departmentAdminController, 'ajaxUpdateSubmission'],
        '/ajax/department-admin/submission/delete' => [$departmentAdminController, 'ajaxDeleteSubmission'],
       '/ajax/department-admin/submission/status' => [$departmentAdminController, 'ajaxUpdateStatus'],
        
        '/ajax/department-admin/add-user' => [$departmentAdminController, 'ajaxCreateUser'],
        '/ajax/department-admin/update-user' => [$departmentAdminController, 'ajaxUpdateUserInDepartment'],
        '/ajax/department-admin/delete-user' => [$departmentAdminController, 'ajaxDeleteUserInDepartment'],
		'/ajax/department-admin/targets/save' => [$departmentAdminController, 'ajaxSaveUserTargets'],
    
        // Faculty Admin AJAX
        '/ajax/faculty-admin/assign-admin' => [$facultyAdminController, 'ajaxAssignDepartmentAdmin'],

        // SuperAdmin AJAX
        '/ajax/admin/submission/update' => [$adminController, 'ajaxUpdateSubmission'],
        '/ajax/admin/submission/delete' => [$adminController, 'ajaxDeleteSubmission'],
        '/ajax/admin/submission/status' => [$adminController, 'ajaxUpdateStatus'],
        
       
        '/ajax/admin/users/create' => [$adminController, 'ajaxCreateUser'],
        '/ajax/admin/users/update' => [$adminController, 'ajaxUpdateUser'],
        '/ajax/admin/users/delete' => [$adminController, 'ajaxDeleteUser'],
        '/ajax/admin/faculties/create' => [$adminController, 'ajaxCreateFaculty'],
        '/ajax/admin/faculties/update' => [$adminController, 'ajaxUpdateFaculty'],
        '/ajax/admin/faculties/delete' => [$adminController, 'ajaxDeleteFaculty'],
        '/ajax/admin/departments/create' => [$adminController, 'ajaxCreateDepartment'],
        '/ajax/admin/departments/update' => [$adminController, 'ajaxUpdateDepartment'],
        '/ajax/admin/departments/delete' => [$adminController, 'ajaxDeleteDepartment'],
    '/ajax/admin/periods/create' => [$adminController, 'ajaxCreatePeriod'],
    '/ajax/admin/periods/update' => [$adminController, 'ajaxUpdatePeriod'],
    '/ajax/admin/periods/delete' => [$adminController, 'ajaxDeletePeriod'],
	'/ajax/admin/targets/save' => [$adminController, 'ajaxSaveDepartmentTargets'],
    
    ]
];

// 5. Joriy so'rovni marshrutga moslashtirish
$requestMethod = $_SERVER['REQUEST_METHOD'];
$url = $_GET['url'] ?? '';
$requestUri = '/' . rtrim($url, '/');

// Asosiy sahifa ('/') uchun alohida tekshiruv
if ($requestUri === '' || $requestUri === '/') {
    if ($auth->isLoggedIn()) {
         $userRole = $auth->getCurrentUser()['role'];
         switch($userRole) {
            case 'superadmin': header('Location: /admin'); exit();
            case 'facultyadmin': header('Location: /faculty-admin'); exit();
            default: header('Location: /user'); exit();
         }
    } else {
        header('Location: /login');
        exit();
    }
}


// Marshrutni qidirish va ishga tushirish
if (isset($routes[$requestMethod][$requestUri])) {
    [$controller, $method] = $routes[$requestMethod][$requestUri];
    // Obyekt va metod mavjudligini tekshirish
    if (is_object($controller) && method_exists($controller, $method)) {
        try {
            $controller->$method();
        } catch (Exception $e) {
            App\ErrorHandler::handleException($e);
        }
    } else {
        // Bu xatolik server logiga yozilishi kerak
        log_error("Routing Error: Controller or method not found for route '{$requestMethod} {$requestUri}'");
        http_response_code(500);
        
        if ($_ENV['APP_ENV'] === 'production') {
            // Production uchun chiroyli error sahifa yaratish kerak
            echo "<h1>Server Error</h1><p>Iltimos, keyinroq urinib ko'ring.</p>";
        } else {
            echo "Development Error: Controller or method not found for route '{$requestMethod} {$requestUri}'";
        }
    }
} else {
    // Agar marshrut topilmasa 404 xatoligini beramiz
    http_response_code(404);
    
    if ($_ENV['APP_ENV'] === 'production') {
        // Production uchun chiroyli 404 sahifa
        include __DIR__ . '/views/errors/404.php';
    } else {
        echo "<h1>404 - Sahifa Topilmadi</h1><p>Route: {$requestMethod} {$requestUri}</p>";
    }
}
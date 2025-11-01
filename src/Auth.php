<?php

namespace App;

use PDO;

class Auth {
    private $db;
    private $currentUser = null;
    private $isUserFetched = false;

    public function __construct(Database $db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function requireUser() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    public function requireAdmin() {
        if (!$this->isLoggedIn() || $this->getCurrentUser()['role'] !== 'superadmin') {
            header('Location: /login');
            exit;
        }
    }

    /**
     * @deprecated Use Authorization class instead
     */
    public function requireFacultyAdmin() {
        if (!$this->isLoggedIn() || $this->getCurrentUser()['role'] !== 'facultyadmin') {
            header('Location: /login');
            exit;
        }
    }

    /**
     * @deprecated Use Authorization class instead
     */
    public function requireDepartmentAdmin() {
        if (!$this->isLoggedIn() || $this->getCurrentUser()['role'] !== 'departmentadmin') {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Check if user has specific role
     * @deprecated Use Authorization class instead
     */
    public function hasRole(string $role): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $user = $this->getCurrentUser();
        return $user['role'] === $role;
    }

    /**
     * Get authorization instance
     */
    public function getAuthorization(): \App\Authorization
    {
        return new \App\Authorization($this);
    }
	
	
	
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if ($this->isUserFetched) {
            return $this->currentUser;
        }

        if ($this->isLoggedIn()) {
            $stmt = $this->db->query("SELECT * FROM users WHERE id = :id", ['id' => $_SESSION['user_id']]);
            $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $this->currentUser = null;
        }
        
        $this->isUserFetched = true;
        return $this->currentUser;
    }

    public function login($username, $password) {
        $stmt = $this->db->query("SELECT * FROM users WHERE username = :username", ['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }

    public function logout() {
        session_destroy();
    }

    // ==========================================================
    // YANGI METODLAR: CSRF HIMOYA UCHUN
    // ==========================================================

    /**
     * CSRF tokenini yaratadi va sessiyaga saqlaydi.
     * @return string Yaratilgan token.
     */
    public function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Yuborilgan CSRF tokenini sessiyadagi bilan solishtiradi.
     * Agar mos kelmasa, dasturni to'xtatadi.
     */
    public function validateCsrfToken(): void
    {
        if (
            empty($_POST['csrf_token']) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            // Sessiyadagi tokenni tozalaymiz, chunki u eski bo'lishi mumkin
            unset($_SESSION['csrf_token']);
            
            // JSON so'rovlar uchun xatolikni shu formatda qaytaramiz
            header('Content-Type: application/json');
            http_response_code(403); // 403 Forbidden
            echo json_encode(['success' => false, 'message' => 'Xavfsizlik tokeni xatosi. Iltimos, sahifani yangilab, qayta urinib ko\'ring.']);
            exit;
        }
        
        // Muvaffaqiyatli tekshiruvdan so'ng, tokenni yangilaymiz (ixtiyoriy, lekin xavfsizroq)
        unset($_SESSION['csrf_token']);
    }
	
	
}
<?php

namespace App;

use App\Auth;

/**
 * Centralized Authorization System
 * Handles all role-based access control and permissions
 */
class Authorization
{
    private $auth;
    
    // Define role hierarchy levels
    const ROLE_LEVELS = [
        'user' => 1,
        'departmentadmin' => 2,
        'facultyadmin' => 3,
        'superadmin' => 4
    ];
    
    // Define permissions for each role
    const PERMISSIONS = [
        'user' => [
            'view_own_dashboard',
            'create_submission',
            'edit_own_submission',
            'delete_own_submission',
            'view_own_submissions',
            'update_own_profile'
        ],
        'departmentadmin' => [
            'view_department_dashboard',
            'view_department_submissions',
            'edit_department_submissions',
            'delete_department_submissions',
            'approve_submissions',
            'reject_submissions',
            'create_department_users',
            'edit_department_users',
            'delete_department_users',
            'set_user_targets',
            'export_department_data'
        ],
        'facultyadmin' => [
            'view_faculty_dashboard',
            'view_faculty_submissions',
            'view_all_faculty_data',
            'assign_department_admins',
            'view_department_admins'
        ],
        'superadmin' => [
            'view_admin_dashboard',
            'manage_all_users',
            'manage_faculties',
            'manage_departments',
            'manage_periods',
            'manage_targets',
            'view_all_submissions',
            'edit_all_submissions',
            'delete_all_submissions',
            'system_configuration'
        ]
    ];

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Check if user has required role
     */
    public function hasRole(string $requiredRole): bool
    {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }

        $user = $this->auth->getCurrentUser();
        $userRole = $user['role'] ?? 'user';

        return $userRole === $requiredRole;
    }

    /**
     * Check if user has minimum role level
     */
    public function hasMinimumRole(string $minimumRole): bool
    {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }

        $user = $this->auth->getCurrentUser();
        $userRole = $user['role'] ?? 'user';

        $userLevel = self::ROLE_LEVELS[$userRole] ?? 0;
        $requiredLevel = self::ROLE_LEVELS[$minimumRole] ?? 999;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }

        $user = $this->auth->getCurrentUser();
        $userRole = $user['role'] ?? 'user';

        // Get all permissions for user's role and higher roles
        $userPermissions = [];
        
        foreach (self::ROLE_LEVELS as $role => $level) {
            if ($level <= (self::ROLE_LEVELS[$userRole] ?? 0)) {
                $userPermissions = array_merge($userPermissions, self::PERMISSIONS[$role] ?? []);
            }
        }

        return in_array($permission, $userPermissions);
    }

    /**
     * Check if user can access specific resource
     */
    public function canAccessResource(string $resourceType, $resourceId = null, array $additionalParams = []): bool
    {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }

        $user = $this->auth->getCurrentUser();
        $userRole = $user['role'] ?? 'user';

        switch ($resourceType) {
            case 'submission':
                return $this->canAccessSubmission($resourceId, $user, $additionalParams);
            case 'user_management':
                return $this->canManageUser($resourceId, $user, $additionalParams);
            case 'department_data':
                return $this->canAccessDepartmentData($resourceId, $user);
            case 'faculty_data':
                return $this->canAccessFacultyData($resourceId, $user);
            default:
                return false;
        }
    }

    /**
     * Require specific role or throw exception
     */
    public function requireRole(string $requiredRole): void
    {
        if (!$this->hasRole($requiredRole)) {
            $this->denyAccess();
        }
    }

    /**
     * Require minimum role or throw exception
     */
    public function requireMinimumRole(string $minimumRole): void
    {
        if (!$this->hasMinimumRole($minimumRole)) {
            $this->denyAccess();
        }
    }

    /**
     * Require specific permission or throw exception
     */
    public function requirePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            $this->denyAccess();
        }
    }

    /**
     * Deny access and redirect
     */
    private function denyAccess(): void
    {
        if ($this->auth->isLoggedIn()) {
            // User is logged in but doesn't have permission
            http_response_code(403);
            
            // For AJAX requests, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Bu amalni bajarish uchun ruxsat yo\'q.'
                ]);
                exit;
            }
            
            // For regular requests, redirect to appropriate dashboard
            $user = $this->auth->getCurrentUser();
            switch ($user['role']) {
                case 'superadmin':
                    header('Location: /admin');
                    break;
                case 'facultyadmin':
                    header('Location: /faculty-admin');
                    break;
                default:
                    header('Location: /user');
                    break;
            }
            exit;
        } else {
            // User not logged in
            header('Location: /login');
            exit;
        }
    }

    /**
     * Check submission access permissions
     */
    private function canAccessSubmission($submissionId, array $user, array $params = []): bool
    {
        switch ($user['role']) {
            case 'superadmin':
                return true;
            
            case 'facultyadmin':
                // Can access submissions from their faculty
                $submissionOwner = $params['submission_owner'] ?? null;
                return $submissionOwner && $submissionOwner['faculty_id'] == $user['faculty_id'];
            
            case 'departmentadmin':
                // Can access submissions from their department
                $submissionOwner = $params['submission_owner'] ?? null;
                return $submissionOwner && $submissionOwner['department_id'] == $user['department_id'];
            
            case 'user':
                // Can only access their own submissions
                $submissionOwner = $params['submission_owner'] ?? null;
                return $submissionOwner && $submissionOwner['id'] == $user['id'];
            
            default:
                return false;
        }
    }

    /**
     * Check user management permissions
     */
    private function canManageUser($targetUserId, array $user, array $params = []): bool
    {
        switch ($user['role']) {
            case 'superadmin':
                return true;
            
            case 'facultyadmin':
                // Can manage users in their faculty (except other faculty admins)
                $targetUser = $params['target_user'] ?? null;
                return $targetUser && 
                       $targetUser['faculty_id'] == $user['faculty_id'] && 
                       $targetUser['role'] !== 'facultyadmin' &&
                       $targetUser['role'] !== 'superadmin';
            
            case 'departmentadmin':
                // Can manage users in their department (only regular users)
                $targetUser = $params['target_user'] ?? null;
                return $targetUser && 
                       $targetUser['department_id'] == $user['department_id'] && 
                       $targetUser['role'] === 'user';
            
            default:
                return false;
        }
    }

    /**
     * Check department data access permissions
     */
    private function canAccessDepartmentData($departmentId, array $user): bool
    {
        switch ($user['role']) {
            case 'superadmin':
                return true;
            case 'facultyadmin':
                // Need to check if department belongs to their faculty
                return true; // This would require additional database check
            case 'departmentadmin':
                return $user['department_id'] == $departmentId;
            default:
                return false;
        }
    }

    /**
     * Check faculty data access permissions
     */
    private function canAccessFacultyData($facultyId, array $user): bool
    {
        switch ($user['role']) {
            case 'superadmin':
                return true;
            case 'facultyadmin':
                return $user['faculty_id'] == $facultyId;
            default:
                return false;
        }
    }

    /**
     * Get user's role level
     */
    public function getUserRoleLevel(): int
    {
        if (!$this->auth->isLoggedIn()) {
            return 0;
        }

        $user = $this->auth->getCurrentUser();
        $userRole = $user['role'] ?? 'user';

        return self::ROLE_LEVELS[$userRole] ?? 0;
    }

    /**
     * Check if user can perform action on specific entity
     */
    public function canPerformAction(string $action, string $entityType, $entityData = null): bool
    {
        if (!$this->auth->isLoggedIn()) {
            return false;
        }

        $actionPermissionMap = [
            'create' => 'create_' . $entityType,
            'read' => 'view_' . $entityType,
            'update' => 'edit_' . $entityType,
            'delete' => 'delete_' . $entityType,
            'approve' => 'approve_' . $entityType,
            'reject' => 'reject_' . $entityType
        ];

        $permission = $actionPermissionMap[$action] ?? null;
        
        if (!$permission) {
            return false;
        }

        return $this->hasPermission($permission);
    }
}
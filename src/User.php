<?php

namespace App;

class User {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }
public function getDb()
    {
        return $this->db;
    }
    public function findAll() {
        return $this->db->query("SELECT * FROM users ORDER BY full_name")->fetchAll();
    }
    
    /**
     * Foydalanuvchilarni filterlar asosida qidirib topadi.
     * @param array $filters - ['faculty_id' => 1, 'department_id' => 5, 'role' => 'user']
     * @return array
     */
    public function findFilteredUsers(array $filters = []): array
    {
        $sql = "SELECT u.*, d.name as department_name, f.name as faculty_name
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
                LEFT JOIN faculties f ON u.faculty_id = f.id";
        
        $conditions = [];
        $params = [];

        if (!empty($filters['faculty_id'])) {
            $conditions[] = "u.faculty_id = :faculty_id";
            $params['faculty_id'] = $filters['faculty_id'];
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = "u.department_id = :department_id";
            $params['department_id'] = $filters['department_id'];
        }
        if (!empty($filters['role'])) {
            $conditions[] = "u.role = :role";
            $params['role'] = $filters['role'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY u.full_name";
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    public function findById($id) {
        return $this->db->query("SELECT * FROM users WHERE id = :id", ['id' => $id])->fetch();
    }

    public function countByRole($role) {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = :role", ['role' => $role]);
        return $stmt->fetch()['count'];
    }

    public function create($data) {
        $stmt = $this->db->query("SELECT id FROM users WHERE username = :username", ['username' => $data['username']]);
        if ($stmt->fetch()) {
            return false;
        }

        $stmt = $this->db->query(
            "INSERT INTO users (full_name, username, password, role, faculty_id, department_id) VALUES (:full_name, :username, :password, :role, :faculty_id, :department_id)",
            [
                'full_name' => $data['full_name'],
                'username' => $data['username'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'],
                'faculty_id' => $data['faculty_id'] ?: null,
                'department_id' => $data['department_id'] ?: null,
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public function update($id, $data) {
        $stmt = $this->db->query("SELECT id FROM users WHERE username = :username AND id != :id", ['username' => $data['username'], 'id' => $id]);
        if ($stmt->fetch()) {
            return false;
        }

        $params = [
            'id' => $id,
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'role' => $data['role'],
            'faculty_id' => $data['faculty_id'] ?: null,
            'department_id' => $data['department_id'] ?: null,
        ];
        
        if (!empty($data['password'])) {
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET full_name = :full_name, username = :username, password = :password, role = :role, faculty_id = :faculty_id, department_id = :department_id WHERE id = :id";
        } else {
            $query = "UPDATE users SET full_name = :full_name, username = :username, role = :role, faculty_id = :faculty_id, department_id = :department_id WHERE id = :id";
        }
        
        $stmt = $this->db->query($query, $params);
        return $stmt->rowCount() > 0;
    }

    public function updateProfile($id, $data) {
        $stmt = $this->db->query("SELECT id FROM users WHERE username = :username AND id != :id", [
            'username' => $data['username'],
            'id' => $id
        ]);
        if ($stmt->fetch()) {
            return false;
        }

        $params = [
            'id' => $id,
            'username' => $data['username'],
        ];

        if (!empty($data['password'])) {
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET username = :username, password = :password WHERE id = :id";
        } else {
            $query = "UPDATE users SET username = :username WHERE id = :id";
        }

        $stmt = $this->db->query($query, $params);
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        $user = $this->findById($id);
        if ($user && $user['id'] === 1) { // Bosh adminni o'chirishdan himoya
            return false;
        }
        $stmt = $this->db->query("DELETE FROM users WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
	
	
	
	
	// src/User.php fayliga shu metodlarni qo'shing

    public function findUsersByFaculty($faculty_id) {
        return $this->db->query(
            "SELECT id, full_name, username, role, department_id FROM users WHERE faculty_id = :faculty_id ORDER BY full_name",
            ['faculty_id' => $faculty_id]
        )->fetchAll();
    }

    public function setDepartmentAdmin($user_id, $department_id) {
        // Agar department_id null bo'lsa, rolni oddiy userga qaytaramiz
        $role = $department_id ? 'departmentadmin' : 'user';
        
        $stmt = $this->db->query(
            "UPDATE users SET role = :role, department_id = :department_id WHERE id = :id",
            [
                'id' => $user_id,
                'role' => $role,
                'department_id' => $department_id ?: null
            ]
        );
        return $stmt->rowCount() > 0;
    }
	
	
	
	public function findUsersByDepartment($department_id) {
        return $this->db->query(
            "SELECT id, full_name, username FROM users WHERE department_id = :department_id ORDER BY full_name",
            ['department_id' => $department_id]
        )->fetchAll();
    }
}
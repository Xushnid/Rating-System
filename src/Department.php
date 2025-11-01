<?php

namespace App;

use PDO;

class Department {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function findAll() {
        return $this->db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
    }
    
    public function findAllWithFacultyName() {
        $sql = "SELECT d.*, f.name as faculty_name 
                FROM departments d 
                JOIN faculties f ON d.faculty_id = f.id 
                ORDER BY f.name, d.name";
        return $this->db->query($sql)->fetchAll();
    }

    public function findById($id) {
        return $this->db->query("SELECT * FROM departments WHERE id = :id", ['id' => $id])->fetch();
    }

    public function findAllByFacultyId($faculty_id) {
        return $this->db->query(
            "SELECT * FROM departments WHERE faculty_id = :faculty_id ORDER BY name",
            ['faculty_id' => $faculty_id]
        )->fetchAll();
    }

    public function create($data) {
        $stmt = $this->db->query(
            "INSERT INTO departments (name, faculty_id) VALUES (:name, :faculty_id)",
            ['name' => $data['name'], 'faculty_id' => $data['faculty_id']]
        );
        return $stmt->rowCount() > 0;
    }

    public function update($id, $data) {
        $stmt = $this->db->query(
            "UPDATE departments SET name = :name, faculty_id = :faculty_id WHERE id = :id",
            ['id' => $id, 'name' => $data['name'], 'faculty_id' => $data['faculty_id']]
        );
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        $stmt = $this->db->query("DELETE FROM departments WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
	
	
	
	/**
     * Kafedralarni filterlar asosida qidirib topadi.
     * @param array $filters - ['faculty_id' => 1, 'department_id' => 5]
     * @return array
     */
    public function findFiltered(array $filters = []): array
    {
        $sql = "SELECT d.*, f.name as faculty_name 
                FROM departments d 
                JOIN faculties f ON d.faculty_id = f.id";
        
        $conditions = [];
        $params = [];

        if (!empty($filters['faculty_id'])) {
            $conditions[] = "d.faculty_id = :faculty_id";
            $params['faculty_id'] = $filters['faculty_id'];
        }
        // YANGI QO'SHILGAN SHART
        if (!empty($filters['department_id'])) {
            $conditions[] = "d.id = :department_id";
            $params['department_id'] = $filters['department_id'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY f.name, d.name";
        return $this->db->query($sql, $params)->fetchAll();
    }
}
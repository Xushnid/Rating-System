<?php

namespace App;

use PDO;

class Period {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function findAll() {
        return $this->db->query("SELECT * FROM periods ORDER BY start_date DESC")->fetchAll();
    }

    public function findById($id) {
        return $this->db->query("SELECT * FROM periods WHERE id = :id", ['id' => $id])->fetch();
    }

    public function create($data) {
        $stmt = $this->db->query(
            "INSERT INTO periods (name, start_date, end_date, created_by) VALUES (:name, :start_date, :end_date, :created_by)",
            [
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'created_by' => $data['created_by'],
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public function update($id, $data) {
        $stmt = $this->db->query(
            "UPDATE periods SET name = :name, start_date = :start_date, end_date = :end_date, status = :status WHERE id = :id",
            [
                'id' => $id,
                'name' => $data['name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'],
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        // KELAJAKDA: Bu yerga davrga bog'langan rejalar bo'lsa, o'chirishni taqiqlash logikasini qo'shish mumkin.
        $stmt = $this->db->query("DELETE FROM periods WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
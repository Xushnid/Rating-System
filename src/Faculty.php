<?php

namespace App;

use PDO;

class Faculty {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function findAll() {
        return $this->db->query("SELECT * FROM faculties ORDER BY name")->fetchAll();
    }

    public function findById($id) {
        return $this->db->query("SELECT * FROM faculties WHERE id = :id", ['id' => $id])->fetch();
    }

    public function create($data) {
        $stmt = $this->db->query(
            "INSERT INTO faculties (name) VALUES (:name)",
            ['name' => $data['name']]
        );
        return $stmt->rowCount() > 0;
    }

    public function update($id, $data) {
        $stmt = $this->db->query(
            "UPDATE faculties SET name = :name WHERE id = :id",
            ['id' => $id, 'name' => $data['name']]
        );
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        $stmt = $this->db->query("DELETE FROM faculties WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
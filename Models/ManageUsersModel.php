<?php
class ManageUsersModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllUsers() {
        $stmt = $this->db->prepare("SELECT id, name, email, roleid, status FROM users ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toggleUserStatus($user_id, $new_status) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
            return $stmt->execute([$new_status, $user_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUserById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUser($id, $name, $email, $roleid) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, roleid = ? WHERE id = ?");
            return $stmt->execute([$name, $email, $roleid, $id]);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
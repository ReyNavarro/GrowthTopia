<?php

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel
{
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE LOWER(username) = LOWER(?)');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getRoles(): array
    {
        return $this->db->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('
            SELECT u.user_id, u.username, u.role_id, r.role_name,
                   COALESCE(p.can_add, FALSE) AS can_add,
                   COALESCE(p.can_edit, FALSE) AS can_edit,
                   COALESCE(p.can_delete, FALSE) AS can_delete
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN user_action_permissions p ON u.user_id = p.user_id
            ORDER BY u.user_id
        ');
        return $stmt->fetchAll();
    }
}

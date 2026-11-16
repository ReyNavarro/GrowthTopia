<?php

require_once __DIR__ . '/BaseModel.php';

class Farmer extends BaseModel
{
    public function getActiveFarmers(): array
    {
        $stmt = $this->db->query("SELECT farmer_id, first_name, last_name FROM farmers WHERE is_deleted = FALSE ORDER BY last_name, first_name");
        return $stmt->fetchAll();
    }

    public function getSuggestions(int $limit = 100): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT first_name, last_name FROM farmers
             WHERE COALESCE(is_deleted, FALSE) = FALSE ORDER BY first_name, last_name LIMIT $limit"
        );
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM farmers WHERE farmer_id = ? AND is_deleted = FALSE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countAll(string $search = ''): int
    {
        $where = ["f.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.first_name ILIKE ? OR f.last_name ILIKE ? OR f.contact_number ILIKE ? OR f.address ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term, $term, $term);
        }
        $whereSQL = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM farmers f WHERE $whereSQL");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAll(string $search = '', int $limit = 10, int $offset = 0, string $sort = 'f.farmer_id', string $dir = 'ASC'): array
    {
        $where = ["f.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.first_name ILIKE ? OR f.last_name ILIKE ? OR f.contact_number ILIKE ? OR f.address ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term, $term, $term);
        }
        $whereSQL = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT f.*, COUNT(fa.farm_id) AS farm_count
            FROM farmers f
            LEFT JOIN farms fa ON f.farmer_id = fa.farmer_id AND fa.is_deleted = FALSE
            WHERE $whereSQL
            GROUP BY f.farmer_id
            ORDER BY $sort $dir
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO farmers (first_name, last_name, contact_number, address, is_deleted) VALUES (?, ?, ?, ?, FALSE)'
        );
        $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['contact_number'] ?? '',
            $data['address'] ?? '',
        ]);
        return (int)$this->db->lastInsertId('farmers_farmer_id_seq');
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE farmers SET first_name = ?, last_name = ?, contact_number = ?, address = ? WHERE farmer_id = ? AND is_deleted = FALSE'
        );
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['contact_number'] ?? '',
            $data['address'] ?? '',
            $id
        ]);
    }

    public function hasFarms(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM farms WHERE farmer_id = ? AND COALESCE(is_deleted, FALSE) = FALSE');
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE farmers SET is_deleted = TRUE WHERE farmer_id = ?');
        return $stmt->execute([$id]);
    }
}

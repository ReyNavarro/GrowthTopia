<?php

require_once __DIR__ . '/BaseModel.php';

class Planting extends BaseModel
{
    public function getStatuses(): array
    {
        return $this->db->query("SELECT * FROM planting_statuses ORDER BY status_name")->fetchAll();
    }

    public function getActivePlantings(): array
    {
        return $this->db->query(
            "SELECT p.planting_id, f.farm_name, c.crop_name
             FROM plantings p
             JOIN farms f ON f.farm_id = p.farm_id
             JOIN crops c ON c.crop_id = p.crop_id
             WHERE COALESCE(p.is_deleted, FALSE) = FALSE
             ORDER BY p.planting_id DESC"
        )->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM plantings WHERE planting_id = ? AND is_deleted = FALSE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function validateStatus(int $statusId): bool
    {
        $statusCheck = $this->db->prepare('SELECT status_id FROM planting_statuses WHERE status_id = ?');
        $statusCheck->execute([$statusId]);
        return ($statusCheck->fetchColumn() !== false);
    }

    public function countAll(string $search = '', int $statusId = 0): int
    {
        $where = ["p.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.farm_name ILIKE ? OR f.location ILIKE ? OR c.crop_name ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term, $term);
        }
        if ($statusId > 0) {
            $where[] = "p.status_id = ?";
            $params[] = $statusId;
        }
        $whereSQL = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM plantings p
            JOIN farms f ON p.farm_id = f.farm_id
            JOIN crops c ON p.crop_id = c.crop_id
            WHERE $whereSQL
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAll(string $search = '', int $statusId = 0, int $limit = 10, int $offset = 0, string $sort = 'p.planting_id', string $dir = 'DESC'): array
    {
        $where = ["p.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.farm_name ILIKE ? OR f.location ILIKE ? OR c.crop_name ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term, $term);
        }
        if ($statusId > 0) {
            $where[] = "p.status_id = ?";
            $params[] = $statusId;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT p.*, f.farm_name, f.location, c.crop_name, ps.status_name
            FROM plantings p
            JOIN farms f ON p.farm_id = f.farm_id
            JOIN crops c ON p.crop_id = c.crop_id
            JOIN planting_statuses ps ON p.status_id = ps.status_id
            WHERE $whereSQL
            ORDER BY $sort $dir
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $columns = [
            'farm_id', 'crop_id', 'planting_date', 'area_planted',
            'seed_quantity', 'expected_harvest_date',
        ];
        $values = [
            (int)$data['farm_id'],
            (int)$data['crop_id'],
            $data['planting_date'],
            (float)$data['area_planted'],
            (float)$data['seed_quantity'],
            $data['expected_harvest_date'],
        ];

        if ($this->hasColumn('plantings', 'status')) {
            $columns[] = 'status';
            $values[] = '';
        }

        $columns[] = 'status_id';
        $columns[] = 'is_deleted';
        $values[] = (int)$data['status_id'];

        $placeholders = array_fill(0, count($columns) - 1, '?');
        $placeholders[] = 'FALSE';

        $insert = $this->db->prepare(
            'INSERT INTO plantings (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        return $insert->execute($values);
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [
            'farm_id = ?', 'crop_id = ?', 'planting_date = ?', 'area_planted = ?',
            'seed_quantity = ?', 'expected_harvest_date = ?', 'status_id = ?',
        ];
        $values = [
            (int)$data['farm_id'],
            (int)$data['crop_id'],
            $data['planting_date'],
            (float)$data['area_planted'],
            (float)$data['seed_quantity'],
            $data['expected_harvest_date'],
            (int)$data['status_id'],
        ];

        if ($this->hasColumn('plantings', 'status')) {
            $setClauses[] = 'status = ?';
            $values[] = '';
        }

        $values[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE plantings SET ' . implode(', ', $setClauses) . ' WHERE planting_id = ? AND is_deleted = FALSE'
        );
        return $stmt->execute($values);
    }

    public function hasHarvests(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM harvests WHERE planting_id = ? AND COALESCE(is_deleted, FALSE) = FALSE');
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE plantings SET is_deleted = TRUE WHERE planting_id = ?");
        return $stmt->execute([$id]);
    }
}

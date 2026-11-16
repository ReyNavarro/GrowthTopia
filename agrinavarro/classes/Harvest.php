<?php

require_once __DIR__ . '/BaseModel.php';

class Harvest extends BaseModel
{
    public function getGrades(): array
    {
        return $this->db->query("SELECT grade_id, grade_name FROM quality_grades ORDER BY grade_name")->fetchAll();
    }

    public function getRemarks(): array
    {
        return $this->db->query("SELECT remark_id, remark_description FROM harvest_remarks ORDER BY remark_description")->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*, p.planting_date, f.farm_name, c.crop_name, qg.grade_name, hr.remark_description
             FROM harvests h
             JOIN plantings p ON p.planting_id = h.planting_id
             JOIN farms f ON f.farm_id = p.farm_id
             JOIN crops c ON c.crop_id = p.crop_id
             LEFT JOIN quality_grades qg ON qg.grade_id = h.grade_id
             LEFT JOIN harvest_remarks hr ON hr.remark_id = h.remark_id
             WHERE h.harvest_id = ? AND COALESCE(h.is_deleted, FALSE) = FALSE"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getPlantingDate(int $plantingId): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT planting_date FROM plantings WHERE planting_id = ? AND COALESCE(is_deleted, FALSE) = FALSE'
        );
        $stmt->execute([$plantingId]);
        $date = $stmt->fetchColumn();
        return $date !== false ? (string)$date : null;
    }

    public function countAll(string $search = '', int $gradeId = 0): int
    {
        $where = ["COALESCE(h.is_deleted, FALSE) = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.farm_name ILIKE ? OR c.crop_name ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term);
        }
        if ($gradeId > 0) {
            $where[] = "h.grade_id = ?";
            $params[] = $gradeId;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM harvests h
            JOIN plantings p ON h.planting_id = p.planting_id
            JOIN crops c ON p.crop_id = c.crop_id
            JOIN farms f ON p.farm_id = f.farm_id
            WHERE $whereSQL
        ");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAll(string $search = '', int $gradeId = 0, int $limit = 10, int $offset = 0, string $sort = 'h.harvest_id', string $dir = 'DESC'): array
    {
        $where = ["COALESCE(h.is_deleted, FALSE) = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.farm_name ILIKE ? OR c.crop_name ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term);
        }
        if ($gradeId > 0) {
            $where[] = "h.grade_id = ?";
            $params[] = $gradeId;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT h.*, c.crop_name, f.farm_name, qg.grade_name, hr.remark_description
            FROM harvests h
            JOIN plantings p ON h.planting_id = p.planting_id
            JOIN crops c ON p.crop_id = c.crop_id
            JOIN farms f ON p.farm_id = f.farm_id
            LEFT JOIN quality_grades qg ON h.grade_id = qg.grade_id
            LEFT JOIN harvest_remarks hr ON h.remark_id = hr.remark_id
            WHERE $whereSQL
            ORDER BY $sort $dir
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $columns = ['planting_id', 'harvest_date', 'quantity_kg', 'selling_price_per_kg', 'grade_id', 'remark_id'];
        $values = [
            (int)$data['planting_id'],
            $data['harvest_date'],
            (float)$data['quantity_kg'],
            $data['selling_price_per_kg'] !== '' && $data['selling_price_per_kg'] !== null ? (float)$data['selling_price_per_kg'] : null,
            !empty($data['grade_id']) ? (int)$data['grade_id'] : null,
            !empty($data['remark_id']) ? (int)$data['remark_id'] : null,
        ];

        foreach (['quality_grade', 'remarks'] as $legacyColumn) {
            if ($this->hasColumn('harvests', $legacyColumn)) {
                $columns[] = $legacyColumn;
                $values[] = '';
            }
        }

        $columns[] = 'is_deleted';
        $placeholders = array_fill(0, count($columns) - 1, '?');
        $placeholders[] = 'FALSE';

        $insert = $this->db->prepare(
            'INSERT INTO harvests (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        return $insert->execute($values);
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [
            'planting_id = ?', 'harvest_date = ?', 'quantity_kg = ?',
            'selling_price_per_kg = ?', 'grade_id = ?', 'remark_id = ?'
        ];
        $values = [
            (int)$data['planting_id'],
            $data['harvest_date'],
            (float)$data['quantity_kg'],
            $data['selling_price_per_kg'] !== '' && $data['selling_price_per_kg'] !== null ? (float)$data['selling_price_per_kg'] : null,
            !empty($data['grade_id']) ? (int)$data['grade_id'] : null,
            !empty($data['remark_id']) ? (int)$data['remark_id'] : null,
        ];

        foreach (['quality_grade', 'remarks'] as $legacyColumn) {
            if ($this->hasColumn('harvests', $legacyColumn)) {
                $setClauses[] = "$legacyColumn = ?";
                $values[] = '';
            }
        }

        $values[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE harvests SET ' . implode(', ', $setClauses) . ' WHERE harvest_id = ? AND COALESCE(is_deleted, FALSE) = FALSE'
        );
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE harvests SET is_deleted = TRUE WHERE harvest_id = ?");
        return $stmt->execute([$id]);
    }
}

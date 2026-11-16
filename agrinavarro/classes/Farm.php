<?php

require_once __DIR__ . '/BaseModel.php';

class Farm extends BaseModel
{
    public function getSoilTypes(): array
    {
        return $this->db->query("SELECT * FROM soil_types ORDER BY soil_name")->fetchAll();
    }

    public function getIrrigationTypes(): array
    {
        return $this->db->query("SELECT * FROM irrigation_types ORDER BY irrigation_name")->fetchAll();
    }

    public function getActiveFarms(): array
    {
        return $this->db->query("SELECT farm_id, farm_name, location FROM farms WHERE is_deleted = FALSE ORDER BY farm_name")->fetchAll();
    }

    public function getSuggestions(int $limit = 100): array
    {
        return $this->db->query(
            "SELECT DISTINCT farm_name FROM farms WHERE COALESCE(is_deleted, FALSE) = FALSE ORDER BY farm_name LIMIT $limit"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLocationSuggestions(int $limit = 100): array
    {
        return $this->db->query(
            "SELECT DISTINCT location FROM farms WHERE COALESCE(is_deleted, FALSE) = FALSE AND location IS NOT NULL ORDER BY location LIMIT $limit"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM farms WHERE farm_id = ? AND is_deleted = FALSE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function validateTypes(int $soilId, int $irrigationId): bool
    {
        $soilCheck = $this->db->prepare('SELECT soil_id FROM soil_types WHERE soil_id = ?');
        $soilCheck->execute([$soilId]);
        $irrigationCheck = $this->db->prepare('SELECT irrigation_id FROM irrigation_types WHERE irrigation_id = ?');
        $irrigationCheck->execute([$irrigationId]);

        return ($soilCheck->fetchColumn() !== false && $irrigationCheck->fetchColumn() !== false);
    }

    public function countAll(string $search = '', int $soilId = 0, int $irrId = 0): int
    {
        $where = ["f.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.farm_name ILIKE ? OR f.location ILIKE ? OR fa.first_name ILIKE ? OR fa.last_name ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term, $term, $term);
        }
        if ($soilId > 0) {
            $where[] = "f.soil_id = ?";
            $params[] = $soilId;
        }
        if ($irrId > 0) {
            $where[] = "f.irrigation_id = ?";
            $params[] = $irrId;
        }
        $whereSQL = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM farms f JOIN farmers fa ON f.farmer_id = fa.farmer_id WHERE $whereSQL");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAll(string $search = '', int $soilId = 0, int $irrId = 0, int $limit = 10, int $offset = 0, string $sort = 'f.farm_id', string $dir = 'ASC'): array
    {
        $where = ["f.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(f.farm_name ILIKE ? OR f.location ILIKE ? OR fa.first_name ILIKE ? OR fa.last_name ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term, $term, $term);
        }
        if ($soilId > 0) {
            $where[] = "f.soil_id = ?";
            $params[] = $soilId;
        }
        if ($irrId > 0) {
            $where[] = "f.irrigation_id = ?";
            $params[] = $irrId;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT f.*, fa.first_name, fa.last_name, st.soil_name, it.irrigation_name
            FROM farms f
            JOIN farmers fa ON f.farmer_id = fa.farmer_id
            LEFT JOIN soil_types st ON f.soil_id = st.soil_id
            LEFT JOIN irrigation_types it ON f.irrigation_id = it.irrigation_id
            WHERE $whereSQL ORDER BY $sort $dir LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $columns = ['farmer_id', 'farm_name', 'location', 'area_hectares'];
        $values = [
            (int)$data['farmer_id'],
            $data['farm_name'],
            $data['location'],
            (float)$data['area_hectares'],
        ];
        foreach (['soil_type', 'irrigation_type'] as $legacyColumn) {
            if ($this->hasColumn('farms', $legacyColumn)) {
                $columns[] = $legacyColumn;
                $values[] = '';
            }
        }
        $columns[] = 'soil_id';
        $columns[] = 'irrigation_id';
        $columns[] = 'is_deleted';

        $placeholders = array_fill(0, count($columns) - 1, '?');
        $placeholders[] = 'FALSE';

        $insert = $this->db->prepare(
            'INSERT INTO farms (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        $values[] = (int)$data['soil_id'];
        $values[] = (int)$data['irrigation_id'];
        return $insert->execute($values);
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [
            'farmer_id = ?', 'farm_name = ?', 'location = ?', 'area_hectares = ?',
            'soil_id = ?', 'irrigation_id = ?'
        ];
        $values = [
            (int)$data['farmer_id'],
            $data['farm_name'],
            $data['location'],
            (float)$data['area_hectares'],
            (int)$data['soil_id'],
            (int)$data['irrigation_id'],
        ];

        foreach (['soil_type', 'irrigation_type'] as $legacyColumn) {
            if ($this->hasColumn('farms', $legacyColumn)) {
                $setClauses[] = "$legacyColumn = ?";
                $values[] = '';
            }
        }

        $values[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE farms SET ' . implode(', ', $setClauses) . ' WHERE farm_id = ? AND is_deleted = FALSE'
        );
        return $stmt->execute($values);
    }

    public function hasPlantings(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM plantings WHERE farm_id = ? AND COALESCE(is_deleted, FALSE) = FALSE');
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE farms SET is_deleted = TRUE WHERE farm_id = ?");
        return $stmt->execute([$id]);
    }
}

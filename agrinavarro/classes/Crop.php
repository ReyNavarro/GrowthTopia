<?php

require_once __DIR__ . '/BaseModel.php';

class Crop extends BaseModel
{
    public function getCategories(): array
    {
        return $this->db->query("SELECT * FROM crop_categories ORDER BY category_name")->fetchAll();
    }

    public function getSeasons(): array
    {
        return $this->db->query("SELECT * FROM seasons ORDER BY season_name")->fetchAll();
    }

    public function getSuggestions(int $limit = 100): array
    {
        return $this->db->query(
            "SELECT DISTINCT crop_name FROM crops WHERE COALESCE(is_deleted, FALSE) = FALSE ORDER BY crop_name LIMIT $limit"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getVarietySuggestions(int $limit = 100): array
    {
        return $this->db->query(
            "SELECT DISTINCT variety FROM crops WHERE COALESCE(is_deleted, FALSE) = FALSE AND variety IS NOT NULL AND variety <> '' ORDER BY variety LIMIT $limit"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getActiveCrops(): array
    {
        return $this->db->query("SELECT crop_id, crop_name, variety FROM crops WHERE is_deleted = FALSE ORDER BY crop_name")->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM crops WHERE crop_id = ? AND is_deleted = FALSE");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function validateRelations(int $categoryId, int $seasonId): bool
    {
        $categoryCheck = $this->db->prepare('SELECT category_id FROM crop_categories WHERE category_id = ?');
        $categoryCheck->execute([$categoryId]);

        $seasonIsValid = true;
        if ($seasonId > 0) {
            $seasonCheck = $this->db->prepare('SELECT season_id FROM seasons WHERE season_id = ?');
            $seasonCheck->execute([$seasonId]);
            $seasonIsValid = ($seasonCheck->fetchColumn() !== false);
        }

        return ($categoryCheck->fetchColumn() !== false && $seasonIsValid);
    }

    public function countAll(string $search = '', int $catId = 0, int $seaId = 0): int
    {
        $where = ["c.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(c.crop_name ILIKE ? OR c.variety ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term);
        }
        if ($catId > 0) {
            $where[] = "c.category_id = ?";
            $params[] = $catId;
        }
        if ($seaId > 0) {
            $where[] = "c.season_id = ?";
            $params[] = $seaId;
        }
        $whereSQL = implode(' AND ', $where);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM crops c WHERE $whereSQL");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAll(string $search = '', int $catId = 0, int $seaId = 0, int $limit = 10, int $offset = 0, string $sort = 'c.crop_id', string $dir = 'ASC'): array
    {
        $where = ["c.is_deleted = FALSE"];
        $params = [];
        if ($search !== '') {
            $where[] = "(c.crop_name ILIKE ? OR c.variety ILIKE ?)";
            $term = "%$search%";
            array_push($params, $term, $term);
        }
        if ($catId > 0) {
            $where[] = "c.category_id = ?";
            $params[] = $catId;
        }
        if ($seaId > 0) {
            $where[] = "c.season_id = ?";
            $params[] = $seaId;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT c.*, cc.category_name, s.season_name
            FROM crops c
            LEFT JOIN crop_categories cc ON c.category_id = cc.category_id
            LEFT JOIN seasons s ON c.season_id = s.season_id
            WHERE $whereSQL
            ORDER BY $sort $dir
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): bool
    {
        $columns = ['crop_name', 'variety'];
        $values = [$data['crop_name'], $data['variety'] ?? ''];
        foreach (['crop_category', 'recommended_season'] as $legacyColumn) {
            if ($this->hasColumn('crops', $legacyColumn)) {
                $columns[] = $legacyColumn;
                $values[] = '';
            }
        }
        $columns = array_merge($columns, ['category_id', 'growing_period_days', 'season_id', 'is_deleted']);
        $values[] = (int)$data['category_id'];
        $values[] = (int)$data['growing_period_days'];
        $values[] = !empty($data['season_id']) ? (int)$data['season_id'] : null;

        $placeholders = array_fill(0, count($columns) - 1, '?');
        $placeholders[] = 'FALSE';

        $insert = $this->db->prepare(
            'INSERT INTO crops (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
        );
        return $insert->execute($values);
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = ['crop_name = ?', 'variety = ?', 'category_id = ?', 'growing_period_days = ?', 'season_id = ?'];
        $values = [
            $data['crop_name'],
            $data['variety'] ?? '',
            (int)$data['category_id'],
            (int)$data['growing_period_days'],
            !empty($data['season_id']) ? (int)$data['season_id'] : null,
        ];
        foreach (['crop_category', 'recommended_season'] as $legacyColumn) {
            if ($this->hasColumn('crops', $legacyColumn)) {
                $setClauses[] = "$legacyColumn = ?";
                $values[] = '';
            }
        }
        $values[] = $id;

        $stmt = $this->db->prepare('UPDATE crops SET ' . implode(', ', $setClauses) . ' WHERE crop_id = ? AND is_deleted = FALSE');
        return $stmt->execute($values);
    }

    public function hasPlantings(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM plantings WHERE crop_id = ? AND COALESCE(is_deleted, FALSE) = FALSE');
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE crops SET is_deleted = TRUE WHERE crop_id = ?");
        return $stmt->execute([$id]);
    }
}

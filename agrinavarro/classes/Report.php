<?php

require_once __DIR__ . '/BaseModel.php';

class Report extends BaseModel
{
    public function getActiveCounts(): array
    {
        $activeCounts = [];
        $tables = [
            'farmers' => 'Farmers',
            'farms' => 'Farms',
            'crops' => 'Crops',
            'plantings' => 'Plantings',
            'harvests' => 'Harvests'
        ];
        foreach ($tables as $table => $label) {
            $activeCounts[$label] = (int)$this->db->query("SELECT COUNT(*) FROM $table WHERE COALESCE(is_deleted, FALSE) = FALSE")->fetchColumn();
        }
        return $activeCounts;
    }

    public function getHarvestSummary(?string $from = null, ?string $to = null): array
    {
        $where = ['COALESCE(h.is_deleted, FALSE) = FALSE'];
        $params = [];
        if ($from !== null && $from !== '') {
            $where[] = 'h.harvest_date >= ?';
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where[] = 'h.harvest_date <= ?';
            $params[] = $to;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(h.quantity_kg),0) AS total_kg, COALESCE(SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)), 0) AS gross_value FROM harvests h WHERE $whereSQL");
        $stmt->execute($params);
        $res = $stmt->fetch();
        return $res ?: ['total_kg' => 0, 'gross_value' => 0];
    }

    public function getHarvestsByCrop(?string $from = null, ?string $to = null, int $limit = 50): array
    {
        $where = ['COALESCE(h.is_deleted, FALSE) = FALSE', 'COALESCE(p.is_deleted, FALSE) = FALSE'];
        $params = [];
        if ($from !== null && $from !== '') {
            $where[] = 'h.harvest_date >= ?';
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where[] = 'h.harvest_date <= ?';
            $params[] = $to;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT c.crop_name, COUNT(h.harvest_id) AS harvest_count,
                   COALESCE(SUM(h.quantity_kg), 0) AS total_kg,
                   COALESCE(SUM(h.quantity_kg * COALESCE(h.selling_price_per_kg, 0)), 0) AS gross_value
            FROM harvests h
            JOIN plantings p ON h.planting_id = p.planting_id
            JOIN crops c ON p.crop_id = c.crop_id
            WHERE $whereSQL
            GROUP BY c.crop_id, c.crop_name
            ORDER BY total_kg DESC, c.crop_name
            LIMIT $limit
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getHarvestsByGrade(?string $from = null, ?string $to = null): array
    {
        $where = ['COALESCE(h.is_deleted, FALSE) = FALSE'];
        $params = [];
        if ($from !== null && $from !== '') {
            $where[] = 'h.harvest_date >= ?';
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $where[] = 'h.harvest_date <= ?';
            $params[] = $to;
        }
        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT COALESCE(qg.grade_name, 'Unspecified') AS grade_name,
                   COUNT(*) AS harvest_count,
                   COALESCE(SUM(h.quantity_kg), 0) AS total_kg
            FROM harvests h
            LEFT JOIN quality_grades qg ON h.grade_id = qg.grade_id
            WHERE $whereSQL
            GROUP BY qg.grade_name
            ORDER BY total_kg DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFarmerFarms(int $limit = 50): array
    {
        $stmt = $this->db->query("
            SELECT fa.farmer_id, fa.first_name, fa.last_name,
                   COUNT(f.farm_id) AS farm_count,
                   COALESCE(SUM(f.area_hectares), 0) AS area_hectares
            FROM farmers fa
            LEFT JOIN farms f ON f.farmer_id = fa.farmer_id AND COALESCE(f.is_deleted, FALSE) = FALSE
            WHERE COALESCE(fa.is_deleted, FALSE) = FALSE
            GROUP BY fa.farmer_id, fa.first_name, fa.last_name
            ORDER BY fa.last_name, fa.first_name
            LIMIT $limit
        ");
        return $stmt->fetchAll();
    }
}

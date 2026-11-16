<?php

require_once __DIR__ . '/Database.php';

abstract class BaseModel
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function getDb(): PDO
    {
        return $this->db;
    }

    public function hasColumn(string $table, string $column): bool
    {
        require_once __DIR__ . '/../includes/db.php';
        return hasDBColumn($table, $column);
    }
}

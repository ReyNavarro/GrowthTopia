<?php

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        require_once __DIR__ . '/../includes/db.php';
        self::$instance = getDB();
        return self::$instance;
    }
}

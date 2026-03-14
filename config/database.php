<?php

// 简单的 PDO 单例封装，后续所有页面统一通过此类访问数据库。

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        // 默认配置：请根据本地环境自行修改
        $host = getenv('ICAMPUS_DB_HOST') ?: 'localhost';
        $dbname = getenv('ICAMPUS_DB_NAME') ?: 'icampus';
        $user = getenv('ICAMPUS_DB_USER') ?: 'icampus';
        $pass = getenv('ICAMPUS_DB_PASS') ?: 'aAXsDm2P8LCEzAWB';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // 为了保证首页在数据库未就绪时仍可访问，这里不直接抛出致命错误。
            $this->pdo = null;
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isAvailable(): bool
    {
        return $this->pdo !== null;
    }

    public function pdo(): ?PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): array
    {
        if (!$this->pdo) {
            return [];
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function queryOne(string $sql, array $params = []): ?array
    {
        $rows = $this->query($sql, $params);
        return $rows[0] ?? null;
    }

    public function execute(string $sql, array $params = []): int
    {
        if (!$this->pdo) {
            return 0;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): int
    {
        if (!$this->pdo) {
            return 0;
        }
        return (int)$this->pdo->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        if (!$this->pdo) {
            return false;
        }
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        if (!$this->pdo) {
            return false;
        }
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        if (!$this->pdo) {
            return false;
        }
        return $this->pdo->rollBack();
    }
}


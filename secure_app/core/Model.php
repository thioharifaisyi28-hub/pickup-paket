<?php
/**
 * Model.php - Base Model
 */

abstract class Model
{
    protected string $table;
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find by ID
     */
    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }

    /**
     * Get all records
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Insert record
     */
    public function create(array $data): bool|string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $this->db->query($sql, $data);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): bool
    {
        $fields = '';
        foreach ($data as $key => $value) {
            $fields .= "{$key} = :{$key}, ";
        }
        $fields = rtrim($fields, ', ');
        
        $sql = "UPDATE {$this->table} SET {$fields} WHERE id = :id";
        $data['id'] = $id;
        
        try {
            $this->db->query($sql, $data);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        try {
            $this->db->query($sql, ['id' => $id]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Count total records
     */
    public function count(string $condition = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        if ($condition) {
            $sql .= " WHERE {$condition}";
        }
        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }
}

<?php
/**
 * User.php - User Model
 */

require_once BASE_PATH . '/core/Model.php';

class User extends Model
{
    protected string $table = 'users';

    /**
     * Find User by Username
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        return $this->db->fetch($sql, ['username' => $username]);
    }

    /**
     * Cek apakah username exist kecuali id tertentu (untuk validasi edit)
     */
    public function isUsernameExists(string $username, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE username = :username AND id != :id LIMIT 1";
        $result = $this->db->fetch($sql, ['username' => $username, 'id' => $excludeId]);
        return $result !== null;
    }
}

<?php
/**
 * FotoInstan.php - Model untuk menyimpan foto paket instan
 */

require_once BASE_PATH . '/core/Model.php';

class FotoInstan extends Model
{
    protected string $table = 'foto_instan';

    /**
     * Simpan data foto baru
     */
    public function savePhoto($paketId, $path, $userId)
    {
        return $this->create([
            'paket_id'   => $paketId,
            'path_foto'  => $path,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $userId
        ]);
    }

    /**
     * Ambil semua foto untuk satu paket
     */
    public function getByPaketId($paketId)
    {
        $sql = "
            SELECT f.*, u.username as petugas 
            FROM {$this->table} f
            LEFT JOIN users u ON f.created_by = u.id
            WHERE f.paket_id = :paket_id AND f.deleted_at IS NULL
            ORDER BY f.created_at DESC
        ";
        return $this->db->fetchAll($sql, ['paket_id' => $paketId]);
    }

    /**
     * Soft delete foto
     */
    public function softDelete($id)
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }
}

<?php
/**
 * Paket.php - Paket Model (Terintegrasi Priority Scheduling)
 */

require_once BASE_PATH . '/core/Model.php';

class Paket extends Model
{
    protected string $table = 'paket';

    /**
     * Get all packages with related users (for datatable/listing)
     * Mendukung mode pengurutan Priority Scheduling ('priority') maupun baseline FIFO ('fifo').
     */
    public function getAllWithUser(
        int $limit = 0,
        int $offset = 0,
        string $search = '',
        string $status = '',
        int $userId = 0,
        string $tanggal = '',
        string $sortMode = 'priority'
    ): array {
        $sql = "
            SELECT p.*, u.username as input_by, 
            (SELECT path_foto FROM foto_instan WHERE paket_id = p.id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1) as latest_photo
            FROM {$this->table} p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.is_deleted = 0
        ";
        $params = [];

        if ($search) {
            $sql .= " AND (p.no_pesanan LIKE :search1 OR p.nama_pemesan LIKE :search2 OR p.no_paket LIKE :search3 OR p.nama_ekspedisi LIKE :search4 OR p.kode LIKE :search5)";
            $params['search1'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
            $params['search4'] = "%{$search}%";
            $params['search5'] = "%{$search}%";
        }

        if ($status) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        }

        if ($userId > 0) {
            $sql .= " AND p.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        if ($tanggal) {
            // Menggunakan perbandingan langsung pada kolom DATE agar indeks komposit idx_priority_queue optimal
            $sql .= " AND p.tanggal = :tanggal";
            $params['tanggal'] = $tanggal;
        }

        if ($sortMode === 'fifo') {
            $sql .= " ORDER BY p.id ASC";
        } elseif ($status !== '') {
            // Memanfaatkan indeks idx_priority_queue secara langsung tanpa filesort
            $sql .= " ORDER BY p.priority_score DESC, p.tanggal_masuk ASC, p.id ASC";
        } else {
            // Menampilkan antrean Pending di urutan teratas berdasarkan skor prioritas tertinggi
            $sql .= " ORDER BY (CASE WHEN p.status = 'Pending' THEN 0 ELSE 1 END) ASC, p.priority_score DESC, p.tanggal_masuk ASC, p.id ASC";
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Count for statistics/pagination
     */
    public function countFiltered(string $search = '', string $status = '', int $userId = 0, string $tanggal = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE is_deleted = 0";
        $params = [];

        if ($search) {
            $sql .= " AND (no_pesanan LIKE :search1 OR nama_pemesan LIKE :search2 OR no_paket LIKE :search3 OR nama_ekspedisi LIKE :search4 OR kode LIKE :search5)";
            $params['search1'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
            $params['search4'] = "%{$search}%";
            $params['search5'] = "%{$search}%";
        }

        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }

        if ($userId > 0) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        if ($tanggal) {
            $sql .= " AND tanggal = :tanggal";
            $params['tanggal'] = $tanggal;
        }

        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get Total By Status
     */
    public function countByStatus(string $status, int $userId = 0): int
    {
        $condition = "status = :status AND is_deleted = 0";
        $params = ['status' => $status];

        if ($userId > 0) {
            $condition .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }

        return $this->count($condition, $params);
    }

    /**
     * Get the latest no_paket sequence, optionally filtered by date
     */
    public function getMaxNoPaket(?string $tanggal = null): int
    {
        $sql = "SELECT MAX(no_paket) as max_no FROM {$this->table} WHERE is_deleted = 0";
        $params = [];

        if ($tanggal) {
            $sql .= " AND tanggal = :tanggal";
            $params['tanggal'] = $tanggal;
        }

        $result = $this->db->fetch($sql, $params);
        return (int)($result['max_no'] ?? 0);
    }

    /**
     * Get package trend for last 7 days
     */
    public function getDailyTrend(int $userId = 0): array
    {
        $sql = "
            SELECT DATE(tanggal_masuk) as date, COUNT(*) as count 
            FROM {$this->table} 
            WHERE tanggal_masuk >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND is_deleted = 0
        ";
        $params = [];
        
        if ($userId > 0) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        $sql .= " GROUP BY DATE(tanggal_masuk) ORDER BY date ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Soft delete a package
     * Updates is_deleted and modifies no_pesanan to free up the unique constraint
     */
    public function softDelete(int $id): bool
    {
        // Add prefix and ID to no_pesanan to free up unique constraint for new items
        $sql = "UPDATE {$this->table} SET is_deleted = 1, no_pesanan = CONCAT('[DEL]-', id, '-', no_pesanan) WHERE id = :id AND is_deleted = 0";
        try {
            $result = $this->db->query($sql, ['id' => $id]);
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Paket softDelete failed for ID {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get status distribution for chart
     */
    public function getStatusDistribution(int $userId = 0, string $tanggal = ''): array
    {
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} WHERE is_deleted = 0";
        $params = [];
        
        if ($userId > 0) {
            $sql .= " AND user_id = :user_id";
            $params['user_id'] = $userId;
        }
        
        if ($tanggal) {
            $sql .= " AND tanggal = :tanggal";
            $params['tanggal'] = $tanggal;
        }
        
        $sql .= " GROUP BY status";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Bulk soft delete multiple packages (Admin only)
     * Adds prefix to no_pesanan to free up unique constraint
     */
    public function bulkSoftDelete(array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'deleted' => 0, 'failed' => 0, 'message' => 'Tidak ada ID yang dipilih'];
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $checkSql = "SELECT id FROM {$this->table} WHERE id IN ({$placeholders}) AND is_deleted = 0";
        
        $validIds = [];
        try {
            $validRows = $this->db->fetchAll($checkSql, $ids);
            $validIds = array_column($validRows, 'id');
            
            if (empty($validIds)) {
                return ['success' => false, 'deleted' => 0, 'failed' => 0, 'message' => 'Semua paket sudah dihapus atau tidak ditemukan'];
            }
            
            $placeholders = str_repeat('?,', count($validIds) - 1) . '?';
            $updateSql = "UPDATE {$this->table} SET is_deleted = 1, no_pesanan = CONCAT('[DEL]-', id, '-', no_pesanan) WHERE id IN ({$placeholders})";
            
            $result = $this->db->query($updateSql, $validIds);
            $deleted = $result->rowCount();
            $failed = count($ids) - count($validIds) + (count($validIds) - $deleted);
            
            return [
                'success' => true, 
                'deleted' => $deleted,
                'failed' => $failed,
                'message' => "Berhasil menghapus {$deleted} paket" . ($failed > 0 ? " ({$failed} gagal)" : "")
            ];
            
        } catch (PDOException $e) {
            error_log("Paket bulkSoftDelete failed: " . $e->getMessage());
            return ['success' => false, 'deleted' => 0, 'failed' => count($ids), 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get a package by its order number (excludes soft-deleted)
     */
    public function getByNoPesanan(string $noPesanan): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE no_pesanan = :no_pesanan AND is_deleted = 0";
        return $this->db->fetch($sql, ['no_pesanan' => $noPesanan]) ?: null;
    }

    /**
     * Get IDs of all packages that match filters AND are selectable (not Pending + no photo)
     */
    public function getSelectableIds(string $search = '', string $status = '', int $userId = 0, string $tanggal = ''): array
    {
        $sql = "
            SELECT p.id 
            FROM {$this->table} p
            LEFT JOIN foto_instan f ON p.id = f.paket_id AND f.deleted_at IS NULL
            WHERE p.is_deleted = 0 
            AND p.status != 'Pending'
            AND f.id IS NULL
        ";
        $params = [];

        if ($search) {
            $sql .= " AND (p.no_pesanan LIKE :search1 OR p.nama_pemesan LIKE :search2 OR p.no_paket LIKE :search3 OR p.nama_ekspedisi LIKE :search4 OR p.kode LIKE :search5)";
            $params['search1'] = "%{$search}%";
            $params['search2'] = "%{$search}%";
            $params['search3'] = "%{$search}%";
            $params['search4'] = "%{$search}%";
            $params['search5'] = "%{$search}%";
        }

        if ($status) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        }

        if ($userId > 0) {
            $sql .= " AND p.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        if ($tanggal) {
            $sql .= " AND p.tanggal = :tanggal";
            $params['tanggal'] = $tanggal;
        }

        $rows = $this->db->fetchAll($sql, $params);
        $ids = array_map(function($r) {
            return (int)$r['id'];
        }, $rows);
        
        return [
            'ids' => $ids,
            'count' => count($ids)
        ];
    }

    /**
     * Cek data duplikat berdasarkan kombinasi kolom
     */
    public function checkDuplicate(string $noPaket, string $noPesanan, string $kode, string $tanggal, string $sesi): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE is_deleted = 0
                AND no_pesanan = :no_pesanan 
                AND kode = :kode 
                AND tanggal = :tanggal 
                AND sesi = :sesi";
        
        $params = [
            'no_pesanan' => $noPesanan,
            'kode' => $kode,
            'tanggal' => $tanggal,
            'sesi' => $sesi
        ];

        // Jika no_paket tidak kosong, tambahkan ke kriteria pencarian
        if ($noPaket !== '') {
            $sql .= " AND no_paket = :no_paket";
            $params['no_paket'] = $noPaket;
        }
        
        $result = $this->db->fetch($sql, $params);
        return ((int)($result['total'] ?? 0)) > 0;
    }
}

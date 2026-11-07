<?php
/**
 * FotoEkspedisi.php - Model untuk fitur Ekspedisi & Segel Integritas SHA-256
 */

require_once BASE_PATH . '/core/Model.php';

class FotoEkspedisi extends Model
{
    protected string $table = 'foto_ekspedisi';

    /**
     * Ambil semua data foto ekspedisi dengan filter
     */
    public function getAll(array $filters = []): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['ekspedisi'])) {
            $sql .= " AND nama_ekspedisi = :ekspedisi";
            $params['ekspedisi'] = $filters['ekspedisi'];
        }

        if (!empty($filters['tanggal'])) {
            $sql .= " AND DATE(created_at) = :tanggal";
            $params['tanggal'] = $filters['tanggal'];
        }

        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Simpan data foto & segel SHA-256 baru
     */
    public function add(array $data): bool|string
    {
        if (empty($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $data['updated_at'] = date('Y-m-d H:i:s');

        $result = $this->create($data);
        if ($result === false && (isset($data['qty_admin']) || isset($data['qty_staff']))) {
            unset($data['qty_admin'], $data['qty_staff']);
            return $this->create($data);
        }
        return $result;
    }

    /**
     * Ambil riwayat log audit integritas SHA-256 untuk suatu bukti serah terima
     */
    public function getAuditHistory(int $fotoId, int $limit = 5): array
    {
        $sql = "SELECT l.*, u.username AS auditor_name
                FROM integrity_audit_logs l
                LEFT JOIN users u ON l.audited_by = u.id
                WHERE l.target_type = 'FOTO_EKSPEDISI' AND l.target_id = :fid
                ORDER BY l.id DESC
                LIMIT " . (int)$limit;

        return $this->db->fetchAll($sql, ['fid' => $fotoId]);
    }

    /**
     * Soft delete data foto
     */
    public function softDelete(int $id): bool
    {
        return $this->update($id, [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Ambil rincian Target QTY per Ekspedisi berdasarkan Hari / Tanggal.
     * Prioritas Utama: Terhubung langsung ke agregasi manifest file Excel di Hasil Ekspedisi.
     * Fallback: Menggunakan target manual dari tabel ekspedisi_target_qty jika belum ada file Excel.
     *
     * @return array{
     *     total_qty: int,
     *     source: string,
     *     has_manifest: bool,
     *     manifest_date: string,
     *     files: array<int, array<string, mixed>>,
     *     manual_qty: int,
     *     manifest_qty: int
     * }
     */
    public function getTargetQtyInfo(string $ekspedisi, ?string $tanggal = null): array
    {
        $eksp = strtoupper(trim($ekspedisi));
        $tanggal = $tanggal ?: date('Y-m-d');

        $result = [
            'total_qty'     => 0,
            'source'        => 'none',
            'has_manifest'  => false,
            'manifest_date' => $tanggal,
            'files'         => [],
            'manual_qty'    => 0,
            'manifest_qty'  => 0,
        ];

        // 1. Cek nilai manual yang ada di ekspedisi_target_qty
        try {
            $rowManual = $this->db->fetch(
                "SELECT total_qty FROM ekspedisi_target_qty WHERE nama_ekspedisi = :eksp AND tanggal = :tgl LIMIT 1",
                ['eksp' => $eksp, 'tgl' => $tanggal]
            );
            if ($rowManual && isset($rowManual['total_qty'])) {
                $result['manual_qty'] = (int)$rowManual['total_qty'];
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel belum ada
        }

        // 2. Ambil agregasi dari Manifest Hasil Ekspedisi (active files only)
        $columnMap = [
            'SPX'     => 'total_spx',
            'JNE'     => 'total_jne',
            'JNT'     => 'total_jnt',
            'LEX'     => 'total_lex',
            'ID'      => 'total_id',
            'NINJA'   => 'total_ninja',
            'SICEPAT' => 'total_sicepat',
        ];
        $colName = $columnMap[$eksp] ?? null;

        $manifestFiles = [];
        $totalManifestFromHeader = 0;

        try {
            if ($colName !== null) {
                $sqlHeader = "SELECT id, nama_file, ekspedisi, tanggal_upload, manifest_hash, {$colName} AS qty_eksp
                              FROM hasil_ekspedisi
                              WHERE deleted_at IS NULL
                                AND DATE(tanggal_upload) = :tgl
                                AND {$colName} > 0
                              ORDER BY id DESC";
                $manifestFiles = $this->db->fetchAll($sqlHeader, ['tgl' => $tanggal]);
                foreach ($manifestFiles as $mf) {
                    $totalManifestFromHeader += (int)($mf['qty_eksp'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            $manifestFiles = [];
            $totalManifestFromHeader = 0;
        }

        // 3. Verifikasi juga dari hasil_ekspedisi_detail jika ada file NON SPX atau detail resi
        $totalManifestFromDetail = 0;
        try {
            $sqlDetail = "SELECT COUNT(*) as cnt
                          FROM hasil_ekspedisi_detail d
                          JOIN hasil_ekspedisi h ON d.hasil_ekspedisi_id = h.id
                          WHERE h.deleted_at IS NULL
                            AND DATE(h.tanggal_upload) = :tgl
                            AND (
                                (:eksp = 'SPX' AND UPPER(d.ekspedisi) = 'SPX') OR
                                (:eksp = 'JNE' AND UPPER(d.ekspedisi) LIKE '%JNE%') OR
                                (:eksp = 'JNT' AND (UPPER(d.ekspedisi) LIKE '%JNT%' OR UPPER(d.ekspedisi) LIKE '%J&T%')) OR
                                (:eksp = 'LEX' AND (UPPER(d.ekspedisi) LIKE '%LEX%' OR UPPER(d.ekspedisi) LIKE '%LAZADA%')) OR
                                (:eksp = 'ID' AND (UPPER(d.ekspedisi) = 'ID' OR (UPPER(d.ekspedisi) LIKE '%ID%' AND UPPER(d.ekspedisi) LIKE '%EXPRESS%'))) OR
                                (:eksp = 'NINJA' AND UPPER(d.ekspedisi) LIKE '%NINJA%') OR
                                (:eksp = 'SICEPAT' AND UPPER(d.ekspedisi) LIKE '%SICEPAT%')
                            )";
            $rowDetail = $this->db->fetch($sqlDetail, ['tgl' => $tanggal, 'eksp' => $eksp]);
            if ($rowDetail && isset($rowDetail['cnt'])) {
                $totalManifestFromDetail = (int)$rowDetail['cnt'];
            }
        } catch (\Throwable $e) {
            $totalManifestFromDetail = 0;
        }

        $manifestQty = max($totalManifestFromHeader, $totalManifestFromDetail);
        $result['manifest_qty'] = $manifestQty;
        $result['files']        = $manifestFiles;

        if ($manifestQty > 0) {
            $result['total_qty']    = $manifestQty;
            $result['source']       = 'manifest';
            $result['has_manifest'] = true;

            // Sinkronkan ke ekspedisi_target_qty agar selaras di database
            if ($result['manual_qty'] !== $manifestQty) {
                try {
                    $this->setTargetQty($eksp, $manifestQty, $tanggal);
                    $result['manual_qty'] = $manifestQty;
                } catch (\Throwable $e) {
                    // Abaikan kesalahan auto-sync
                }
            }
        } elseif ($result['manual_qty'] > 0) {
            $result['total_qty']    = $result['manual_qty'];
            $result['source']       = 'manual';
            $result['has_manifest'] = false;
        } else {
            // Fallback terakhir: cek qty_admin dari foto terakhir hari ini
            try {
                $rowFoto = $this->db->fetch(
                    "SELECT qty_admin FROM {$this->table}
                     WHERE nama_ekspedisi = :eksp AND DATE(created_at) = :tgl AND deleted_at IS NULL
                     ORDER BY id DESC LIMIT 1",
                    ['eksp' => $eksp, 'tgl' => $tanggal]
                );
                if ($rowFoto && isset($rowFoto['qty_admin']) && (int)$rowFoto['qty_admin'] > 0) {
                    $result['total_qty'] = (int)$rowFoto['qty_admin'];
                    $result['source']    = 'manual';
                }
            } catch (\Throwable $e) {
                // Abaikan
            }
        }

        return $result;
    }

    /**
     * Ambil Target Total QTY per Ekspedisi berdasarkan Hari / Tanggal (khusus acuan Admin & Validasi Staff)
     */
    public function getTargetQty(string $ekspedisi, ?string $tanggal = null): int
    {
        $info = $this->getTargetQtyInfo($ekspedisi, $tanggal);
        return (int)($info['total_qty'] ?? 0);
    }

    /**
     * Sinkronisasi seluruh manifest reguler di Hasil Ekspedisi ke ekspedisi_target_qty
     */
    public function syncManifestToTargetQty(?string $tanggal = null): array
    {
        $tanggal = $tanggal ?: date('Y-m-d');
        $ekspedisis = ['SPX', 'LEX', 'JNE', 'JNT', 'ID', 'NINJA', 'SICEPAT'];
        $synced = [];

        foreach ($ekspedisis as $eksp) {
            $info = $this->getTargetQtyInfo($eksp, $tanggal);
            $qty  = (int)($info['total_qty'] ?? 0);
            if ($qty > 0 && $info['has_manifest']) {
                $this->setTargetQty($eksp, $qty, $tanggal);
                $synced[$eksp] = $qty;
            }
        }

        return $synced;
    }

    /**
     * Simpan / Perbarui Target Total QTY per Ekspedisi berdasarkan Hari / Tanggal (HANYA KHUSUS ADMIN)
     */
    public function setTargetQty(string $ekspedisi, int $qty, ?string $tanggal = null): bool
    {
        $tanggal = $tanggal ?: date('Y-m-d');

        try {
            $this->db->query("CREATE TABLE IF NOT EXISTS ekspedisi_target_qty (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama_ekspedisi VARCHAR(20) NOT NULL,
                tanggal DATE NOT NULL,
                total_qty INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY ekspedisi_tanggal_unique (nama_ekspedisi, tanggal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $sql = "INSERT INTO ekspedisi_target_qty (nama_ekspedisi, tanggal, total_qty) 
                    VALUES (:ekspedisi, :tanggal, :qty) 
                    ON DUPLICATE KEY UPDATE total_qty = :qty_update";
            $this->db->query($sql, [
                'ekspedisi'  => $ekspedisi,
                'tanggal'    => $tanggal,
                'qty'        => $qty,
                'qty_update' => $qty
            ]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}

<?php
/**
 * HasilEkspedisi.php - Model for Hasil Ekspedisi (Terintegrasi SHA-256 Manifest & Resi Hash)
 */

require_once BASE_PATH . '/core/Model.php';

class HasilEkspedisi extends Model
{
    protected string $table = 'hasil_ekspedisi';

    public function getAllWithDetails(int $limit = 10, int $offset = 0, string $search = '', string $ekspedisi = '', string $startDate = '', string $endDate = ''): array
    {
        $sql = "SELECT h.*, u.username as uploaded_by_name 
                FROM {$this->table} h 
                LEFT JOIN users u ON h.uploaded_by = u.id 
                WHERE h.deleted_at IS NULL";
        $params = [];

        if ($search) {
            $sql .= " AND (h.nama_file LIKE :search OR EXISTS (
                        SELECT 1 FROM hasil_ekspedisi_detail hd
                        WHERE hd.hasil_ekspedisi_id = h.id
                          AND (hd.resi LIKE :searchResi OR hd.nama_penerima LIKE :searchNama)
                      ))";
            $params['search']     = "%{$search}%";
            $params['searchResi'] = "%{$search}%";
            $params['searchNama'] = "%{$search}%";
        }

        if ($ekspedisi) {
            $ekspUpper = strtoupper(trim($ekspedisi));
            if ($ekspUpper === 'NON SPX') {
                $sql .= " AND h.ekspedisi <> 'SPX'";
            } elseif ($ekspUpper === 'INSTAN' || $ekspUpper === 'INSTANT' || $ekspUpper === 'SAMEDAY') {
                $sql .= " AND (h.ekspedisi IN ('INSTAN', 'INSTANT', 'SAMEDAY') OR h.total_instan > 0)";
            } else {
                $sql .= " AND h.ekspedisi = :ekspedisi";
                $params['ekspedisi'] = $ekspedisi;
            }
        }

        if ($startDate) {
            $sql .= " AND DATE(h.tanggal_upload) >= :startDate";
            $params['startDate'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(h.tanggal_upload) <= :endDate";
            $params['endDate'] = $endDate;
        }

        $sql .= " ORDER BY h.id DESC";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function countFiltered(string $search = '', string $ekspedisi = '', string $startDate = '', string $endDate = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} h WHERE h.deleted_at IS NULL";
        $params = [];

        if ($search) {
            $sql .= " AND (h.nama_file LIKE :search OR EXISTS (
                        SELECT 1 FROM hasil_ekspedisi_detail hd
                        WHERE hd.hasil_ekspedisi_id = h.id
                          AND (hd.resi LIKE :searchResi OR hd.nama_penerima LIKE :searchNama)
                      ))";
            $params['search']     = "%{$search}%";
            $params['searchResi'] = "%{$search}%";
            $params['searchNama'] = "%{$search}%";
        }

        if ($ekspedisi) {
            $ekspUpper = strtoupper(trim($ekspedisi));
            if ($ekspUpper === 'NON SPX') {
                $sql .= " AND h.ekspedisi <> 'SPX'";
            } elseif ($ekspUpper === 'INSTAN' || $ekspUpper === 'INSTANT' || $ekspUpper === 'SAMEDAY') {
                $sql .= " AND (h.ekspedisi IN ('INSTAN', 'INSTANT', 'SAMEDAY') OR h.total_instan > 0)";
            } else {
                $sql .= " AND h.ekspedisi = :ekspedisi";
                $params['ekspedisi'] = $ekspedisi;
            }
        }

        if ($startDate) {
            $sql .= " AND DATE(h.tanggal_upload) >= :startDate";
            $params['startDate'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(h.tanggal_upload) <= :endDate";
            $params['endDate'] = $endDate;
        }

        $result = $this->db->fetch($sql, $params);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Statistik terpadu yang reaktif terhadap:
     * 1) Manifest resi tersimpan di `hasil_ekspedisi_detail` (mengecualikan resi yang telah
     *    dihapus dari antrean `paket` tanpa merusak segel kriptografi SHA-256 pada manifest), dan
     * 2) Paket mandiri (`+ Tambah Paket`) di tabel `paket` yang belum ada di `hasil_ekspedisi_detail`.
     */
    public function getStats(string $search = '', string $ekspedisi = '', string $startDate = '', string $endDate = ''): array
    {
        $stats = [
            'total_files'         => 0,
            'upload_today'        => 0,
            'total_pakets'        => 0,
            'total_paket_spx'     => 0,
            'total_paket_instan'  => 0,
            'total_paket_jne'     => 0,
            'total_paket_jnt'     => 0,
            'total_paket_lex'     => 0,
            'total_paket_id'      => 0,
            'total_paket_ninja'   => 0,
            'total_paket_sicepat' => 0
        ];

        $whereH  = " WHERE h.deleted_at IS NULL";
        $paramsH = [];
        if ($search) {
            $whereH .= " AND (h.nama_file LIKE :search OR EXISTS (
                            SELECT 1 FROM hasil_ekspedisi_detail hd
                            WHERE hd.hasil_ekspedisi_id = h.id
                              AND (hd.resi LIKE :searchResi OR hd.nama_penerima LIKE :searchNama)
                        ))";
            $paramsH['search']     = "%{$search}%";
            $paramsH['searchResi'] = "%{$search}%";
            $paramsH['searchNama'] = "%{$search}%";
        }
        if ($ekspedisi) {
            $ekspUpper = strtoupper(trim($ekspedisi));
            if ($ekspUpper === 'NON SPX') {
                $whereH .= " AND h.ekspedisi <> 'SPX'";
            } elseif ($ekspUpper === 'INSTAN' || $ekspUpper === 'INSTANT' || $ekspUpper === 'SAMEDAY') {
                $whereH .= " AND (h.ekspedisi IN ('INSTAN', 'INSTANT', 'SAMEDAY') OR h.total_instan > 0)";
            } else {
                $whereH .= " AND h.ekspedisi = :ekspedisi";
                $paramsH['ekspedisi'] = $ekspedisi;
            }
        }
        if ($startDate) {
            $whereH .= " AND DATE(h.tanggal_upload) >= :startDate";
            $paramsH['startDate'] = $startDate;
        }
        if ($endDate) {
            $whereH .= " AND DATE(h.tanggal_upload) <= :endDate";
            $paramsH['endDate'] = $endDate;
        }

        $resFiles = $this->db->fetch("SELECT COUNT(*) as total_files FROM {$this->table} h {$whereH}", $paramsH);
        $stats['total_files'] = (int)($resFiles['total_files'] ?? 0);

        $resToday = $this->db->fetch("SELECT COUNT(*) as upload_today FROM {$this->table} WHERE DATE(tanggal_upload) = CURDATE() AND deleted_at IS NULL");
        $stats['upload_today'] = (int)($resToday['upload_today'] ?? 0);

        // Bagian A: Hitung item dari hasil_ekspedisi_detail (sinkron dengan status hapus & ekspedisi aktif di tabel paket)
        $effEksp = "UPPER(COALESCE(NULLIF(p_act.act_eksp, ''), d.ekspedisi))";
        $whereDetails = $whereH;
        $paramsDetails = $paramsH;
        if ($search) {
            // Jika ada kata kunci pencarian resi/nama spesifik, filter baris detail yang relevan atau nama file cocok
            $whereDetails .= " AND (h.nama_file LIKE :dFileSearch OR d.resi LIKE :dResiSearch OR d.nama_penerima LIKE :dNamaSearch)";
            $paramsDetails['dFileSearch'] = "%{$search}%";
            $paramsDetails['dResiSearch'] = "%{$search}%";
            $paramsDetails['dNamaSearch'] = "%{$search}%";
        }

        $sqlDetails = "SELECT 
                        COUNT(*) as total_all,
                        SUM(CASE WHEN {$effEksp} = 'SPX' THEN 1 ELSE 0 END) as total_spx,
                        SUM(CASE WHEN {$effEksp} IN ('INSTAN', 'INSTANT', 'SAMEDAY') THEN 1 ELSE 0 END) as total_instan,
                        SUM(CASE WHEN {$effEksp} LIKE '%JNE%' THEN 1 ELSE 0 END) as total_jne,
                        SUM(CASE WHEN {$effEksp} LIKE '%JNT%' OR {$effEksp} LIKE '%J&T%' THEN 1 ELSE 0 END) as total_jnt,
                        SUM(CASE WHEN {$effEksp} LIKE '%LEX%' OR {$effEksp} LIKE '%LAZADA%' THEN 1 ELSE 0 END) as total_lex,
                        SUM(CASE WHEN {$effEksp} = 'ID' OR ({$effEksp} LIKE '%ID%' AND {$effEksp} LIKE '%EXPRESS%') THEN 1 ELSE 0 END) as total_id,
                        SUM(CASE WHEN {$effEksp} LIKE '%NINJA%' THEN 1 ELSE 0 END) as total_ninja,
                        SUM(CASE WHEN {$effEksp} LIKE '%SICEPAT%' THEN 1 ELSE 0 END) as total_sicepat
                       FROM hasil_ekspedisi_detail d
                       JOIN hasil_ekspedisi h ON d.hasil_ekspedisi_id = h.id
                       LEFT JOIN (
                           SELECT DISTINCT SUBSTRING(no_pesanan, LOCATE('-', no_pesanan, 7) + 1) AS del_resi
                           FROM paket
                           WHERE is_deleted = 1 AND no_pesanan LIKE '[DEL]-%'
                       ) p_del ON p_del.del_resi = d.resi
                       LEFT JOIN (
                           SELECT no_pesanan AS act_resi, MAX(nama_ekspedisi) AS act_eksp
                           FROM paket
                           WHERE is_deleted = 0
                           GROUP BY no_pesanan
                       ) p_act ON p_act.act_resi = d.resi
                       {$whereDetails}
                         AND (
                              UPPER(d.ekspedisi) NOT IN ('INSTAN', 'INSTANT', 'SAMEDAY', 'GOSEND', 'GRAB')
                              OR p_del.del_resi IS NULL
                              OR p_act.act_resi IS NOT NULL
                          )";

        $resDetails = $this->db->fetch($sqlDetails, $paramsDetails);
        if ($resDetails) {
            $stats['total_pakets']        += (int)($resDetails['total_all'] ?? 0);
            $stats['total_paket_spx']     += (int)($resDetails['total_spx'] ?? 0);
            $stats['total_paket_instan']  += (int)($resDetails['total_instan'] ?? 0);
            $stats['total_paket_jne']     += (int)($resDetails['total_jne'] ?? 0);
            $stats['total_paket_jnt']     += (int)($resDetails['total_jnt'] ?? 0);
            $stats['total_paket_lex']     += (int)($resDetails['total_lex'] ?? 0);
            $stats['total_paket_id']      += (int)($resDetails['total_id'] ?? 0);
            $stats['total_paket_ninja']   += (int)($resDetails['total_ninja'] ?? 0);
            $stats['total_paket_sicepat'] += (int)($resDetails['total_sicepat'] ?? 0);
        }

        // Bagian B: Hitung paket aktif mandiri di tabel `paket` (misal hasil + Tambah Paket) yang belum tercatat di hasil_ekspedisi_detail aktif
        $whereP  = " WHERE p.is_deleted = 0 AND m_act.resi IS NULL";
        $paramsP = [];
        if ($search) {
            $whereP .= " AND (p.no_pesanan LIKE :pSearch1 OR p.nama_pemesan LIKE :pSearch2 OR p.no_paket LIKE :pSearch3)";
            $paramsP['pSearch1'] = "%{$search}%";
            $paramsP['pSearch2'] = "%{$search}%";
            $paramsP['pSearch3'] = "%{$search}%";
        }
        if ($ekspedisi) {
            $ekspUpper = strtoupper(trim($ekspedisi));
            if ($ekspUpper === 'NON SPX') {
                $whereP .= " AND UPPER(p.nama_ekspedisi) <> 'SPX'";
            } elseif ($ekspUpper === 'INSTAN' || $ekspUpper === 'INSTANT' || $ekspUpper === 'SAMEDAY') {
                $whereP .= " AND UPPER(p.nama_ekspedisi) IN ('INSTAN', 'INSTANT', 'SAMEDAY')";
            } else {
                $whereP .= " AND UPPER(p.nama_ekspedisi) = :pEkspedisi";
                $paramsP['pEkspedisi'] = $ekspUpper;
            }
        }
        if ($startDate) {
            $whereP .= " AND p.tanggal >= :pStartDate";
            $paramsP['pStartDate'] = $startDate;
        }
        if ($endDate) {
            $whereP .= " AND p.tanggal <= :pEndDate";
            $paramsP['pEndDate'] = $endDate;
        }

        $pEksp = "UPPER(COALESCE(p.nama_ekspedisi, ''))";
        $sqlStandalone = "SELECT
                            COUNT(*) as total_all,
                            SUM(CASE WHEN {$pEksp} = 'SPX' THEN 1 ELSE 0 END) as total_spx,
                            SUM(CASE WHEN {$pEksp} IN ('INSTAN', 'INSTANT', 'SAMEDAY') THEN 1 ELSE 0 END) as total_instan,
                            SUM(CASE WHEN {$pEksp} LIKE '%JNE%' THEN 1 ELSE 0 END) as total_jne,
                            SUM(CASE WHEN {$pEksp} LIKE '%JNT%' OR {$pEksp} LIKE '%J&T%' THEN 1 ELSE 0 END) as total_jnt,
                            SUM(CASE WHEN {$pEksp} LIKE '%LEX%' OR {$pEksp} LIKE '%LAZADA%' THEN 1 ELSE 0 END) as total_lex,
                            SUM(CASE WHEN {$pEksp} = 'ID' OR ({$pEksp} LIKE '%ID%' AND {$pEksp} LIKE '%EXPRESS%') THEN 1 ELSE 0 END) as total_id,
                            SUM(CASE WHEN {$pEksp} LIKE '%NINJA%' THEN 1 ELSE 0 END) as total_ninja,
                            SUM(CASE WHEN {$pEksp} LIKE '%SICEPAT%' THEN 1 ELSE 0 END) as total_sicepat
                          FROM paket p
                          LEFT JOIN (
                              SELECT DISTINCT d2.resi
                              FROM hasil_ekspedisi_detail d2
                              JOIN hasil_ekspedisi h2 ON d2.hasil_ekspedisi_id = h2.id
                              WHERE h2.deleted_at IS NULL
                          ) m_act ON m_act.resi = p.no_pesanan
                          {$whereP}";

        $resStandalone = $this->db->fetch($sqlStandalone, $paramsP);
        if ($resStandalone) {
            $stats['total_pakets']        += (int)($resStandalone['total_all'] ?? 0);
            $stats['total_paket_spx']     += (int)($resStandalone['total_spx'] ?? 0);
            $stats['total_paket_instan']  += (int)($resStandalone['total_instan'] ?? 0);
            $stats['total_paket_jne']     += (int)($resStandalone['total_jne'] ?? 0);
            $stats['total_paket_jnt']     += (int)($resStandalone['total_jnt'] ?? 0);
            $stats['total_paket_lex']     += (int)($resStandalone['total_lex'] ?? 0);
            $stats['total_paket_id']      += (int)($resStandalone['total_id'] ?? 0);
            $stats['total_paket_ninja']   += (int)($resStandalone['total_ninja'] ?? 0);
            $stats['total_paket_sicepat'] += (int)($resStandalone['total_sicepat'] ?? 0);
        }

        return $stats;
    }

    public function getDetails(int $id, int $limit = 500): array
    {
        return $this->db->fetchAll("SELECT * FROM hasil_ekspedisi_detail WHERE hasil_ekspedisi_id = :id ORDER BY id ASC LIMIT " . (int)$limit, ['id' => $id]);
    }

    public function insertDetail(array $data): bool
    {
        $sql = "INSERT INTO hasil_ekspedisi_detail (hasil_ekspedisi_id, resi, nama_penerima, alamat, ekspedisi, status_scan, resi_hash, tanggal) 
                VALUES (:hasil_ekspedisi_id, :resi, :nama_penerima, :alamat, :ekspedisi, :status_scan, :resi_hash, :tanggal)";
        return $this->db->query($sql, $data) !== false;
    }

    public function isResiExists(string $resi): bool
    {
        $sql = "SELECT COUNT(*) as total
                FROM hasil_ekspedisi_detail d
                JOIN hasil_ekspedisi h ON d.hasil_ekspedisi_id = h.id
                WHERE h.deleted_at IS NULL AND d.resi = :resi";
        $result = $this->db->fetch($sql, ['resi' => $resi]);
        return (int)($result['total'] ?? 0) > 0;
    }

    public function getExistingResis(array $resis): array
    {
        if (empty($resis)) return [];
        
        $existing = [];
        $chunks = array_chunk($resis, 5000);
        
        foreach ($chunks as $chunk) {
            $placeholders = str_repeat('?,', count($chunk) - 1) . '?';
            $sql = "SELECT d.resi
                    FROM hasil_ekspedisi_detail d
                    JOIN hasil_ekspedisi h ON d.hasil_ekspedisi_id = h.id
                    WHERE h.deleted_at IS NULL AND d.resi IN ($placeholders)";
            $results = $this->db->fetchAll($sql, array_values($chunk));
            $existing = array_merge($existing, array_column($results, 'resi'));
        }
        
        return $existing;
    }

    /**
     * Sinkronisasi kategori ekspedisi & segel SHA-256 pada manifest yang belum diserahkan
     * ke kurir (`foto_ekspedisi_id IS NULL`) saat Admin mengimport ulang file Pending dengan kategori baru.
     */
    public function syncPendingResisCategory(array $resis, string $newEkspedisi, IntegritySha256Service $integrityService): int
    {
        if (empty($resis) || trim($newEkspedisi) === '') {
            return 0;
        }

        $expUpper = strtoupper(trim($newEkspedisi));
        $affectedManifestIds = [];
        $updatedRows = 0;

        foreach (array_chunk(array_values(array_unique($resis)), 1000) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $rows = $this->db->fetchAll(
                "SELECT d.id, d.hasil_ekspedisi_id, d.resi, d.ekspedisi, d.tanggal
                 FROM hasil_ekspedisi_detail d
                 JOIN hasil_ekspedisi h ON d.hasil_ekspedisi_id = h.id
                 WHERE h.deleted_at IS NULL
                   AND h.foto_ekspedisi_id IS NULL
                   AND d.resi IN ({$placeholders})",
                $chunk
            );

            foreach ($rows as $row) {
                if (strtoupper((string)$row['ekspedisi']) === $expUpper) {
                    continue;
                }
                $newResiHash = $integrityService->hashResiRecord(
                    (string)$row['resi'],
                    $expUpper,
                    (string)$row['tanggal']
                );
                $this->db->query(
                    "UPDATE hasil_ekspedisi_detail SET ekspedisi = :eksp, resi_hash = :rhash WHERE id = :id",
                    ['eksp' => $expUpper, 'rhash' => $newResiHash, 'id' => (int)$row['id']]
                );
                $affectedManifestIds[(int)$row['hasil_ekspedisi_id']] = true;
                $updatedRows++;
            }
        }

        foreach (array_keys($affectedManifestIds) as $hid) {
            $detailRows = $this->db->fetchAll(
                "SELECT resi, ekspedisi FROM hasil_ekspedisi_detail WHERE hasil_ekspedisi_id = :hid ORDER BY id ASC",
                ['hid' => $hid]
            );
            if (empty($detailRows)) {
                continue;
            }

            $orderedResis = [];
            $spx = $instan = $jne = $jnt = $lex = $idExp = $ninja = $sicepat = $nonSpx = 0;
            $distinctEksp = [];

            foreach ($detailRows as $dr) {
                $orderedResis[] = (string)$dr['resi'];
                $eName = strtoupper(trim((string)$dr['ekspedisi']));
                $distinctEksp[$eName] = true;

                if ($eName === 'SPX') $spx++;
                elseif ($eName === 'INSTAN' || $eName === 'INSTANT' || $eName === 'SAMEDAY') $instan++;
                elseif (strpos($eName, 'JNE') !== false) $jne++;
                elseif (strpos($eName, 'JNT') !== false || strpos($eName, 'J&T') !== false) $jnt++;
                elseif (strpos($eName, 'LEX') !== false || strpos($eName, 'LAZADA') !== false) $lex++;
                elseif ($eName === 'ID' || (strpos($eName, 'ID') !== false && strpos($eName, 'EXPRESS') !== false)) $idExp++;
                elseif (strpos($eName, 'NINJA') !== false) $ninja++;
                elseif (strpos($eName, 'SICEPAT') !== false) $sicepat++;
                else $nonSpx++;
            }

            $parentEksp = (count($distinctEksp) === 1) ? array_key_first($distinctEksp) : 'NON SPX';
            $newManifestHash = $integrityService->computeManifestAggregateHash($orderedResis, $parentEksp);

            $this->updateStats($hid, [
                'ekspedisi'     => $parentEksp,
                'manifest_hash' => $newManifestHash,
                'jumlah_paket'  => count($orderedResis),
                'total_spx'     => $spx,
                'total_instan'  => $instan,
                'total_non_spx' => $nonSpx,
                'total_jne'     => $jne,
                'total_jnt'     => $jnt,
                'total_lex'     => $lex,
                'total_id'      => $idExp,
                'total_ninja'   => $ninja,
                'total_sicepat' => $sicepat,
            ]);
        }

        return $updatedRows;
    }

    public function insertBatchDetails(array $details): int
    {
        if (empty($details)) return 0;

        $values = [];
        $params = [];
        foreach ($details as $detail) {
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?)";
            array_push(
                $params,
                $detail['hasil_ekspedisi_id'],
                $detail['resi'],
                $detail['nama_penerima'],
                $detail['alamat'],
                $detail['ekspedisi'],
                $detail['status_scan'],
                $detail['resi_hash'] ?? null,
                $detail['tanggal']
            );
        }

        $chunks_values = array_chunk($values, 1000);
        $chunks_params = array_chunk($params, 8000); // 1000 * 8 params

        $totalInserted = 0;
        foreach ($chunks_values as $i => $chunkVal) {
            $sql = "INSERT INTO hasil_ekspedisi_detail (hasil_ekspedisi_id, resi, nama_penerima, alamat, ekspedisi, status_scan, resi_hash, tanggal) VALUES " . implode(", ", $chunkVal);
            $this->db->query($sql, $chunks_params[$i]);
            $totalInserted += count($chunkVal);
        }

        return $totalInserted;
    }

    public function updateStats(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return (bool)$this->db->query(
            "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Mengambil daftar file manifest hasil upload untuk pilihan filter pada modal export.
     */
    public function getManifestFileList(?string $ekspedisi = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $sql = "SELECT id, nama_file, ekspedisi, tanggal_upload, jumlah_paket, 
                       total_spx, total_instan, total_jne, total_jnt, total_lex, 
                       total_id, total_ninja, total_sicepat, manifest_hash
                FROM {$this->table}
                WHERE deleted_at IS NULL";
        $params = [];

        if ($ekspedisi && !in_array(strtoupper($ekspedisi), ['ALL', 'SEMUA', ''], true)) {
            $expUpper = strtoupper(trim($ekspedisi));
            if (in_array($expUpper, ['INSTAN', 'INSTANT', 'SAMEDAY'], true)) {
                $sql .= " AND (ekspedisi IN ('INSTAN', 'INSTANT', 'SAMEDAY') OR total_instan > 0)";
            } elseif ($expUpper === 'SPX') {
                $sql .= " AND (ekspedisi = 'SPX' OR total_spx > 0)";
            } elseif ($expUpper === 'JNE') {
                $sql .= " AND (ekspedisi LIKE '%JNE%' OR total_jne > 0)";
            } elseif ($expUpper === 'JNT') {
                $sql .= " AND (ekspedisi LIKE '%JNT%' OR ekspedisi LIKE '%J&T%' OR total_jnt > 0)";
            } elseif ($expUpper === 'LEX') {
                $sql .= " AND (ekspedisi LIKE '%LEX%' OR ekspedisi LIKE '%LAZADA%' OR total_lex > 0)";
            } elseif ($expUpper === 'ID') {
                $sql .= " AND (ekspedisi = 'ID' OR ekspedisi LIKE '%ID%' OR total_id > 0)";
            } elseif ($expUpper === 'NINJA') {
                $sql .= " AND (ekspedisi LIKE '%NINJA%' OR total_ninja > 0)";
            } elseif ($expUpper === 'SICEPAT') {
                $sql .= " AND (ekspedisi LIKE '%SICEPAT%' OR total_sicepat > 0)";
            } else {
                $sql .= " AND ekspedisi = :eksp";
                $params['eksp'] = $ekspedisi;
            }
        }

        if ($startDate) {
            $sql .= " AND DATE(tanggal_upload) >= :startDate";
            $params['startDate'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(tanggal_upload) <= :endDate";
            $params['endDate'] = $endDate;
        }

        $sql .= " ORDER BY id DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Mengambil kumpulan data terformat untuk keperluan Export Excel per ekspedisi / per manifest file.
     */
    public function getExportData(string $ekspedisi, string $startDate = '', string $endDate = '', ?int $fileId = null): array
    {
        $expUpper = strtoupper(trim($ekspedisi));
        if ($expUpper === '' || $expUpper === 'SEMUA') {
            $expUpper = 'ALL';
        }

        // KASUS 1: EKSPEDISI INSTAN / SAMEDAY
        // Mengambil antrean prioritas dari tabel `paket`
        if (in_array($expUpper, ['INSTAN', 'INSTANT', 'SAMEDAY'], true)) {
            $where = "p.is_deleted = 0 AND UPPER(p.nama_ekspedisi) IN ('INSTAN', 'INSTANT', 'SAMEDAY')";
            $params = [];

            if ($startDate !== '') {
                $where .= " AND p.tanggal >= :startDate";
                $params['startDate'] = $startDate;
            }
            if ($endDate !== '') {
                $where .= " AND p.tanggal <= :endDate";
                $params['endDate'] = $endDate;
            }
            if ($fileId && $fileId > 0) {
                // Jika user memilih file manifest tertentu, filter paket yang nomor pesanannya ada di manifest tersebut
                $where .= " AND EXISTS (
                    SELECT 1 FROM hasil_ekspedisi_detail d 
                    WHERE d.hasil_ekspedisi_id = :fileId AND d.resi = p.no_pesanan
                )";
                $params['fileId'] = $fileId;
            }

            $sql = "SELECT p.id, p.no_paket, p.no_pesanan, p.nama_pemesan, p.nama_ekspedisi, p.layanan_ekspedisi,
                           p.cutoff_time, p.priority_score, p.score_breakdown, p.tanggal, p.tanggal_masuk, p.sesi,
                           p.status, u.username as petugas_input
                    FROM paket p
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE {$where}
                    ORDER BY p.priority_score DESC, p.id ASC";

            $rows = $this->db->fetchAll($sql, $params);

            // Jika dari antrean paket kosong tapi di file manifest ada data instan, ambil juga dari manifest detail
            if (empty($rows) && $fileId && $fileId > 0) {
                $sqlDetail = "SELECT d.id, d.resi as no_pesanan, d.nama_penerima as nama_pemesan, 
                                     d.ekspedisi as nama_ekspedisi, 'INSTANT' as layanan_ekspedisi,
                                     '20:00:00' as cutoff_time, 50.00 as priority_score, '' as score_breakdown,
                                     DATE(d.tanggal) as tanggal, d.tanggal as tanggal_masuk, 'SESI 1' as sesi,
                                     d.status_scan as status, 'Sistem Import' as petugas_input,
                                     d.resi_hash
                              FROM hasil_ekspedisi_detail d
                              WHERE d.hasil_ekspedisi_id = :fileId
                                AND UPPER(d.ekspedisi) IN ('INSTAN', 'INSTANT', 'SAMEDAY')
                              ORDER BY d.id ASC";
                $rows = $this->db->fetchAll($sqlDetail, ['fileId' => $fileId]);
            }

            return [
                'type'      => 'INSTAN',
                'ekspedisi' => 'INSTAN',
                'title'     => 'LAPORAN ANTREAN PENGIRIMAN PAKET INSTAN & SAMEDAY (PRIORITY SCHEDULING)',
                'count'     => count($rows),
                'rows'      => $rows
            ];
        }

        // KASUS 2: REGULER ATAU ALL (SPX, JNE, JNT, LEX, ID, NINJA, SICEPAT, ALL)
        $where = "h.deleted_at IS NULL";
        $params = [];

        if ($fileId && $fileId > 0) {
            $where .= " AND h.id = :fileId";
            $params['fileId'] = $fileId;
        }

        if ($startDate !== '') {
            $where .= " AND (DATE(d.tanggal) >= :startDate OR DATE(h.tanggal_upload) >= :startDateH)";
            $params['startDate']  = $startDate;
            $params['startDateH'] = $startDate;
        }
        if ($endDate !== '') {
            $where .= " AND (DATE(d.tanggal) <= :endDate OR DATE(h.tanggal_upload) <= :endDateH)";
            $params['endDate']  = $endDate;
            $params['endDateH'] = $endDate;
        }

        // Filter berdasarkan ekspedisi spesifik
        if ($expUpper !== 'ALL') {
            if ($expUpper === 'SPX') {
                $where .= " AND (UPPER(d.ekspedisi) = 'SPX' OR UPPER(d.ekspedisi) LIKE '%SHOPEE%')";
            } elseif ($expUpper === 'JNE') {
                $where .= " AND UPPER(d.ekspedisi) LIKE '%JNE%'";
            } elseif ($expUpper === 'JNT') {
                $where .= " AND (UPPER(d.ekspedisi) LIKE '%JNT%' OR UPPER(d.ekspedisi) LIKE '%J&T%')";
            } elseif ($expUpper === 'LEX') {
                $where .= " AND (UPPER(d.ekspedisi) LIKE '%LEX%' OR UPPER(d.ekspedisi) LIKE '%LAZADA%')";
            } elseif ($expUpper === 'ID') {
                $where .= " AND (UPPER(d.ekspedisi) = 'ID' OR UPPER(d.ekspedisi) LIKE '%ID EXPRESS%')";
            } elseif ($expUpper === 'NINJA') {
                $where .= " AND UPPER(d.ekspedisi) LIKE '%NINJA%'";
            } elseif ($expUpper === 'SICEPAT') {
                $where .= " AND UPPER(d.ekspedisi) LIKE '%SICEPAT%'";
            } else {
                $where .= " AND UPPER(d.ekspedisi) = :filterEksp";
                $params['filterEksp'] = $expUpper;
            }
        }

        $sql = "SELECT d.id, d.resi, d.nama_penerima, d.alamat, d.ekspedisi, d.status_scan, 
                       d.tanggal, d.resi_hash, h.id as manifest_id, h.nama_file as manifest_file, 
                       h.tanggal_upload, h.manifest_hash
                FROM hasil_ekspedisi_detail d
                JOIN hasil_ekspedisi h ON d.hasil_ekspedisi_id = h.id
                WHERE {$where}
                ORDER BY d.id ASC";

        $rows = $this->db->fetchAll($sql, $params);

        $ekspTitle = $expUpper === 'ALL' ? 'SEMUA EKSPEDISI' : $expUpper;

        return [
            'type'      => 'REGULER',
            'ekspedisi' => $expUpper,
            'title'     => 'LAPORAN HASIL MANIFEST RESI PENGIRIMAN - ' . $ekspTitle,
            'count'     => count($rows),
            'rows'      => $rows
        ];
    }
}

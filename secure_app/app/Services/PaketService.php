<?php
/**
 * PaketService.php - Service Layer for Package logic (Terintegrasi Priority Scheduling)
 */

require_once BASE_PATH . '/app/Models/Paket.php';
require_once BASE_PATH . '/app/Services/PrioritySchedulingService.php';

class PaketService
{
    private Paket $paketModel;
    private PrioritySchedulingService $priorityService;

    public function __construct()
    {
        $this->paketModel = new Paket();
        $this->priorityService = new PrioritySchedulingService();
    }

    /**
     * Cek apakah ekspedisi / layanan tergolong paket INSTAN atau SAMEDAY (1 paket 1 kurir perorangan).
     * Paket reguler (SPX, LEX, JNE, JNT, ID, Ninja, SiCepat, Cargo) adalah serah terima massal kurir
     * dan tidak dimasukkan ke dalam daftar antrean perorangan Instan.
     */
    public static function isInstantOrSameday(?string $ekspedisi, ?string $layanan = null, ?string $sesi = null): bool
    {
        $e = strtoupper(trim((string)$ekspedisi));
        $l = strtoupper(trim((string)$layanan));
        $s = strtoupper(trim((string)$sesi));

        if (in_array($e, ['INSTAN', 'INSTANT', 'SAMEDAY', 'GOSEND', 'GRAB', 'LALAMOVE'], true) ||
            str_contains($e, 'INSTAN') || str_contains($e, 'SAMEDAY') || str_contains($e, 'GOSEND') || str_contains($e, 'GRAB') || str_contains($e, 'LALAMOVE')) {
            return true;
        }
        if (in_array($l, ['INSTAN', 'INSTANT', 'SAMEDAY'], true) || str_contains($l, 'INSTAN') || str_contains($l, 'SAMEDAY')) {
            return true;
        }
        if ($s === 'INSTAN' || $s === 'SAMEDAY' || str_contains($s, 'INSTAN') || str_contains($s, 'SAMEDAY')) {
            return true;
        }
        return false;
    }

    public function getPriorityService(): PrioritySchedulingService
    {
        return $this->priorityService;
    }

    public function getMasterEkspedisi(): array
    {
        return $this->priorityService->getMasterProfiles();
    }

    public function recalculateQueue(string $tanggal, bool $onlyPending = false): array
    {
        return $this->priorityService->recalculateDailyQueue($tanggal, $onlyPending);
    }

    public function getAllPackages($params, $userId = 0)
    {
        $search   = $params['search'] ?? '';
        $status   = $params['status'] ?? '';
        $tanggal  = $params['tanggal'] ?? date('Y-m-d');
        $sortMode = (($params['sort'] ?? 'priority') === 'fifo') ? 'fifo' : 'priority';
        $page     = max(1, (int)($params['page'] ?? 1));
        $limit    = max(1, (int)($params['limit'] ?? 10));
        $offset   = ($page - 1) * $limit;

        // Sinkronisasi ringan komponen dinamis (Cut-Off & Aging) untuk antrean Pending
        if ($tanggal !== '') {
            $this->priorityService->refreshIfStale($tanggal, 300);
        }

        $items = $this->paketModel->getAllWithUser($limit, $offset, $search, $status, $userId, $tanggal, $sortMode);
        $total = $this->paketModel->countFiltered($search, $status, $userId, $tanggal);

        return [
            'items'      => $items,
            'total'      => $total,
            'totalPages' => ceil($total / $limit),
            'page'       => $page,
            'sort'       => $sortMode,
        ];
    }

    public function createPackage($data, $creatorId, bool $skipVolumeSync = false)
    {
        $tanggal      = $data['tanggal'] ?? date('Y-m-d');
        $tanggalMasuk = $data['tanggal_masuk'] ?? date('Y-m-d H:i:s');
        $jumlahItem   = max(1, (int)($data['jumlah_item'] ?? 1));

        // Get the next no_paket counter if not provided
        $noPaket = isset($data['no_paket']) && $data['no_paket'] !== '' ? $data['no_paket'] : null;
        if ($noPaket === null) {
            $noPaket = $this->paketModel->getMaxNoPaket($tanggal) + 1;
        }

        // Hitung Priority Score awal
        $resolved = $this->priorityService->resolveParameters(
            $data['nama_ekspedisi'] ?? null,
            $data['layanan_ekspedisi'] ?? null,
            $data['cutoff_time'] ?? null,
            $data['no_pesanan'] ?? '',
            $data['kode'] ?? '',
            $data['sesi'] ?? ''
        );

        $batchVol = $jumlahItem;
        if (!$skipVolumeSync) {
            $volMap   = $this->priorityService->getDailyVolumeMap($tanggal);
            $batchVol = ($volMap[$resolved['nama_ekspedisi']] ?? 0) + $jumlahItem;
        }

        $calc = $this->priorityService->calculatePriority([
            'tanggal'           => $tanggal,
            'tanggal_masuk'     => $tanggalMasuk,
            'nama_ekspedisi'    => $resolved['nama_ekspedisi'],
            'layanan_ekspedisi' => $resolved['layanan_ekspedisi'],
            'cutoff_time'       => $resolved['cutoff_time'],
            'batch_volume'      => $batchVol,
        ]);

        return $this->paketModel->create([
            'no_paket'          => $noPaket,
            'no_pesanan'        => $data['no_pesanan'],
            'nama_pemesan'      => $data['nama_pemesan'],
            'kode'              => $data['kode'] ?? '',
            'tanggal'           => $tanggal,
            'sesi'              => $data['sesi'] ?? 'Umum',
            'nama_ekspedisi'    => $calc['nama_ekspedisi'],
            'layanan_ekspedisi' => $calc['layanan_ekspedisi'],
            'jumlah_item'       => $jumlahItem,
            'jam'               => $data['jam'] ?? null,
            'cutoff_time'       => $calc['cutoff_time'],
            'priority_score'    => $calc['priority_score'],
            'score_breakdown'   => $calc['score_breakdown_json'],
            'scheduled_at'      => date('Y-m-d H:i:s'),
            'status'            => 'Pending',
            'user_id'           => $creatorId,
            'tanggal_masuk'     => $tanggalMasuk,
        ]);
    }

    public function updatePackage($id, $data, bool $skipVolumeSync = false)
    {
        $existing = $this->paketModel->find((int)$id);
        if (!$existing) {
            return false;
        }

        $tanggal     = $data['tanggal'] ?? $existing['tanggal'];
        $jumlahItem  = isset($data['jumlah_item']) ? max(1, (int)$data['jumlah_item']) : max(1, (int)($existing['jumlah_item'] ?? 1));
        $namaEksp    = $data['nama_ekspedisi'] ?? ($existing['nama_ekspedisi'] ?? null);
        $layananEksp = $data['layanan_ekspedisi'] ?? ($existing['layanan_ekspedisi'] ?? null);
        $cutoffInput = array_key_exists('cutoff_time', $data) ? $data['cutoff_time'] : ($existing['cutoff_time'] ?? null);

        $resolved = $this->priorityService->resolveParameters(
            $namaEksp,
            $layananEksp,
            $cutoffInput,
            $data['no_pesanan'] ?? $existing['no_pesanan'],
            $data['kode'] ?? ($existing['kode'] ?? ''),
            $data['sesi'] ?? ($existing['sesi'] ?? '')
        );

        $batchVol = $jumlahItem;
        if (!$skipVolumeSync) {
            $volMap   = $this->priorityService->getDailyVolumeMap((string)$tanggal);
            $batchVol = max($jumlahItem, $volMap[$resolved['nama_ekspedisi']] ?? $jumlahItem);
        }

        $calc = $this->priorityService->calculatePriority([
            'tanggal'           => $tanggal,
            'tanggal_masuk'     => $existing['tanggal_masuk'] ?? date('Y-m-d H:i:s'),
            'nama_ekspedisi'    => $resolved['nama_ekspedisi'],
            'layanan_ekspedisi' => $resolved['layanan_ekspedisi'],
            'cutoff_time'       => $resolved['cutoff_time'],
            'batch_volume'      => $batchVol,
        ]);

        return $this->paketModel->update($id, [
            'no_paket'          => $data['no_paket'] ?? $existing['no_paket'],
            'no_pesanan'        => $data['no_pesanan'] ?? $existing['no_pesanan'],
            'nama_pemesan'      => $data['nama_pemesan'] ?? $existing['nama_pemesan'],
            'kode'              => $data['kode'] ?? ($existing['kode'] ?? ''),
            'tanggal'           => $tanggal,
            'sesi'              => $data['sesi'] ?? $existing['sesi'],
            'jam'               => (array_key_exists('jam', $data) && $data['jam'] !== null && $data['jam'] !== '')
                                    ? $data['jam']
                                    : ($existing['jam'] ?? null),
            'nama_ekspedisi'    => $calc['nama_ekspedisi'],
            'layanan_ekspedisi' => $calc['layanan_ekspedisi'],
            'jumlah_item'       => $jumlahItem,
            'cutoff_time'       => $calc['cutoff_time'],
            'priority_score'    => $calc['priority_score'],
            'score_breakdown'   => $calc['score_breakdown_json'],
            'scheduled_at'      => date('Y-m-d H:i:s'),
            'status'            => $data['status'] ?? ($existing['status'] ?? 'Pending'),
        ]);
    }

    public function updateStatus($id, $status, $userId)
    {
        return $this->paketModel->update($id, [
            'status'  => $status,
            'user_id' => $userId
        ]);
    }

    public function deletePackage($id)
    {
        return $this->paketModel->delete($id);
    }

    public function softDeletePackage($id)
    {
        return $this->paketModel->softDelete($id);
    }

    public function getPackageById($id)
    {
        return $this->paketModel->find($id);
    }

    public function getStats($userId = 0, $tanggal = '')
    {
        $distribution = $this->paketModel->getStatusDistribution($userId, $tanggal);
        
        $stats = [
            'total'   => 0,
            'pending' => 0,
            'sudah'   => 0,
            'batal'   => 0,
            'retur'   => 0,
        ];

        foreach ($distribution as $row) {
            $status = strtolower($row['status']);
            if (array_key_exists($status, $stats)) {
                $stats[$status] = (int)$row['count'];
            }
            $stats['total'] += (int)$row['count'];
        }

        return $stats;
    }

    public function getRecentPackages($userId = 0, $limit = 5, $tanggal = '')
    {
        if ($tanggal !== '') {
            $this->priorityService->refreshIfStale($tanggal, 300);
        }
        return $this->paketModel->getAllWithUser($limit, 0, '', '', $userId, $tanggal, 'priority');
    }

    public function getChartData($userId = 0)
    {
        return [
            'trend'        => $this->paketModel->getDailyTrend($userId),
            'distribution' => $this->paketModel->getStatusDistribution($userId)
        ];
    }

    public function importFromExcel($filePath, $userId, array $importOptions = [])
    {
        // =============================================
        // Step 1: Read file using PHP native XlsxReader
        // =============================================
        try {
            $rows = XlsxReader::read($filePath);
        } catch (RuntimeException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        if (empty($rows)) {
            return [
                'success' => false,
                'message' => 'File kosong atau tidak memiliki data.'
            ];
        }

        // =============================================
        // Step 2: Parse rows & resolve batch Ekspedisi/SLA override
        // =============================================
        $defaultTanggal = trim((string)($importOptions['default_tanggal'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultTanggal)) {
            $defaultTanggal = date('Y-m-d');
        }

        $items = $this->parseExcelRows($rows, $defaultTanggal);

        if (empty($items)) {
            return [
                'success' => false,
                'message' => 'Tidak ditemukan data valid di file. Pastikan file memiliki kolom: No Pesanan, Nama Pemesan.'
            ];
        }

        $rawMode = strtolower(trim((string)($importOptions['import_mode'] ?? '')));
        $rawEksp = strtoupper(trim((string)($importOptions['nama_ekspedisi'] ?? '')));
        $isManualOverride = ($rawEksp !== '' && $rawEksp !== 'AUTO' && $rawMode !== 'auto');

        $overrideResolved = null;
        if ($isManualOverride) {
            $overrideResolved = $this->priorityService->resolveParameters(
                $rawEksp,
                $importOptions['layanan_ekspedisi'] ?? null,
                $importOptions['cutoff_time'] ?? null
            );
        }

        // =============================================
        // Step 3: Insert/update items into database
        // =============================================
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $tanggalDates = [];
        $manifestReadyItems = [];
        
        // Track max no_paket per date to increment locally
        $maxNoPerDate = [];
        
        foreach ($items as $item) {
            $item['no_pesanan'] = trim($item['no_pesanan']);

            // Terapkan parameter Ekspedisi, SLA, & Cut-Off batch pilihan pengguna secara menyeluruh
            if ($overrideResolved !== null) {
                $item['nama_ekspedisi']    = $overrideResolved['nama_ekspedisi'];
                $item['layanan_ekspedisi'] = $overrideResolved['layanan_ekspedisi'];
                $item['cutoff_time']       = $overrideResolved['cutoff_time'];
            }
            
            $noPaket = trim((string)($item['no_paket'] ?? ''));
            $kode    = trim((string)($item['kode'] ?? ''));
            $tanggal = trim((string)($item['tanggal'] ?? ''));
            $sesi    = trim((string)($item['sesi'] ?? ''));

            $resolvedRowMeta = $this->priorityService->resolveParameters(
                $item['nama_ekspedisi'] ?? null,
                $item['layanan_ekspedisi'] ?? null,
                $item['cutoff_time'] ?? null,
                $item['no_pesanan'],
                $kode,
                $sesi
            );

            $alamatInfo = trim($kode . ($sesi !== '' ? ($kode !== '' ? ' • ' : '') . $sesi : ''));
            $isInstantItem = self::isInstantOrSameday(
                $resolvedRowMeta['nama_ekspedisi'] ?? null,
                $resolvedRowMeta['layanan_ekspedisi'] ?? null,
                $sesi
            );

            $manifestReadyItems[] = [
                'resi'          => $item['no_pesanan'],
                'nama_penerima' => $item['nama_pemesan'] ?? '',
                'alamat'        => $alamatInfo,
                'ekspedisi'     => $resolvedRowMeta['nama_ekspedisi'],
                'status_scan'   => 'Terjadwal',
                'tanggal'       => ($tanggal !== '' ? $tanggal : $defaultTanggal) . ' ' . date('H:i:s'),
                'sheet_name'    => $resolvedRowMeta['nama_ekspedisi'],
                'is_instant'    => $isInstantItem,
            ];

            // Antrean tabel `paket` HANYA dikhususkan untuk paket INSTAN / SAMEDAY (1 paket 1 kurir).
            // Paket reguler massal (SPX, LEX, JNE, JNT, ID, Ninja, SiCepat) hanya dicatat di Laporan Hasil Ekspedisi ($manifestReadyItems).
            if (!$isInstantItem) {
                // Jika data lama sempat ada di antrean `paket` dan berstatus Pending,
                // lalu di-reimport menjadi reguler, keluarkan/soft-delete dari antrean `paket`
                // agar antrean Instan tidak tercemar paket reguler!
                $existing = $this->paketModel->getByNoPesanan($item['no_pesanan']);
                if ($existing && ($existing['status'] ?? '') === 'Pending') {
                    $this->paketModel->softDelete((int)$existing['id']);
                    $updated++;
                }
                continue;
            }

            // Check if no_pesanan already exists in active records
            $existing = $this->paketModel->getByNoPesanan($item['no_pesanan']);
            if ($existing) {
                // PERLINDUNGAN: Jika data sudah pernah dieksekusi (bukan Pending), JANGAN sentuh sama sekali
                if (!empty($existing['status']) && $existing['status'] !== 'Pending') {
                    $skipped++;
                    continue;
                }

                $targetResolved = $resolvedRowMeta;

                $isDuplicateCore = $this->paketModel->checkDuplicate($noPaket, $item['no_pesanan'], $kode, $tanggal, $sesi);
                $samePriorityMeta = (
                    strtoupper((string)($existing['nama_ekspedisi'] ?? '')) === $targetResolved['nama_ekspedisi'] &&
                    strtoupper((string)($existing['layanan_ekspedisi'] ?? '')) === $targetResolved['layanan_ekspedisi'] &&
                    substr((string)($existing['cutoff_time'] ?? ''), 0, 5) === substr((string)$targetResolved['cutoff_time'], 0, 5)
                );

                // Jika 5 kolom inti DAN Ekspedisi/SLA/Cut-Off sudah 100% sama persis -> lewati sebagai duplikat
                if ($isDuplicateCore && $samePriorityMeta) {
                    $skipped++;
                    continue;
                }

                $updateData = [
                    'no_pesanan'        => $item['no_pesanan'],
                    'nama_pemesan'      => $item['nama_pemesan'],
                    'kode'              => $item['kode'] ?? '',
                    'tanggal'           => $item['tanggal'],
                    'sesi'              => $item['sesi'],
                    'jam'               => $item['jam'] ?? ($existing['jam'] ?? null),
                    'nama_ekspedisi'    => $targetResolved['nama_ekspedisi'],
                    'layanan_ekspedisi' => $targetResolved['layanan_ekspedisi'],
                    'cutoff_time'       => $targetResolved['cutoff_time'],
                    'jumlah_item'       => $item['jumlah_item'] ?? ($existing['jumlah_item'] ?? 1),
                    'no_paket'          => (!empty($item['no_paket']) && $item['no_paket'] !== '0')
                                            ? $item['no_paket']
                                            : $existing['no_paket'],
                    'status'            => $existing['status'],
                ];
                
                if ($this->updatePackage($existing['id'], $updateData, true)) {
                    $updated++;
                    foreach ([$existing['tanggal'] ?? null, $item['tanggal'] ?? null] as $tglTrack) {
                        if ($tglTrack && !in_array($tglTrack, $tanggalDates, true)) {
                            $tanggalDates[] = $tglTrack;
                        }
                    }
                } else {
                    $skipped++;
                }
                continue;
            }
            
            // Ensure unique no_paket if not provided from excel
            if ($item['no_paket'] === null || $item['no_paket'] === '') {
                $itemDate = $item['tanggal'] ?? date('Y-m-d');
                if (!isset($maxNoPerDate[$itemDate])) {
                    $maxNoPerDate[$itemDate] = (int)$this->paketModel->getMaxNoPaket($itemDate);
                }
                $maxNoPerDate[$itemDate]++;
                $item['no_paket'] = $maxNoPerDate[$itemDate];
            } else {
                $itemDate = $item['tanggal'] ?? date('Y-m-d');
                if (!isset($maxNoPerDate[$itemDate]) || (int)$item['no_paket'] > $maxNoPerDate[$itemDate]) {
                    $maxNoPerDate[$itemDate] = (int)$item['no_paket'];
                }
            }
            
            // Skip per-row volume query during batch insert; synced accurately right after loop
            if ($this->createPackage($item, $userId, true)) {
                $inserted++;
                $tgl = $item['tanggal'] ?? null;
                if ($tgl && !in_array($tgl, $tanggalDates, true)) {
                    $tanggalDates[] = $tgl;
                }
            } else {
                $skipped++;
            }
        }

        // Step 4: Hitung ulang Priority Score secara kolektif agar Volume Batch (V_i) presisi
        foreach ($tanggalDates as $tglSync) {
            $this->priorityService->recalculateDailyQueue($tglSync, false);
        }
        
        // Format tanggal dates
        $tanggalStr = empty($tanggalDates) ? date('d-m-Y', strtotime($defaultTanggal)) : implode(', ', array_map(function($d) {
            return date('d-m-Y', strtotime($d));
        }, $tanggalDates));

        $appliedProfile = $overrideResolved !== null ? [
            'mode'              => 'manual',
            'nama_ekspedisi'    => $overrideResolved['nama_ekspedisi'],
            'layanan_ekspedisi' => $overrideResolved['layanan_ekspedisi'],
            'cutoff_time'       => substr((string)$overrideResolved['cutoff_time'], 0, 5),
        ] : [
            'mode' => 'auto',
        ];

        $partsMsg = [];
        if ($inserted > 0) $partsMsg[] = "{$inserted} data baru masuk";
        if ($updated > 0)  $partsMsg[] = "{$updated} data Pending diperbarui";
        $summaryStr = !empty($partsMsg) ? implode(', ', $partsMsg) : "0 data diproses";
        
        return [
            'success'              => true,
            'message'              => "Import berhasil: {$summaryStr} & dijadwalkan berdasarkan Priority Score ({$skipped} dilewati).",
            'inserted'             => $inserted,
            'updated'              => $updated,
            'skipped'              => $skipped,
            'tanggal'              => $tanggalStr,
            'raw_dates'            => $tanggalDates,
            'applied_profile'      => $appliedProfile,
            'manifest_ready_items' => $manifestReadyItems,
        ];
    }

    /**
     * Parse raw spreadsheet rows into structured item arrays.
     * Mendukung format 5 kolom lama maupun kolom tambahan Priority Scheduling
     * (ekspedisi, layanan, cutoff, jumlah_item).
     */
    private function parseExcelRows(array $rows, ?string $defaultTanggal = null): array
    {
        $currentMeta = [
            'tanggal' => ($defaultTanggal && preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultTanggal))
                            ? $defaultTanggal
                            : date('Y-m-d'),
            'sesi'    => 'SESI 1',
            'jam'     => null
        ];

        $mapping = [];
        $headerRowFound = false;
        $results = [];

        foreach ($rows as $row) {
            $rowValues = array_map(function ($val) {
                return trim((string)$val);
            }, $row);

            $joinedRow = strtolower(implode(' ', $rowValues));

            // ── 1. Update metadata if this row contains date/sesi/jam ──
            $newMeta = $this->parseRowMetadata(implode(' ', $rowValues));
            if ($newMeta['tanggal']) $currentMeta['tanggal'] = $newMeta['tanggal'];
            if ($newMeta['sesi'])    $currentMeta['sesi']    = $newMeta['sesi'];
            if ($newMeta['jam'])     $currentMeta['jam']     = $newMeta['jam'];

            // ── 2. Check if this is a header row ──
            $targetKeywords = ['pesanan', 'resi', 'waybill', 'awb', 'nama', 'penerima', 'kode', 'alamat', 'ekspedisi', 'kurir'];
            $hasOrderKeyword = (
                strpos($joinedRow, 'pesanan') !== false ||
                strpos($joinedRow, 'resi') !== false ||
                strpos($joinedRow, 'waybill') !== false ||
                strpos($joinedRow, 'awb') !== false
            );
            $matches = 0;
            foreach ($targetKeywords as $k) {
                if (strpos($joinedRow, $k) !== false) {
                    $matches++;
                }
            }

            if ($matches >= 2 && $hasOrderKeyword) {
                $mapping = [];
                foreach ($rowValues as $i => $val) {
                    $vLow = strtolower(trim($val));

                    if (
                        strpos($vLow, 'pesanan') !== false ||
                        strpos($vLow, 'resi') !== false ||
                        strpos($vLow, 'waybill') !== false ||
                        $vLow === 'awb'
                    ) {
                        $mapping[$i] = 'no_pesanan';
                    } elseif ((strpos($vLow, 'nama') !== false || strpos($vLow, 'penerima') !== false) && strpos($vLow, 'ekspedisi') === false) {
                        $mapping[$i] = 'nama_pemesan';
                    } elseif (strpos($vLow, 'layanan') !== false || strpos($vLow, 'service') !== false || $vLow === 'sla') {
                        $mapping[$i] = 'layanan_ekspedisi';
                    } elseif (strpos($vLow, 'ekspedisi') !== false || strpos($vLow, 'kurir') !== false) {
                        $mapping[$i] = 'nama_ekspedisi';
                    } elseif (strpos($vLow, 'cutoff') !== false || strpos($vLow, 'cut-off') !== false || strpos($vLow, 'batas') !== false) {
                        $mapping[$i] = 'cutoff_time';
                    } elseif ($vLow === 'qty' || strpos($vLow, 'jumlah') !== false || strpos($vLow, 'item') !== false) {
                        $mapping[$i] = 'jumlah_item';
                    } elseif (
                        strpos($vLow, 'kode paket') !== false ||
                        strpos($vLow, 'no paket') !== false ||
                        strpos($vLow, 'no.paket') !== false ||
                        strpos($vLow, 'nomor paket') !== false ||
                        strpos($vLow, 'nomor') !== false ||
                        strpos($vLow, 'number') !== false ||
                        $vLow === 'no' || $vLow === 'no.'
                    ) {
                        $mapping[$i] = 'no_paket';
                    } elseif ($vLow === 'kode' || strpos($vLow, 'alamat') !== false) {
                        $mapping[$i] = 'kode';
                    } elseif ($vLow === 'sesi') {
                        $mapping[$i] = 'sesi';
                    } elseif (strpos($vLow, 'tanggal') !== false || $vLow === 'waktu' || $vLow === 'date') {
                        $mapping[$i] = 'tanggal';
                    }
                }
                if ($this->findMappingIndex($mapping, 'no_pesanan') !== -1) {
                    $headerRowFound = true;
                    continue;
                }
            }

            // ── 3. Process data rows (only after header is found) ──
            if ($headerRowFound && !empty($mapping)) {
                $noPesananIdx   = $this->findMappingIndex($mapping, 'no_pesanan');
                $namaPemesanIdx = $this->findMappingIndex($mapping, 'nama_pemesan');

                $noPesanan   = ($noPesananIdx !== -1 && isset($rowValues[$noPesananIdx]))
                    ? $rowValues[$noPesananIdx] : '';
                $namaPemesan = ($namaPemesanIdx !== -1 && isset($rowValues[$namaPemesanIdx]))
                    ? $rowValues[$namaPemesanIdx] : '';

                $skipValues = ['nan', '', 'none', 'no pesanan', 'pesanan', 'resi', 'no resi', 'no. resi', 'waybill', 'awb', 'sesi'];
                if (empty($noPesanan) || in_array(strtolower($noPesanan), $skipValues, true)) {
                    continue;
                }

                $dayNames = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
                $containsDay = false;
                foreach ($dayNames as $day) {
                    if (strpos(strtolower($noPesanan), $day) !== false) {
                        $containsDay = true;
                        break;
                    }
                }
                if ($containsDay) continue;

                if (in_array(strtolower($namaPemesan), ['nama pemesan', 'nama', 'nama penerima', 'penerima'], true)) continue;
                if ($namaPemesan === '' || in_array(strtolower($namaPemesan), ['nan', 'none'], true)) {
                    $namaPemesan = '-';
                }

                $kodeIdx = $this->findMappingIndex($mapping, 'kode');
                $kode = ($kodeIdx !== -1 && isset($rowValues[$kodeIdx])) ? $rowValues[$kodeIdx] : '';
                if (in_array(strtolower($kode), ['nan', 'none', '-'], true)) $kode = '';
                if (mb_strlen($kode) > 50) {
                    $kode = mb_substr($kode, 0, 50);
                }

                $noPaketIdx = $this->findMappingIndex($mapping, 'no_paket');
                $noPaket = ($noPaketIdx !== -1 && isset($rowValues[$noPaketIdx])) ? $rowValues[$noPaketIdx] : '';
                if (in_array(strtolower($noPaket), ['nan', 'none'], true)) $noPaket = '';

                $sesiIdx = $this->findMappingIndex($mapping, 'sesi');
                $rowSesi = ($sesiIdx !== -1 && !empty($rowValues[$sesiIdx])) ? strtoupper($rowValues[$sesiIdx]) : $currentMeta['sesi'];

                $tglIdx = $this->findMappingIndex($mapping, 'tanggal');
                $rawTglCell = ($tglIdx !== -1 && isset($rowValues[$tglIdx])) ? trim($rowValues[$tglIdx]) : '';
                $rowTgl = $currentMeta['tanggal'];
                if ($rawTglCell !== '') {
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $rawTglCell, $mTgl)) {
                        $rowTgl = $mTgl[1];
                    } elseif (($ts = strtotime($rawTglCell)) !== false && $ts > 946684800) {
                        $rowTgl = date('Y-m-d', $ts);
                    }
                }

                $ekspIdx    = $this->findMappingIndex($mapping, 'nama_ekspedisi');
                $layananIdx = $this->findMappingIndex($mapping, 'layanan_ekspedisi');
                $cutoffIdx  = $this->findMappingIndex($mapping, 'cutoff_time');
                $qtyIdx     = $this->findMappingIndex($mapping, 'jumlah_item');

                $results[] = [
                    'no_paket'          => !empty($noPaket) ? $noPaket : null,
                    'no_pesanan'        => $noPesanan,
                    'nama_pemesan'      => $namaPemesan === '-' ? '-' : mb_convert_case($namaPemesan, MB_CASE_TITLE, 'UTF-8'),
                    'kode'              => strtoupper($kode),
                    'sesi'              => $rowSesi,
                    'tanggal'           => $rowTgl,
                    'jam'               => $currentMeta['jam'],
                    'nama_ekspedisi'    => ($ekspIdx !== -1 && !empty($rowValues[$ekspIdx])) ? strtoupper($rowValues[$ekspIdx]) : null,
                    'layanan_ekspedisi' => ($layananIdx !== -1 && !empty($rowValues[$layananIdx])) ? strtoupper($rowValues[$layananIdx]) : null,
                    'cutoff_time'       => ($cutoffIdx !== -1 && !empty($rowValues[$cutoffIdx])) ? $rowValues[$cutoffIdx] : null,
                    'jumlah_item'       => ($qtyIdx !== -1 && !empty($rowValues[$qtyIdx])) ? max(1, (int)$rowValues[$qtyIdx]) : 1,
                ];
            }
        }

        return $results;
    }

    private function parseRowMetadata(string $rowStr): array
    {
        $meta = ['tanggal' => null, 'sesi' => null, 'jam' => null];

        if (preg_match('/(\w+),\s*(\d{1,2})\s+(\w+)\s+(\d{4})/i', $rowStr, $matches)) {
            $dayNum = $matches[2];
            $month  = strtolower($matches[3]);
            $year   = $matches[4];

            $months = [
                'januari' => '01', 'februari' => '02', 'maret' => '03', 'april' => '04',
                'mei' => '05', 'juni' => '06', 'juli' => '07', 'agustus' => '08',
                'september' => '09', 'oktober' => '10', 'november' => '11', 'desember' => '12',
                'january' => '01', 'february' => '02', 'march' => '03', 'may' => '05',
                'june' => '06', 'july' => '07', 'august' => '08'
            ];

            $monthNum = $months[$month] ?? '01';
            $meta['tanggal'] = "{$year}-{$monthNum}-" . str_pad($dayNum, 2, '0', STR_PAD_LEFT);
        }

        if (preg_match('/(Sesi\s*\d+)/i', $rowStr, $matches)) {
            $meta['sesi'] = strtoupper($matches[1]);
        }

        if (preg_match('/(\d{2}\.\d{2}[-\s]*\d{2}\.\d{2})/', $rowStr, $matches)) {
            $meta['jam'] = str_replace(' ', '', $matches[1]);
        }

        return $meta;
    }

    private function findMappingIndex(array $mapping, string $field): int
    {
        foreach ($mapping as $idx => $val) {
            if ($val === $field) return $idx;
        }
        return -1;
    }

    public function importFromCsv($filePath, $userId, array $importOptions = [])
    {
        return $this->importFromExcel($filePath, $userId, $importOptions);
    }

    /**
     * Sinkronisasi item manifest mentah (1-kolom / multi-sheet tanpa header standar)
     * ke tabel antrean `paket` sekaligus memicu perhitungan Priority Scheduling + Aging.
     */
    public function syncManifestItemsToQueue(array $manifestItems, int $userId, array $importOptions = []): array
    {
        if (empty($manifestItems)) {
            return [
                'success'         => false,
                'message'         => 'Tidak ada data resi manifest untuk disinkronkan.',
                'inserted'        => 0,
                'updated'         => 0,
                'skipped'         => 0,
                'tanggal'         => '',
                'raw_dates'       => [],
                'applied_profile' => ['mode' => 'auto'],
            ];
        }

        $importMode     = strtolower(trim((string)($importOptions['import_mode'] ?? 'auto')));
        $optEkspedisi   = strtoupper(trim((string)($importOptions['nama_ekspedisi'] ?? '')));
        $optLayanan     = strtoupper(trim((string)($importOptions['layanan_ekspedisi'] ?? '')));
        $optCutoff      = trim((string)($importOptions['cutoff_time'] ?? ''));
        $defaultTanggal = trim((string)($importOptions['default_tanggal'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultTanggal)) {
            $defaultTanggal = date('Y-m-d');
        }

        $isManualOverride = (
            $importMode === 'manual' &&
            $optEkspedisi !== '' &&
            !in_array($optEkspedisi, ['AUTO', 'UMUM', 'NON SPX'], true)
        );

        $appliedProfile = ['mode' => 'auto'];
        if ($isManualOverride) {
            $resolvedBatch = $this->priorityService->resolveParameters(
                $optEkspedisi,
                $optLayanan !== '' ? $optLayanan : null,
                $optCutoff !== '' ? $optCutoff : null,
                '',
                '',
                ($optEkspedisi === 'INSTANT' ? 'INSTAN' : ($optEkspedisi === 'SAMEDAY' ? 'SAMEDAY' : 'SESI 1'))
            );
            $appliedProfile = [
                'mode'              => 'manual',
                'nama_ekspedisi'    => $resolvedBatch['nama_ekspedisi'],
                'layanan_ekspedisi' => $resolvedBatch['layanan_ekspedisi'],
                'cutoff_time'       => substr((string)$resolvedBatch['cutoff_time'], 0, 5),
            ];
        }

        // Deduplikasi resi di dalam file
        $uniqueItems = [];
        $seenResis   = [];
        $skipped     = 0;
        foreach ($manifestItems as $mItem) {
            $resi = trim((string)($mItem['resi'] ?? ''));
            if ($resi === '') {
                continue;
            }
            if (isset($seenResis[$resi])) {
                $skipped++;
                continue;
            }
            $seenResis[$resi] = true;
            $uniqueItems[]    = $mItem;
        }

        if (empty($uniqueItems)) {
            return [
                'success'         => false,
                'message'         => 'Tidak ada nomor resi valid pada file manifest.',
                'inserted'        => 0,
                'updated'         => 0,
                'skipped'         => $skipped,
                'tanggal'         => '',
                'raw_dates'       => [],
                'applied_profile' => $appliedProfile,
            ];
        }

        $db = Database::getInstance();

        // Ambil seluruh resi aktif yang sudah ada di tabel paket secara chunked
        $existingMap = [];
        $allResis    = array_keys($seenResis);
        foreach (array_chunk($allResis, 1000) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $rows = $db->fetchAll(
                "SELECT id, no_pesanan, status, nama_ekspedisi, layanan_ekspedisi, cutoff_time, tanggal, no_paket, kode, sesi, jam, jumlah_item
                 FROM paket
                 WHERE is_deleted = 0 AND no_pesanan IN ({$placeholders})",
                array_values($chunk)
            );
            foreach ($rows as $r) {
                $existingMap[$r['no_pesanan']] = $r;
            }
        }

        $inserted        = 0;
        $updated         = 0;
        $affectedDates   = [];
        $nextNoPaketMap  = [];
        $rowsToInsert    = [];

        foreach ($uniqueItems as $mItem) {
            $resi = trim((string)$mItem['resi']);
            $rawTgl = trim((string)($mItem['tanggal'] ?? ''));
            $tgl = $defaultTanggal;
            if ($rawTgl !== '') {
                if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $rawTgl, $mTgl)) {
                    $tgl = $mTgl[1];
                } elseif (($ts = strtotime($rawTgl)) !== false && $ts > 946684800) {
                    $tgl = date('Y-m-d', $ts);
                }
            }

            $sheetOrEksp = strtoupper(trim((string)($mItem['sheet_name'] ?? ($mItem['ekspedisi'] ?? ''))));
            if ($sheetOrEksp === 'INSTAN') {
                $sheetOrEksp = 'INSTANT';
            }
            if (in_array($sheetOrEksp, ['', 'UMUM', 'NON SPX', 'SHEET1', 'WORKSHEET', 'DATA'], true)) {
                $sheetOrEksp = ($optEkspedisi !== '' && !in_array($optEkspedisi, ['AUTO', 'UMUM', 'NON SPX'], true))
                    ? $optEkspedisi
                    : '';
            }

            $sesiDefault = 'SESI 1';
            if ($isManualOverride) {
                if ($optEkspedisi === 'INSTANT') $sesiDefault = 'INSTAN';
                elseif ($optEkspedisi === 'SAMEDAY') $sesiDefault = 'SAMEDAY';
            } elseif ($sheetOrEksp === 'INSTANT') {
                $sesiDefault = 'INSTAN';
            } elseif ($sheetOrEksp === 'SAMEDAY') {
                $sesiDefault = 'SAMEDAY';
            }

            $resolved = $this->priorityService->resolveParameters(
                $isManualOverride ? $optEkspedisi : ($sheetOrEksp !== '' ? $sheetOrEksp : null),
                $isManualOverride ? ($optLayanan !== '' ? $optLayanan : null) : null,
                $isManualOverride ? ($optCutoff !== '' ? $optCutoff : null) : null,
                $resi,
                '',
                $sesiDefault
            );

            $isInstantItem = self::isInstantOrSameday(
                $resolved['nama_ekspedisi'] ?? null,
                $resolved['layanan_ekspedisi'] ?? null,
                $sesiDefault
            );

            // Antrean tabel `paket` HANYA dikhususkan untuk paket INSTAN / SAMEDAY.
            // Lewati penyimpanan ke antrean `paket` jika ekspedisi/layanan adalah REGULER.
            if (!$isInstantItem) {
                continue;
            }

            if (isset($existingMap[$resi])) {
                $ex = $existingMap[$resi];
                if ($isManualOverride && ($ex['status'] ?? '') === 'Pending') {
                    $currEksp    = strtoupper((string)($ex['nama_ekspedisi'] ?? ''));
                    $currLayanan = strtoupper((string)($ex['layanan_ekspedisi'] ?? ''));
                    $currCutoff  = substr((string)($ex['cutoff_time'] ?? ''), 0, 5);
                    $newCutoff   = substr((string)$resolved['cutoff_time'], 0, 5);

                    if (
                        $currEksp !== $resolved['nama_ekspedisi'] ||
                        $currLayanan !== $resolved['layanan_ekspedisi'] ||
                        $currCutoff !== $newCutoff
                    ) {
                        $this->paketModel->update((int)$ex['id'], [
                            'no_paket'          => $ex['no_paket'],
                            'no_pesanan'        => $ex['no_pesanan'],
                            'nama_pemesan'      => $mItem['nama_penerima'] && $mItem['nama_penerima'] !== '-' ? $mItem['nama_penerima'] : '-',
                            'kode'              => $ex['kode'] ?? '',
                            'tanggal'           => $ex['tanggal'],
                            'sesi'              => $ex['sesi'] ?: $sesiDefault,
                            'jam'               => $ex['jam'] ?? null,
                            'nama_ekspedisi'    => $resolved['nama_ekspedisi'],
                            'layanan_ekspedisi' => $resolved['layanan_ekspedisi'],
                            'cutoff_time'       => $resolved['cutoff_time'],
                            'jumlah_item'       => (int)($ex['jumlah_item'] ?? 1),
                        ]);
                        $updated++;
                        $affectedDates[$ex['tanggal']] = true;
                        continue;
                    }
                }
                $skipped++;
                continue;
            }

            if (!isset($nextNoPaketMap[$tgl])) {
                $maxRow = $db->fetch(
                    "SELECT COALESCE(MAX(CAST(no_paket AS UNSIGNED)), 0) AS max_no
                     FROM paket
                     WHERE tanggal = :tanggal AND is_deleted = 0",
                    ['tanggal' => $tgl]
                );
                $nextNoPaketMap[$tgl] = ((int)($maxRow['max_no'] ?? 0)) + 1;
            }

            $noPaket = (string)$nextNoPaketMap[$tgl]++;
            $namaPenerima = trim((string)($mItem['nama_penerima'] ?? '-'));
            if ($namaPenerima === '') $namaPenerima = '-';
            $alamatKode = trim((string)($mItem['alamat'] ?? ''));
            if ($alamatKode === '-') $alamatKode = '';
            if (mb_strlen($alamatKode) > 50) {
                $alamatKode = mb_substr($alamatKode, 0, 50);
            }

            $rowsToInsert[] = [
                'no_paket'          => $noPaket,
                'no_pesanan'        => $resi,
                'nama_pemesan'      => $namaPenerima,
                'kode'              => strtoupper($alamatKode),
                'tanggal'           => $tgl,
                'sesi'              => $sesiDefault,
                'jam'               => null,
                'nama_ekspedisi'    => $resolved['nama_ekspedisi'],
                'layanan_ekspedisi' => $resolved['layanan_ekspedisi'],
                'cutoff_time'       => $resolved['cutoff_time'],
                'jumlah_item'       => 1,
                'priority_score'    => 0,
                'status'            => 'Pending',
                'user_id'           => $userId,
                'is_deleted'        => 0,
            ];
            $affectedDates[$tgl] = true;
        }

        if (!empty($rowsToInsert)) {
            foreach (array_chunk($rowsToInsert, 500) as $batch) {
                $valPlaceholders = [];
                $params = [];
                foreach ($batch as $r) {
                    $valPlaceholders[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    array_push(
                        $params,
                        $r['no_paket'],
                        $r['no_pesanan'],
                        $r['nama_pemesan'],
                        $r['kode'],
                        $r['tanggal'],
                        $r['sesi'],
                        $r['jam'],
                        $r['nama_ekspedisi'],
                        $r['layanan_ekspedisi'],
                        $r['cutoff_time'],
                        $r['jumlah_item'],
                        $r['priority_score'],
                        $r['status'],
                        $r['user_id'],
                        $r['is_deleted']
                    );
                }
                $sqlBatch = "INSERT INTO paket (no_paket, no_pesanan, nama_pemesan, kode, tanggal, sesi, jam, nama_ekspedisi, layanan_ekspedisi, cutoff_time, jumlah_item, priority_score, status, user_id, is_deleted) VALUES " . implode(', ', $valPlaceholders);
                $db->query($sqlBatch, $params);
                $inserted += count($batch);
            }
        }

        foreach (array_keys($affectedDates) as $tgl) {
            $this->priorityService->recalculateDailyQueue($tgl, false);
        }

        $tanggalDates = array_keys($affectedDates);
        if (empty($tanggalDates)) {
            $tanggalDates = [$defaultTanggal];
        }
        $formattedDates = array_map(fn($d) => date('d-m-Y', strtotime($d)), $tanggalDates);
        $tanggalStr     = implode(', ', $formattedDates);

        $parts = [];
        if ($inserted > 0) $parts[] = "{$inserted} paket baru masuk antrean";
        if ($updated > 0)  $parts[] = "{$updated} paket Pending diperbarui SLA/Ekspedisi";
        $summaryStr = !empty($parts) ? implode(', ', $parts) : "0 paket diproses";

        return [
            'success'         => true,
            'message'         => "Sinkronisasi antrean berhasil: {$summaryStr} ({$skipped} dilewati).",
            'inserted'        => $inserted,
            'updated'         => $updated,
            'skipped'         => $skipped,
            'tanggal'         => $tanggalStr,
            'raw_dates'       => $tanggalDates,
            'applied_profile' => $appliedProfile,
        ];
    }

    public function bulkSoftDeletePackages(array $ids): array
    {
        return $this->paketModel->bulkSoftDelete($ids);
    }
}

<?php
/**
 * HasilEkspedisiController.php (Terintegrasi SHA-256 Manifest & Resi Hash)
 */

require_once BASE_PATH . '/app/Models/HasilEkspedisi.php';
require_once BASE_PATH . '/app/Services/IntegritySha256Service.php';
require_once BASE_PATH . '/app/Services/PaketService.php';

class HasilEkspedisiController extends Controller
{
    private HasilEkspedisi $model;
    private IntegritySha256Service $integrityService;
    private PaketService $paketService;

    public function __construct()
    {
        $this->model            = new HasilEkspedisi();
        $this->integrityService = new IntegritySha256Service();
        $this->paketService     = new PaketService();
    }

    public function index(): void
    {
        $search    = $_GET['search'] ?? '';
        $ekspedisi = $_GET['ekspedisi'] ?? '';

        $today = date('Y-m-d');
        if (isset($_GET['start_date']) || isset($_GET['end_date'])) {
            $startDate = $_GET['start_date'] ?? '';
            $endDate   = $_GET['end_date'] ?? '';
        } else {
            $startDate = $today;
            $endDate   = $today;
        }
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        $items = $this->model->getAllWithDetails($limit, $offset, $search, $ekspedisi, $startDate, $endDate);
        $total = $this->model->countFiltered($search, $ekspedisi, $startDate, $endDate);
        $stats = $this->model->getStats($search, $ekspedisi, $startDate, $endDate);

        // Tanggal operasional aktif untuk form Tambah Paket, Import, Rekalkulasi & Hapus
        if (!empty($startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $activeQueueDate = $startDate;
        } elseif (!empty($endDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $activeQueueDate = $endDate;
        } else {
            $activeQueueDate = $today;
        }

        // Statistik ringkas antrean paket yang tersinkronisasi dengan filter aktif di Hasil Ekspedisi
        $queueStats = $this->getFilteredQueueStats($search, $ekspedisi, $startDate, $endDate);

        $this->view('hasil_ekspedisi.index', [
            'title'           => 'Hasil Ekspedisi (SHA-256 Manifest)',
            'items'           => $items,
            'stats'           => $stats,
            'queueStats'      => $queueStats,
            'manifestFiles'   => $this->model->getManifestFileList(),
            'activeQueueDate' => $activeQueueDate,
            'masterEkspedisi' => $this->paketService->getMasterEkspedisi(),
            'search'          => $search,
            'ekspedisi'       => $ekspedisi,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'page'            => $page,
            'totalPages'      => ceil($total / $limit),
            'total'           => $total
        ]);
    }

    /**
     * Export Laporan Excel Multi-Ekspedisi Terpadu
     * Mendukung pilihan ekspedisi: INSTAN, SPX, JNE, JNT, LEX, ID, NINJA, SICEPAT, ALL
     * Mendukung pilihan file manifest spesifik dan rentang tanggal filter
     */
    public function export(): void
    {
        $ekspedisi = strtoupper(trim((string)($_GET['ekspedisi'] ?? $_POST['ekspedisi'] ?? 'ALL')));
        if ($ekspedisi === '' || $ekspedisi === 'SEMUA') {
            $ekspedisi = 'ALL';
        }

        $fileId    = (int)($_GET['file_id'] ?? $_POST['file_id'] ?? 0);
        $startDate = trim((string)($_GET['start_date'] ?? $_POST['start_date'] ?? ''));
        $endDate   = trim((string)($_GET['end_date'] ?? $_POST['end_date'] ?? ''));

        // Ambil data export dari model
        $exportData = $this->model->getExportData($ekspedisi, $startDate, $endDate, $fileId > 0 ? $fileId : null);
        $type       = $exportData['type'];
        $rows       = $exportData['rows'];
        $count      = count($rows);

        // Keterangan periode tanggal untuk header Excel
        $periodeText = 'Semua Periode';
        if ($startDate !== '' && $endDate !== '') {
            $periodeText = date('d/m/Y', strtotime($startDate)) . ' s/d ' . date('d/m/Y', strtotime($endDate));
        } elseif ($startDate !== '') {
            $periodeText = 'Mulai ' . date('d/m/Y', strtotime($startDate));
        } elseif ($endDate !== '') {
            $periodeText = 'Sampai ' . date('d/m/Y', strtotime($endDate));
        }

        // Keterangan nama file untuk header Excel
        $fileInfoText = 'Semua File Manifest / Seluruh Batch';
        if ($fileId > 0 && !empty($rows)) {
            $firstRowFile = $rows[0]['manifest_file'] ?? '';
            if ($firstRowFile) {
                $fileInfoText = 'Berkas: ' . $firstRowFile;
            }
        }

        $writer = new XlsxWriter();
        $sheetData = [];
        $cellStyles = [];

        if ($type === 'INSTAN') {
            // FORMAT KHUSUS ANTREAN PAKET INSTAN / SAMEDAY (PRIORITY SCHEDULING)
            $writer->setColWidths([6, 12, 22, 28, 14, 14, 12, 16, 20, 10, 14, 16]);

            // Baris 1: Judul Organisasi
            $sheetData[] = ["PT POLO SPORT INDONESIA - SISTEM LOGISTIK & EKSPEDISI", "", "", "", "", "", "", "", "", "", "", ""];
            $cellStyles["A1"] = 4;
            $writer->addMergeCell("A1:L1");

            // Baris 2: Judul Laporan
            $sheetData[] = ["LAPORAN ANTREAN PENGIRIMAN PAKET INSTAN & SAMEDAY (PRIORITY SCHEDULING)", "", "", "", "", "", "", "", "", "", "", ""];
            $cellStyles["A2"] = 4;
            $writer->addMergeCell("A2:L2");

            // Baris 3: Metadata
            $sheetData[] = ["Periode: {$periodeText} | Algoritma: Non-Preemptive + Aging | Dicetak: " . date('d/m/Y H:i') . " | Total Antrean: {$count} Paket", "", "", "", "", "", "", "", "", "", "", ""];
            $cellStyles["A3"] = 5;
            $writer->addMergeCell("A3:L3");

            // Baris 4: Spacer
            $sheetData[] = ["", "", "", "", "", "", "", "", "", "", "", ""];

            // Baris 5: Table Header
            $headers = ["NO", "NO PAKET", "NO PESANAN", "NAMA PEMESAN", "EKSPEDISI", "LAYANAN", "CUT-OFF", "SKOR PRIORITAS", "TANGGAL MASUK", "SESI", "STATUS", "PETUGAS"];
            $sheetData[] = $headers;
            for ($c = 0; $c < 12; $c++) {
                $coord = XlsxWriter::colLetter($c) . "5";
                $cellStyles[$coord] = 3;
            }

            $statsCount = ['Sudah' => 0, 'Pending' => 0, 'Batal' => 0, 'Retur' => 0];
            $startRow = 6;

            if (empty($rows)) {
                $sheetData[] = ["-", "-", "Tidak ada data antrean paket instan", "-", "-", "-", "-", "-", "-", "-", "-", "-"];
                for ($c = 0; $c < 12; $c++) {
                    $cellStyles[XlsxWriter::colLetter($c) . "6"] = 1;
                }
                $startRow = 7;
            } else {
                foreach ($rows as $idx => $r) {
                    $rNum = $startRow + $idx;
                    $isEven = ($idx % 2 !== 0);

                    $st = trim((string)($r['status'] ?? 'Pending'));
                    if (isset($statsCount[$st])) {
                        $statsCount[$st]++;
                    } else {
                        $statsCount[$st] = 1;
                    }

                    $cutoff = !empty($r['cutoff_time']) ? substr((string)$r['cutoff_time'], 0, 5) : '-';
                    $scoreVal = number_format((float)($r['priority_score'] ?? 0), 2);
                    $tglMasuk = !empty($r['tanggal_masuk']) ? date('d/m/Y H:i', strtotime($r['tanggal_masuk'])) : (!empty($r['tanggal']) ? date('d/m/Y', strtotime($r['tanggal'])) : '-');

                    $sheetData[] = [
                        $idx + 1,
                        $r['no_paket'] ?? ($idx + 1),
                        $r['no_pesanan'] ?? '-',
                        $r['nama_pemesan'] ?? '-',
                        $r['nama_ekspedisi'] ?? 'INSTANT',
                        $r['layanan_ekspedisi'] ?? 'INSTANT',
                        $cutoff,
                        $scoreVal,
                        $tglMasuk,
                        $r['sesi'] ?? 'SESI 1',
                        $st,
                        $r['petugas_input'] ?? '-'
                    ];

                    for ($c = 0; $c < 12; $c++) {
                        $coord = XlsxWriter::colLetter($c) . $rNum;
                        if ($c === 10) { // Status
                            if ($st === 'Sudah') {
                                $cellStyles[$coord] = $isEven ? 7 : 6;
                            } elseif ($st === 'Batal' || $st === 'Retur') {
                                $cellStyles[$coord] = $isEven ? 9 : 8;
                            } else {
                                $cellStyles[$coord] = $isEven ? 11 : 10;
                            }
                        } else {
                            $cellStyles[$coord] = $isEven ? 2 : 1;
                        }
                    }
                }
                $startRow += $count;
            }

            // Summary Footer Row
            $footerRow = [
                "TOTAL REKAPITULASI ANTREAN", "", "", "", "",
                "Total: {$count}",
                "Sudah: " . ($statsCount['Sudah'] ?? 0),
                "Pending: " . ($statsCount['Pending'] ?? 0),
                "Batal: " . ($statsCount['Batal'] ?? 0),
                "Retur: " . ($statsCount['Retur'] ?? 0),
                "PT POLO SPORT", ""
            ];
            $sheetData[] = $footerRow;
            $writer->addMergeCell("A{$startRow}:E{$startRow}");
            $writer->addMergeCell("K{$startRow}:L{$startRow}");

            for ($c = 0; $c < 12; $c++) {
                $coord = XlsxWriter::colLetter($c) . $startRow;
                if ($c === 0) {
                    $cellStyles[$coord] = 12; // Label
                } elseif ($c === 6) {
                    $cellStyles[$coord] = 13; // Green (Sudah)
                } elseif ($c === 8 || $c === 9) {
                    $cellStyles[$coord] = 14; // Red (Batal/Retur)
                } else {
                    $cellStyles[$coord] = 15; // Standard Bold
                }
            }

            $writer->writeSheet($sheetData);
            $writer->setCellStyles($cellStyles);

            $filename = 'LAPORAN_INSTAN_PRIORITY_' . date('Ymd_His') . '.xlsx';
            $writer->download($filename);
            return;
        }

        // FORMAT REGULER & ALL EKSPEDISI (SPX, JNE, JNT, LEX, ID, NINJA, SICEPAT, ALL)
        $writer->setColWidths([6, 24, 26, 32, 14, 16, 20, 28, 30]);

        // Baris 1: Judul Organisasi
        $sheetData[] = ["PT POLO SPORT INDONESIA - SISTEM LOGISTIK & EKSPEDISI", "", "", "", "", "", "", "", ""];
        $cellStyles["A1"] = 4;
        $writer->addMergeCell("A1:I1");

        // Baris 2: Judul Laporan Sesuai Ekspedisi
        $reportTitle = $exportData['title'];
        $sheetData[] = [$reportTitle, "", "", "", "", "", "", "", ""];
        $cellStyles["A2"] = 4;
        $writer->addMergeCell("A2:I2");

        // Baris 3: Metadata & Segel SHA-256
        $sheetData[] = ["Periode: {$periodeText} | {$fileInfoText} | Dicetak: " . date('d/m/Y H:i') . " | Integritas: Aggregate SHA-256 Terkunci", "", "", "", "", "", "", "", ""];
        $cellStyles["A3"] = 5;
        $writer->addMergeCell("A3:I3");

        // Baris 4: Spacer
        $sheetData[] = ["", "", "", "", "", "", "", "", ""];

        // Baris 5: Table Header
        $headers = ["NO", "NO RESI / WAYBILL", "NAMA PENERIMA", "ALAMAT / TUJUAN", "EKSPEDISI", "STATUS SCAN", "TANGGAL SCAN", "FILE MANIFEST", "SEKUEN HASH (SHA-256)"];
        $sheetData[] = $headers;
        for ($c = 0; $c < 9; $c++) {
            $coord = XlsxWriter::colLetter($c) . "5";
            $cellStyles[$coord] = 3;
        }

        $statsScan = ['Berhasil' => 0, 'Pending' => 0, 'Lainnya' => 0];
        $startRow = 6;

        if (empty($rows)) {
            $sheetData[] = ["-", "-", "Tidak ada data resi ekspedisi untuk filter yang dipilih", "-", "-", "-", "-", "-", "-"];
            for ($c = 0; $c < 9; $c++) {
                $cellStyles[XlsxWriter::colLetter($c) . "6"] = 1;
            }
            $startRow = 7;
        } else {
            foreach ($rows as $idx => $r) {
                $rNum = $startRow + $idx;
                $isEven = ($idx % 2 !== 0);

                $status = trim((string)($r['status_scan'] ?? 'Berhasil'));
                if ($status === '') $status = 'Berhasil';

                $stLower = strtolower($status);
                if (in_array($stLower, ['berhasil', 'sudah', 'terkirim', 'valid'])) {
                    $statsScan['Berhasil']++;
                } elseif (in_array($stLower, ['pending', 'proses', 'antri'])) {
                    $statsScan['Pending']++;
                } else {
                    $statsScan['Lainnya']++;
                }

                $tgl = !empty($r['tanggal']) ? date('d/m/Y H:i', strtotime($r['tanggal'])) : '-';
                $hashDisplay = !empty($r['resi_hash']) ? substr($r['resi_hash'], 0, 16) . '...' : (!empty($r['manifest_hash']) ? substr($r['manifest_hash'], 0, 16) . '...' : 'SHA256-OK');

                $sheetData[] = [
                    $idx + 1,
                    $r['resi'] ?? '-',
                    $r['nama_penerima'] ?: '-',
                    $r['alamat'] ?: '-',
                    $r['ekspedisi'] ?? $ekspedisi,
                    $status,
                    $tgl,
                    $r['manifest_file'] ?: '-',
                    $hashDisplay
                ];

                for ($c = 0; $c < 9; $c++) {
                    $coord = XlsxWriter::colLetter($c) . $rNum;
                    if ($c === 5) { // Status Scan
                        if (in_array($stLower, ['berhasil', 'sudah', 'terkirim', 'valid'])) {
                            $cellStyles[$coord] = $isEven ? 7 : 6; // Green
                        } elseif (in_array($stLower, ['pending', 'proses', 'antri'])) {
                            $cellStyles[$coord] = $isEven ? 11 : 10; // Yellow/Gray
                        } else {
                            $cellStyles[$coord] = $isEven ? 9 : 8; // Red
                        }
                    } else {
                        $cellStyles[$coord] = $isEven ? 2 : 1;
                    }
                }
            }
            $startRow += $count;
        }

        // Summary Footer Row
        $footerRow = [
            "TOTAL MANIFEST RESI TEREKAPITULASI", "", "", "",
            "Total: {$count}",
            "Berhasil: " . ($statsScan['Berhasil'] ?? 0),
            "Pending: " . ($statsScan['Pending'] ?? 0),
            "Lainnya: " . ($statsScan['Lainnya'] ?? 0),
            "Segel SHA-256 Valid"
        ];
        $sheetData[] = $footerRow;
        $writer->addMergeCell("A{$startRow}:D{$startRow}");

        for ($c = 0; $c < 9; $c++) {
            $coord = XlsxWriter::colLetter($c) . $startRow;
            if ($c === 0) {
                $cellStyles[$coord] = 12; // Label Left
            } elseif ($c === 5) {
                $cellStyles[$coord] = 13; // Green Value
            } elseif ($c === 7) {
                $cellStyles[$coord] = 14; // Red/Other Value
            } elseif ($c === 8) {
                $cellStyles[$coord] = 13; // Green SHA-256 Seal Valid
            } else {
                $cellStyles[$coord] = 15; // Bold Standard
            }
        }

        $writer->writeSheet($sheetData);
        $writer->setCellStyles($cellStyles);

        $ekspClean = preg_replace('/[^A-Z0-9]/', '_', $ekspedisi);
        $filename = 'LAPORAN_EKSPEDISI_' . $ekspClean . '_' . date('Ymd_His') . '.xlsx';
        $writer->download($filename);
    }

    /**
     * Hitung status antrean (Total, Pending, Sudah, Batal, Retur) sesuai filter halaman Hasil Ekspedisi.
     */
    private function getFilteredQueueStats(string $search, string $ekspedisi, string $startDate, string $endDate): array
    {
        $db     = Database::getInstance();
        $conds  = ["is_deleted = 0"];
        $params = [];

        if ($search !== '') {
            $conds[]  = "(no_pesanan LIKE ? OR nama_pemesan LIKE ? OR no_paket LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($ekspedisi !== '') {
            $ekspUpper = strtoupper(trim($ekspedisi));
            if ($ekspUpper === 'NON SPX') {
                $conds[] = "UPPER(nama_ekspedisi) <> 'SPX'";
            } elseif ($ekspUpper === 'INSTAN' || $ekspUpper === 'INSTANT' || $ekspUpper === 'SAMEDAY') {
                $conds[] = "UPPER(nama_ekspedisi) IN ('INSTAN', 'INSTANT', 'SAMEDAY')";
            } else {
                $conds[]  = "UPPER(nama_ekspedisi) = ?";
                $params[] = $ekspUpper;
            }
        }
        if ($startDate !== '') {
            $conds[]  = "tanggal >= ?";
            $params[] = $startDate;
        }
        if ($endDate !== '') {
            $conds[]  = "tanggal <= ?";
            $params[] = $endDate;
        }

        $sql = "SELECT
                    COUNT(*) as total,
                    COALESCE(SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END), 0) as pending,
                    COALESCE(SUM(CASE WHEN status = 'Sudah' THEN 1 ELSE 0 END), 0) as sudah,
                    COALESCE(SUM(CASE WHEN status = 'Batal' THEN 1 ELSE 0 END), 0) as batal,
                    COALESCE(SUM(CASE WHEN status = 'Retur' THEN 1 ELSE 0 END), 0) as retur
                FROM paket
                WHERE " . implode(' AND ', $conds);

        $row = $db->fetch($sql, $params);
        return [
            'total'   => (int)($row['total'] ?? 0),
            'pending' => (int)($row['pending'] ?? 0),
            'sudah'   => (int)($row['sudah'] ?? 0),
            'batal'   => (int)($row['batal'] ?? 0),
            'retur'   => (int)($row['retur'] ?? 0),
        ];
    }

    /**
     * Endpoint Import Excel Terpadu (Single Point of Entry):
     * 1) Menyimpan & menyegel manifest resi dengan Aggregate SHA-256 (`hasil_ekspedisi` & `hasil_ekspedisi_detail`).
     * 2) Menyimpan/memperbarui antrean paket & mengkalkulasi skor Priority Scheduling Non-Preemptive + Aging (`paket`).
     */
    public function store(): void
    {
        if (!$this->validateCsrf()) return;

        $uploadedFile = $_FILES['excel_file'] ?? ($_FILES['import_file'] ?? null);
        if (!$uploadedFile || ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errCode = $uploadedFile['error'] ?? 'UNKNOWN';
            $msg = "Silakan pilih file Excel/CSV yang valid (Error Code: {$errCode}).";
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            } 
            $this->flash('error', $msg);
            $this->redirect('/hasil-ekspedisi');
            return;
        }

        $file = $uploadedFile;
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx', 'xls'], true)) {
            $msg = 'Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            $this->flash('error', $msg);
            $this->redirect('/hasil-ekspedisi');
            return;
        }

        $importMode       = strtolower(trim((string)($_POST['import_mode'] ?? 'manual')));
        $namaEkspedisi    = strtoupper(trim((string)($_POST['nama_ekspedisi'] ?? ($_POST['ekspedisi'] ?? ''))));
        $layananEkspedisi = strtoupper(trim((string)($_POST['layanan_ekspedisi'] ?? '')));
        $cutoffTime       = trim((string)($_POST['cutoff_time'] ?? ''));
        $defaultTanggal   = trim((string)($_POST['default_tanggal'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $defaultTanggal)) {
            $defaultTanggal = date('Y-m-d');
        }

        if ($namaEkspedisi === 'INSTAN') {
            $namaEkspedisi = 'INSTANT';
        }
        if ($namaEkspedisi === 'AUTO') {
            $importMode = 'auto';
        }

        if ($importMode === 'manual' && $namaEkspedisi === '') {
            $msg = 'Silakan pilih Ekspedisi Penjemput / Kategori Paket terlebih dahulu sebelum memulai import.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            $this->flash('error', $msg);
            $this->redirect('/hasil-ekspedisi');
            return;
        }

        $uploadDir = BASE_PATH . '/storage/uploads/excel/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => 'Gagal membuat direktori storage.'], 500);
                    return;
                }
                $this->flash('error', 'Gagal membuat direktori storage.');
                $this->redirect('/hasil-ekspedisi');
                return;
            }
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $msg = 'Gagal memindahkan file ke storage. Cek izin folder storage.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 500);
                return;
            }
            $this->flash('error', $msg);
            $this->redirect('/hasil-ekspedisi');
            return;
        }

        $importOptions = [
            'import_mode'       => ($namaEkspedisi === 'NON SPX') ? 'auto' : $importMode,
            'nama_ekspedisi'    => ($namaEkspedisi === 'NON SPX') ? '' : $namaEkspedisi,
            'layanan_ekspedisi' => $layananEkspedisi,
            'cutoff_time'       => $cutoffTime,
            'default_tanggal'   => $defaultTanggal,
        ];

        $isSpecificManual = (
            $importMode === 'manual' &&
            $namaEkspedisi !== '' &&
            !in_array($namaEkspedisi, ['AUTO', 'NON SPX', 'UMUM'], true)
        );

        // Tahap A: Coba proses sebagai spreadsheet antrean terstruktur via PaketService
        $queueResult   = $this->paketService->importFromExcel($filePath, auth()['id'], $importOptions);
        $manifestItems = [];

        if (!empty($queueResult['success']) && !empty($queueResult['manifest_ready_items'])) {
            $manifestItems = $queueResult['manifest_ready_items'];
        } else {
            // Tahap B: Fallback ke parser manifest mentah (1-kolom / multi-sheet tanpa header standar)
            $nativeResult = $this->parseFileNative($filePath, $ext);
            if (!$nativeResult || empty($nativeResult['success']) || (int)($nativeResult['count'] ?? 0) === 0) {
                if (file_exists($filePath)) unlink($filePath);
                $errorMsg = $nativeResult['message'] ?? 'File tidak berisi data nomor pesanan/resi yang valid.';
                if ($this->isAjax()) {
                    $this->json(['success' => false, 'message' => $errorMsg], 400);
                    return;
                }
                $this->flash('error', $errorMsg);
                $this->redirect('/hasil-ekspedisi');
                return;
            }

            $manifestItems = $nativeResult['items'];
            // Sinkronkan item manifest mentah ke antrean `paket` & kalkulasi Priority Scheduling
            $queueResult = $this->paketService->syncManifestItemsToQueue($manifestItems, auth()['id'], $importOptions);
        }

        // Hitung SHA-256 berkas manifest asli
        $fileHash = $this->integrityService->hashFile($filePath);

        $primaryDate = !empty($queueResult['raw_dates'][0]) ? $queueResult['raw_dates'][0] : $defaultTanggal;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $primaryDate)) {
            $primaryDate = date('Y-m-d');
        }
        $tanggalUploadStr = $primaryDate . ' ' . date('H:i:s');

        $allResis = array_values(array_unique(array_filter(array_map(
            fn($it) => trim((string)($it['resi'] ?? '')),
            $manifestItems
        ))));
        $existingResis    = $this->model->getExistingResis($allResis);
        $existingResisSet = array_flip($existingResis);

        $genericSheetNames = ['SHEET1', 'SHEET2', 'SHEET3', 'CSV', 'WORKSHEET', 'DATA', 'LAPORAN', ''];
        $priorityHelper    = new PrioritySchedulingService();

        $preparedNewItems = [];
        $seenInFile       = [];
        $skippedManifest  = 0;
        $distinctNewEksp  = [];

        foreach ($manifestItems as $item) {
            $resi = trim((string)($item['resi'] ?? ''));
            if ($resi === '' || isset($seenInFile[$resi]) || isset($existingResisSet[$resi])) {
                $skippedManifest++;
                continue;
            }

            if ($isSpecificManual) {
                $expName = $namaEkspedisi;
            } else {
                $candSheet = strtoupper(trim((string)($item['sheet_name'] ?? '')));
                $candEksp  = strtoupper(trim((string)($item['ekspedisi'] ?? '')));
                if (!in_array($candSheet, $genericSheetNames, true)) {
                    $expName = ($candSheet === 'INSTAN') ? 'INSTANT' : $candSheet;
                } elseif (!in_array($candEksp, array_merge($genericSheetNames, ['UMUM', 'NON SPX']), true)) {
                    $expName = ($candEksp === 'INSTAN') ? 'INSTANT' : $candEksp;
                } else {
                    $resolvedAuto = $priorityHelper->resolveParameters(null, null, null, $resi);
                    $expName = strtoupper((string)($resolvedAuto['nama_ekspedisi'] ?? 'SPX'));
                }
            }

            $seenInFile[$resi]          = true;
            $distinctNewEksp[$expName]  = true;
            $preparedNewItems[]         = [
                'resi'          => $resi,
                'nama_penerima' => trim((string)($item['nama_penerima'] ?? '-')) ?: '-',
                'alamat'        => trim((string)($item['alamat'] ?? '-')) ?: '-',
                'ekspedisi'     => $expName,
                'status_scan'   => trim((string)($item['status_scan'] ?? 'Pending')) ?: 'Pending',
                'tanggal'       => $item['tanggal'] ?? null,
            ];
        }

        if ($isSpecificManual) {
            $manifestHeaderEksp = $namaEkspedisi;
        } elseif ($namaEkspedisi === 'NON SPX') {
            $manifestHeaderEksp = 'NON SPX';
        } elseif (count($distinctNewEksp) === 1) {
            $manifestHeaderEksp = (string)array_key_first($distinctNewEksp);
        } elseif (count($distinctNewEksp) > 1) {
            $manifestHeaderEksp = isset($distinctNewEksp['SPX']) && count($distinctNewEksp) === 1 ? 'SPX' : 'NON SPX';
        } else {
            $manifestHeaderEksp = ($namaEkspedisi !== '' && $namaEkspedisi !== 'AUTO') ? $namaEkspedisi : 'SPX';
        }

        $insertedManifestCount = 0;
        $manifestHash          = null;
        $syncedManifestRows    = 0;

        $db = Database::getInstance();

        try {
            if (!empty($preparedNewItems)) {
                $db->beginTransaction();

                $hasilId = $this->model->create([
                    'nama_file'      => $file['name'],
                    'ekspedisi'      => $manifestHeaderEksp,
                    'tanggal_upload' => $tanggalUploadStr,
                    'jumlah_paket'   => 0,
                    'uploaded_by'    => auth()['id'],
                    'path_file'      => 'uploads/excel/' . $fileName,
                    'file_hash'      => $fileHash,
                ]);

                if (!$hasilId) {
                    throw new Exception("Gagal menyimpan metadata manifest ke database.");
                }

                $detailsToInsert = [];
                $orderedNewResis = [];
                $spxCount = $instanCount = $jneCount = $jntCount = $lexCount = $idExpressCount = $ninjaCount = $sicepatCount = $nonSpxCount = 0;

                foreach ($preparedNewItems as $pItem) {
                    $resi    = $pItem['resi'];
                    $expName = $pItem['ekspedisi'];

                    if ($expName === 'SPX') {
                        $spxCount++;
                    } elseif ($expName === 'INSTAN' || $expName === 'INSTANT' || $expName === 'SAMEDAY') {
                        $instanCount++;
                    } elseif (strpos($expName, 'JNE') !== false) {
                        $jneCount++;
                    } elseif (strpos($expName, 'JNT') !== false || strpos($expName, 'J&T') !== false) {
                        $jntCount++;
                    } elseif (strpos($expName, 'LEX') !== false || strpos($expName, 'LAZADA') !== false) {
                        $lexCount++;
                    } elseif ($expName === 'ID' || (strpos($expName, 'ID') !== false && strpos($expName, 'EXPRESS') !== false)) {
                        $idExpressCount++;
                    } elseif (strpos($expName, 'NINJA') !== false) {
                        $ninjaCount++;
                    } elseif (strpos($expName, 'SICEPAT') !== false) {
                        $sicepatCount++;
                    } else {
                        $nonSpxCount++;
                    }

                    $tglResi  = !empty($pItem['tanggal']) ? date('Y-m-d H:i:s', strtotime((string)$pItem['tanggal'])) : $tanggalUploadStr;
                    $resiHash = $this->integrityService->hashResiRecord($resi, $expName, $tglResi);

                    $detailsToInsert[] = [
                        'hasil_ekspedisi_id' => (int)$hasilId,
                        'resi'               => $resi,
                        'nama_penerima'      => $pItem['nama_penerima'],
                        'alamat'             => $pItem['alamat'],
                        'ekspedisi'          => $expName,
                        'status_scan'        => $pItem['status_scan'],
                        'resi_hash'          => $resiHash,
                        'tanggal'            => $tglResi,
                    ];
                    $orderedNewResis[] = $resi;
                }

                $insertedManifestCount = $this->model->insertBatchDetails($detailsToInsert);
                $manifestHash = $insertedManifestCount > 0
                    ? $this->integrityService->computeManifestAggregateHash($orderedNewResis, $manifestHeaderEksp)
                    : null;

                $this->model->updateStats((int)$hasilId, [
                    'jumlah_paket'  => $insertedManifestCount,
                    'manifest_hash' => $manifestHash,
                    'total_spx'     => $spxCount,
                    'total_instan'  => $instanCount,
                    'total_non_spx' => $nonSpxCount,
                    'total_jne'     => $jneCount,
                    'total_jnt'     => $jntCount,
                    'total_lex'     => $lexCount,
                    'total_id'      => $idExpressCount,
                    'total_ninja'   => $ninjaCount,
                    'total_sicepat' => $sicepatCount
                ]);

                $db->commit();

                // Sinkronisasi otomatis total paket manifest ke Target QTY modul Ekspedisi (Ambil Foto)
                try {
                    require_once BASE_PATH . '/app/Models/FotoEkspedisi.php';
                    $fotoEkspedisiModel = new FotoEkspedisi();
                    $fotoEkspedisiModel->syncManifestToTargetQty($primaryDate);
                } catch (\Throwable $e) {
                    // Fail-safe agar proses import tidak terganggu
                }
            } else {
                // Seluruh resi sudah ada di hasil_ekspedisi_detail aktif: jangan buat baris manifest 0 Paket
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                if ($isSpecificManual && (((int)($queueResult['updated'] ?? 0)) > 0 || ((int)($queueResult['inserted'] ?? 0)) > 0)) {
                    $syncedManifestRows = $this->model->syncPendingResisCategory($allResis, $namaEkspedisi, $this->integrityService);
                }
            }

            $qInserted    = (int)($queueResult['inserted'] ?? 0);
            $qUpdated     = (int)($queueResult['updated'] ?? 0);
            $qSkipped     = (int)($queueResult['skipped'] ?? 0);
            $tanggalLabel = e((string)($queueResult['tanggal'] ?? date('d-m-Y', strtotime($primaryDate))));

            $profile = $queueResult['applied_profile'] ?? ['mode' => 'auto'];
            if (($profile['mode'] ?? 'auto') === 'manual') {
                $pEksp    = e((string)($profile['nama_ekspedisi'] ?? $manifestHeaderEksp));
                $pLayanan = e((string)($profile['layanan_ekspedisi'] ?? 'REGULER'));
                $pCutoff  = e((string)($profile['cutoff_time'] ?? '17:00'));
                $kategoriLabel = "<span style='display:inline-block; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 8px; border-radius:6px; font-weight:700; font-size:12px;'>{$pEksp} &bull; {$pLayanan} (Cut-off {$pCutoff} WIB)</span>";
            } else {
                $kategoriLabel = "<span style='display:inline-block; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:2px 8px; border-radius:6px; font-weight:700; font-size:12px;'>Deteksi Otomatis Kolom / Sheet / Resi</span>";
            }

            $anyProcessed   = ($insertedManifestCount > 0 || $qInserted > 0 || $qUpdated > 0 || $syncedManifestRows > 0);
            $isInstantBatch = PaketService::isInstantOrSameday($namaEkspedisi, $layananEkspedisi);

            if ($anyProcessed) {
                if ($insertedManifestCount > 0 && $qInserted === 0 && !$isInstantBatch) {
                    $swalTitle = "Import Manifest {$manifestHeaderEksp} Berhasil (SHA-256 Tersegel)";
                } elseif ($insertedManifestCount === 0 && $qInserted === 0 && $qUpdated > 0) {
                    $swalTitle = 'Pembaruan Batch Ekspedisi & SLA Berhasil';
                } else {
                    $swalTitle = 'Import Manifest SHA-256 & Antrean Prioritas Berhasil';
                }

                $swalIcon = 'success';

                $hashSnippet = $manifestHash
                    ? "<div style='margin-top:6px; padding:6px 8px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; font-size:11.5px; color:#166534;'><b>Segel Aggregate SHA-256:</b> <code>" . substr($manifestHash, 0, 20) . "...</code> ({$insertedManifestCount} resi)</div>"
                    : ($syncedManifestRows > 0
                        ? "<div style='margin-top:6px; padding:6px 8px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; font-size:11.5px; color:#166534;'><b>Segel Manifest SHA-256:</b> Diperbarui otomatis untuk {$syncedManifestRows} resi.</div>"
                        : "");

                $updateLine = $qUpdated > 0
                    ? "<p style='margin:4px 0;'><b>Antrean Pending diperbarui (SLA/Ekspedisi):</b> {$qUpdated} paket</p>"
                    : "";

                $antreanLine = $qInserted > 0
                    ? "<p style='margin:4px 0;'><b>Antrean baru dijadwalkan (Instan):</b> {$qInserted} paket</p>"
                    : "<p style='margin:4px 0; color:#64748b;'><b>Antrean kurir perorangan (Instan):</b> 0 paket (Serah terima massal)</p>";

                $regularBanner = (!$isInstantBatch && $insertedManifestCount > 0)
                    ? "<div style='margin-top:8px; padding:6px 9px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; font-size:11.5px; color:#1e40af;'><b>Catatan Alur:</b> Seluruh resi {$manifestHeaderEksp} tercatat di Laporan Hasil Ekspedisi untuk serah terima massal kurir (tidak dimasukkan ke antrean kurir perorangan Instan).</div>"
                    : "";

                $swalHtml = "
                    <div style='text-align:left; font-size:13px; line-height:1.5;'>
                        <p style='margin:4px 0 8px;'><b>Kategori Ekspedisi &amp; SLA:</b><br>{$kategoriLabel}</p>
                        {$antreanLine}
                        {$updateLine}
                        <p style='margin:4px 0;'><b>Manifest resi baru tersimpan:</b> {$insertedManifestCount} resi</p>
                        <p style='margin:4px 0;'><b>Data duplikat dilewati:</b> {$qSkipped} baris</p>
                        <p style='margin:4px 0;'><b>Tanggal operasional:</b> {$tanggalLabel}</p>
                        {$hashSnippet}
                        {$regularBanner}
                    </div>
                ";

                $_SESSION['swal'] = [
                    'icon'  => $swalIcon,
                    'title' => $swalTitle,
                    'text'  => $swalHtml,
                ];

                $msgPlain = "Berhasil memproses import: {$qInserted} paket baru, {$qUpdated} diperbarui, {$insertedManifestCount} resi disegel SHA-256.";
                if ($this->isAjax()) {
                    $this->json([
                        'success'           => true,
                        'message'           => $msgPlain,
                        'manifest_hash'     => $manifestHash,
                        'file_hash'         => $fileHash,
                        'redirect_date'     => $primaryDate,
                        'queue_inserted'    => $qInserted,
                        'queue_updated'     => $qUpdated,
                        'manifest_inserted' => $insertedManifestCount,
                    ]);
                    return;
                }
                $this->flash('success', $msgPlain);
            } else {
                $swalHtml = "
                    <div style='text-align:left; font-size:13px; line-height:1.5;'>
                        <p style='margin:4px 0;'><b>Tidak ada data baru maupun perubahan SLA yang diproses.</b></p>
                        <p style='margin:4px 0;'><b>Data identik / sudah terdata:</b> " . max($qSkipped, $skippedManifest) . " paket</p>
                        <p style='margin:4px 0;'><b>Tanggal operasional:</b> {$tanggalLabel}</p>
                        <p style='margin:8px 0 0; color:#475569;'>Seluruh resi pada file ini sudah terdata dengan kategori Ekspedisi &amp; SLA yang sama.</p>
                    </div>
                ";
                $_SESSION['swal'] = [
                    'icon'  => 'warning',
                    'title' => 'Data Sudah Terdaftar',
                    'text'  => $swalHtml,
                ];
                $msgDup = "Semua nomor resi sudah terdaftar dengan kategori yang sama. Tidak ada data baru yang masuk.";
                if ($this->isAjax()) {
                    $this->json([
                        'success'       => true,
                        'warning'       => true,
                        'message'       => $msgDup,
                        'redirect_date' => $primaryDate,
                    ]);
                    return;
                }
                $this->flash('warning', $msgDup);
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if (file_exists($filePath)) unlink($filePath);
            $msg = 'Gagal menyimpan data: ' . $e->getMessage();
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 500);
                return;
            }
            $this->flash('error', $msg);
        }

        $this->redirect('/hasil-ekspedisi?start_date=' . urlencode($primaryDate) . '&end_date=' . urlencode($primaryDate));
    }

    /**
     * Endpoint Verifikasi Integritas SHA-256 pada Manifest Resi
     */
    public function verify(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$this->validateCsrf()) {
            return;
        }

        $hasilId   = (int)$id;
        $auditorId = auth()['id'] ?? null;
        $report    = $this->integrityService->verifyHasilEkspedisi($hasilId, $auditorId, true);

        if (!$report['success']) {
            $this->json($report, 404);
            return;
        }

        $this->json([
            'success'      => true,
            'verification' => $report,
        ]);
    }

    public function show(string $id): void
    {
        $header = $this->model->find((int)$id);
        if (!$header) {
            $this->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
            return;
        }

        $details = $this->model->getDetails((int)$id, 500);
        $this->json([
            'success' => true,
            'header'  => $header,
            'details' => $details
        ]);
    }

    public function download(string $id): void
    {
        $data = $this->model->find((int)$id);
        if (!$data || !isset($data['path_file'])) {
            $this->flash('error', 'File tidak ditemukan.');
            $this->redirect('/hasil-ekspedisi');
            return;
        }

        $filePath = BASE_PATH . '/storage/' . $data['path_file'];
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $data['nama_file'] . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            $this->flash('error', 'File fisik tidak ditemukan di storage.');
            $this->redirect('/hasil-ekspedisi');
        }
    }

    public function delete(string $id): void
    {
        if (!$this->validateCsrf()) return;

        $data = $this->model->find((int)$id);
        if ($data) {
            $this->model->delete((int)$id);
            $this->flash('success', 'Laporan berhasil dihapus.');
        } else {
            $this->flash('error', 'Data tidak ditemukan.');
        }

        $this->redirect('/hasil-ekspedisi');
    }
    
    public function downloadTemplate(): void
    {
        $filename = "template_hasil_ekspedisi.csv";
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $output = fopen("php://output", "w");
        fputcsv($output, ['Resi', 'Nama Penerima', 'Alamat', 'Ekspedisi', 'Status Scan', 'Tanggal']);
        fputcsv($output, ['SPXID0123456789', 'Budi Santoso', 'Jl. Merdeka No. 10, Jakarta', 'SPX', 'Berhasil', date('Y-m-d H:i:s')]);
        fclose($output);
        exit;
    }

    private function parseFileNative(string $filePath, string $ext): array
    {
        $allResults = [];
        
        if ($ext === 'csv') {
            if (($handle = fopen($filePath, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if (count($data) == 1 && strpos($data[0], ';') !== false) {
                        $data = explode(';', $data[0]);
                    }
                    if (empty(trim(implode('', $data)))) continue;
                    
                    $resi = trim($data[0] ?? '');
                    if (empty($resi) || in_array(strtolower($resi), ['resi', 'nomor', 'waybill', 'paket'])) continue;
                    
                    $allResults[] = [
                        'resi'          => $resi,
                        'nama_penerima' => trim($data[1] ?? ''),
                        'alamat'        => trim($data[2] ?? ''),
                        'ekspedisi'     => trim($data[3] ?? 'CSV'),
                        'status_scan'   => trim($data[4] ?? 'Berhasil'),
                        'tanggal'       => trim($data[5] ?? date('Y-m-d H:i:s')),
                        'sheet_name'    => 'CSV'
                    ];
                }
                fclose($handle);
            }
        } elseif ($ext === 'xlsx' || $ext === 'xls') {
            if (!class_exists('ZipArchive')) {
                return ['success' => false, 'message' => 'PHP ZipArchive extension tidak aktif di server hosting Anda.'];
            }
            $zip = new ZipArchive();
            if ($zip->open($filePath) === true) {
                $sharedStrings = [];
                if (($index = $zip->locateName('xl/sharedStrings.xml')) !== false) {
                    $xml = simplexml_load_string($zip->getFromIndex($index));
                    if ($xml) {
                        foreach ($xml->si as $si) {
                            $value = '';
                            if (isset($si->t)) {
                                $value = (string)$si->t;
                            } elseif (isset($si->r)) {
                                foreach ($si->r as $r) {
                                    $value .= (string)$r->t;
                                }
                            }
                            $sharedStrings[] = trim($value);
                        }
                    }
                }

                $sheets = [];
                $workbookXmlPath = 'xl/workbook.xml';
                $workbookRelsPath = 'xl/_rels/workbook.xml.rels';
                
                $rels = [];
                if (($index = $zip->locateName($workbookRelsPath)) !== false) {
                    $xml = simplexml_load_string($zip->getFromIndex($index));
                    if ($xml) {
                        foreach ($xml->Relationship as $rel) {
                            $rels[(string)$rel['Id']] = (string)$rel['Target'];
                        }
                    }
                }

                if (($index = $zip->locateName($workbookXmlPath)) !== false) {
                    $xml = simplexml_load_string($zip->getFromIndex($index));
                    if ($xml) {
                        foreach ($xml->sheets->sheet as $sheet) {
                            $rId = (string)($sheet->attributes('r', true)->id ?? $sheet['id'] ?? $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id ?? '');
                            $target = $rels[$rId] ?? '';
                            if ($target) {
                                $sheets[$target] = (string)$sheet['name'];
                            }
                        }
                    }
                }
                
                if (empty($sheets)) {
                    for ($i = 1; $i <= 10; $i++) {
                        $sheets["worksheets/sheet{$i}.xml"] = "Sheet{$i}";
                    }
                }

                foreach ($sheets as $target => $sheetName) {
                    $targetPath = 'xl/' . ltrim($target, '/');
                    if (($index = $zip->locateName($targetPath)) !== false) {
                        $xml = simplexml_load_string($zip->getFromIndex($index));
                        if ($xml && isset($xml->sheetData->row)) {
                            foreach ($xml->sheetData->row as $row) {
                                $rowData = [];
                                $colIndex = 0;
                                foreach ($row->c as $c) {
                                    $cellRef = (string)$c['r'];
                                    if ($cellRef) {
                                        $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                                        $colIdx = 0;
                                        for ($i = 0; $i < strlen($colLetters); $i++) {
                                            $colIdx = $colIdx * 26 + (ord(strtoupper($colLetters[$i])) - 64);
                                        }
                                        $colIndex = $colIdx - 1;
                                    }
                                    
                                    $val = (string)$c->v;
                                    if (isset($c['t']) && (string)$c['t'] === 's') {
                                        $val = $sharedStrings[(int)$val] ?? $val;
                                    }
                                    
                                    $rowData[$colIndex] = $val;
                                    $colIndex++;
                                }
                                
                                $maxCol = empty($rowData) ? -1 : max(array_keys($rowData));
                                $filledRow = [];
                                for ($i = 0; $i <= $maxCol; $i++) {
                                    $filledRow[] = $rowData[$i] ?? '';
                                }

                                if (empty(trim(implode('', $filledRow)))) continue;

                                $firstCell = strtolower(trim($filledRow[0] ?? ''));
                                if (in_array($firstCell, ['resi', 'paket', 'waybill', 'nomor', 'no. paket', 'no pesanan'])) {
                                    continue;
                                }

                                $resi = trim($filledRow[0] ?? '');
                                if (empty($resi)) continue;
                                if (substr($resi, -2) === '.0') $resi = substr($resi, 0, -2);

                                $allResults[] = [
                                    'resi'          => $resi,
                                    'nama_penerima' => trim($filledRow[1] ?? ''),
                                    'alamat'        => trim($filledRow[2] ?? ''),
                                    'ekspedisi'     => trim($filledRow[3] ?? $sheetName),
                                    'status_scan'   => trim($filledRow[4] ?? 'Berhasil'),
                                    'tanggal'       => trim($filledRow[5] ?? date('Y-m-d H:i:s')),
                                    'sheet_name'    => $sheetName
                                ];
                            }
                        }
                    }
                }
                $zip->close();
            } else {
                return ['success' => false, 'message' => 'Gagal membaca isi file Excel. Format mungkin tidak didukung.'];
            }
        } else {
             return ['success' => false, 'message' => 'Format file tidak didukung. Gunakan .xlsx atau .csv'];
        }

        if (empty($allResults)) {
            return ['success' => false, 'message' => 'Tidak ada data valid yang ditemukan di file.'];
        }

        return ['success' => true, 'count' => count($allResults), 'items' => $allResults];
    }
}

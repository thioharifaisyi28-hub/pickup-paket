<?php
/**
 * PaketController.php (Terintegrasi Priority Scheduling)
 */

require_once BASE_PATH . '/app/Services/PaketService.php';
require_once BASE_PATH . '/app/Models/Paket.php';

class PaketController extends Controller
{
    private PaketService $paketService;
    private Database $db;

    public function __construct()
    {
        $this->paketService = new PaketService();
        $this->db = Database::getInstance();
    }

    /**
     * List all packages (Default: Priority Scheduling Order, Optional: ?sort=fifo)
     */
    public function index(): void
    {
        $userId = 0;
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        
        if (!isset($_GET['tanggal'])) {
            $_GET['tanggal'] = $tanggal;
        }
        
        $result = $this->paketService->getAllPackages($_GET, $userId);

        $data = [
            'title'           => 'Manajemen Paket (Priority Queue)',
            'pakets'          => $result['items'],
            'stats'           => $this->paketService->getStats($userId, $tanggal),
            'masterEkspedisi' => $this->paketService->getMasterEkspedisi(),
            'search'          => $_GET['search'] ?? '',
            'status'          => $_GET['status'] ?? '',
            'tanggal'         => $tanggal,
            'sort'            => $result['sort'] ?? 'priority',
            'page'            => $result['page'],
            'totalPages'      => $result['totalPages'],
            'total'           => $result['total']
        ];

        if ($this->isAjax()) {
            $this->partial('paket.table', $data);
            return;
        }

        $this->view('paket.index', $data);
    }

    /**
     * Validasi URL redirect internal agar tetap aman (mencegah Open Redirect).
     */
    private function resolveSafeReturnUrl(?string $url, string $default): string
    {
        $clean = trim((string)$url);
        if ($clean !== '' && str_starts_with($clean, '/') && !str_starts_with($clean, '//')) {
            return $clean;
        }
        return $default;
    }

    /**
     * Endpoint Recalculate Priority Score Harian secara Real-Time
     */
    public function recalculatePriority(): void
    {
        if (!$this->validateCsrf()) return;

        $tanggal   = trim($_POST['tanggal'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate   = trim($_POST['end_date'] ?? '');

        $datesToRecalc = [];
        if ($tanggal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $datesToRecalc[] = $tanggal;
        } elseif ($startDate !== '' || $endDate !== '') {
            if ($startDate === $endDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                $datesToRecalc[] = $startDate;
            } else {
                $conds  = ["is_deleted = 0"];
                $params = [];
                if ($startDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                    $conds[]  = "tanggal >= ?";
                    $params[] = $startDate;
                }
                if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $conds[]  = "tanggal <= ?";
                    $params[] = $endDate;
                }
                $rows = $this->db->fetchAll("SELECT DISTINCT tanggal FROM paket WHERE " . implode(' AND ', $conds), $params);
                foreach ($rows as $r) {
                    if (!empty($r['tanggal'])) {
                        $datesToRecalc[] = $r['tanggal'];
                    }
                }
            }
        }

        if (empty($datesToRecalc)) {
            $datesToRecalc[] = date('Y-m-d');
        }

        $totalUpdated = 0;
        $totalMs      = 0.0;
        foreach (array_unique($datesToRecalc) as $tgl) {
            $st = $this->paketService->recalculateQueue($tgl, false);
            $totalUpdated += (int)($st['updated'] ?? 0);
            $totalMs      += (float)($st['duration_ms'] ?? 0);
        }

        $totalMsRounded = round($totalMs, 2);
        $primaryDate    = $datesToRecalc[0];
        $msg = "Skor prioritas berhasil diperbarui untuk {$totalUpdated} paket ({$totalMsRounded} ms).";

        if ($this->isAjax()) {
            $this->json([
                'success'     => true,
                'message'     => $msg,
                'updated'     => $totalUpdated,
                'tanggal'     => $primaryDate,
                'duration_ms' => $totalMsRounded,
            ]);
            return;
        }

        $this->flash('success', $msg);
        $targetRedirect = $this->resolveSafeReturnUrl($_POST['return_url'] ?? null, '/paket?tanggal=' . urlencode($primaryDate));
        $this->redirect($targetRedirect);
    }

    /**
     * Create form
     */
    public function create(): void
    {
        $this->view('paket.create', [
            'title'           => 'Tambah Paket Baru',
            'masterEkspedisi' => $this->paketService->getMasterEkspedisi(),
        ]);
    }

    /**
     * Store new package
     */
    public function store(): void
    {
        if (!$this->validateCsrf()) return;

        $no_pesanan   = trim($_POST['no_pesanan'] ?? '');
        $nama_pemesan = trim($_POST['nama_pemesan'] ?? '');
        $tanggal      = trim($_POST['tanggal'] ?? '');
        $sesi         = trim($_POST['sesi'] ?? '');
        $returnUrl    = $this->resolveSafeReturnUrl($_POST['return_url'] ?? null, '');
        $fromHasil    = !empty($_POST['from_hasil_ekspedisi']) || str_starts_with($returnUrl, '/hasil-ekspedisi');

        if (empty($no_pesanan) || empty($nama_pemesan) || empty($tanggal) || empty($sesi)) {
            $this->flash('error', 'Field bertanda bintang (*) wajib diisi.');
            setOldInput($_POST);
            $this->redirect($fromHasil ? ($returnUrl ?: '/hasil-ekspedisi') : '/paket');
            return;
        }

        $result = $this->paketService->createPackage($_POST, auth()['id']);

        if ($result) {
            $this->flash('success', 'Paket berhasil ditambahkan & dijadwalkan ke antrean prioritas.');
            if ($fromHasil) {
                $this->redirect('/hasil-ekspedisi?start_date=' . urlencode($tanggal) . '&end_date=' . urlencode($tanggal));
            } else {
                $this->redirect('/paket?tanggal=' . urlencode($tanggal));
            }
        } else {
            $this->flash('error', 'Gagal menambahkan paket (No Pesanan mungkin duplikat).');
            setOldInput($_POST);
            $this->redirect($fromHasil ? ($returnUrl ?: '/hasil-ekspedisi') : '/paket');
        }
    }

    /**
     * Edit form
     */
    public function edit(string $id): void
    {
        $paket = $this->paketService->getPackageById((int)$id);
        
        if (!$paket) {
            App::abort(404, 'Paket tidak ditemukan.');
        }

        $this->view('paket.edit', [
            'title'           => 'Edit Paket ' . e($paket['no_pesanan']),
            'paket'           => $paket,
            'masterEkspedisi' => $this->paketService->getMasterEkspedisi(),
        ]);
    }

    /**
     * Update package
     */
    public function update(string $id): void
    {
        if (!$this->validateCsrf()) return;

        $paket = $this->paketService->getPackageById((int)$id);
        if (!$paket) {
            $this->flash('error', 'Paket tidak ditemukan.');
            $this->redirect('/paket');
            return;
        }

        $result = $this->paketService->updatePackage((int)$id, $_POST);

        if ($result) {
            $this->flash('success', 'Paket & skor prioritas berhasil diperbarui.');
            $this->redirect('/paket?tanggal=' . urlencode($_POST['tanggal'] ?? $paket['tanggal']));
        } else {
            $this->flash('error', 'Gagal update paket.');
            $this->redirect("/paket/{$id}/edit");
        }
    }
    
    /**
     * Update Status Only (Ambil, Batal, Retur, Reset)
     */
    public function updateStatus(string $id): void
    {
        if (!$this->validateCsrf()) return;

        $paket = $this->paketService->getPackageById((int)$id);
        if (!$paket) {
            $this->flash('error', 'Paket tidak ditemukan.');
            $this->redirect('/paket');
            return;
        }

        $action = $_POST['action'] ?? '';
        $newStatus = 'Pending';
        
        switch ($action) {
            case 'ambil': $newStatus = 'Sudah'; break;
            case 'batal': $newStatus = 'Batal'; break;
            case 'retur': $newStatus = 'Retur'; break;
            case 'reset': $newStatus = 'Pending'; break;
        }

        if ($this->paketService->updateStatus((int)$id, $newStatus, auth()['id'])) {
            $this->flash('success', 'Status paket berhasil diubah.');
        } else {
            $this->flash('error', 'Gagal merubah status.');
        }
        
        $returnUrl = $_POST['return_url'] ?? '/paket';
        $this->redirect($returnUrl);
    }

    /**
     * Show details (AJAX / Modal usage)
     */
    public function show(string $id): void
    {
        $paket = $this->paketService->getPackageById((int)$id);
        if (!$paket) {
            $this->json(['success' => false, 'message' => 'Not found'], 404);
            return;
        }
        $this->json(['success' => true, 'data' => $paket]);
    }

    /**
     * Download CSV Template (Mendukung kolom Priority Scheduling)
     */
    public function downloadTemplate(): void
    {
        $filename = "template_import_paket_prioritas.csv";
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $output = fopen("php://output", "w");
        fputcsv($output, ['no_pesanan', 'nama_pemesan', 'kode', 'tanggal', 'sesi', 'nama_ekspedisi', 'layanan_ekspedisi', 'cutoff_time', 'jumlah_item']);
        fputcsv($output, ['261030SPX001', 'Budi Santoso', 'POLO-A1', date('Y-m-d'), 'SESI 1', 'SPX', 'REGULER', '17:00', '2']);
        fputcsv($output, ['261030INS002', 'Siti Aminah', 'POLO-B2', date('Y-m-d'), 'SESI 1', 'INSTANT', 'INSTANT', '16:00', '1']);
        fclose($output);
        exit;
    }

    /**
     * Import from CSV / Excel
     */
    public function import(): void
    {
        if (!$this->validateCsrf()) return;

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $msg = 'Silakan pilih file yang valid.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            $this->flash('error', $msg);
            $this->redirect('/paket');
            return;
        }

        $file = $_FILES['import_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            $msg = 'Format file tidak didukung. Silakan gunakan .xlsx, .xls, atau .csv';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            $this->flash('error', $msg);
            $this->redirect('/paket');
            return;
        }

        $importMode      = strtolower(trim((string)($_POST['import_mode'] ?? 'auto')));
        $namaEkspedisi   = trim((string)($_POST['nama_ekspedisi'] ?? ''));
        $layananEkspedisi = trim((string)($_POST['layanan_ekspedisi'] ?? ''));
        $cutoffTime      = trim((string)($_POST['cutoff_time'] ?? ''));
        $defaultTanggal  = trim((string)($_POST['default_tanggal'] ?? ''));

        if ($importMode === 'manual' && $namaEkspedisi === '') {
            $msg = 'Silakan pilih Ekspedisi Penjemput / Kategori Paket terlebih dahulu sebelum memulai import.';
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 400);
                return;
            }
            $this->flash('error', $msg);
            $this->redirect('/paket');
            return;
        }

        $importOptions = [
            'import_mode'       => $importMode,
            'nama_ekspedisi'    => $namaEkspedisi,
            'layanan_ekspedisi' => $layananEkspedisi,
            'cutoff_time'       => $cutoffTime,
            'default_tanggal'   => $defaultTanggal,
        ];

        $result = $this->paketService->importFromExcel($file, auth()['id'], $importOptions);

        if ($result['success']) {
            $jumlahBerhasil   = (int)($result['inserted'] ?? 0);
            $jumlahDiperbarui = (int)($result['updated'] ?? 0);
            $jumlahDuplikat   = (int)($result['skipped'] ?? 0);
            $totalDiproses    = $jumlahBerhasil + $jumlahDiperbarui;
            $tanggalImport    = e((string)($result['tanggal'] ?? ''));

            $profile = $result['applied_profile'] ?? ['mode' => 'auto'];
            if (($profile['mode'] ?? 'auto') === 'manual') {
                $pEksp    = e((string)($profile['nama_ekspedisi'] ?? ''));
                $pLayanan = e((string)($profile['layanan_ekspedisi'] ?? ''));
                $pCutoff  = e((string)($profile['cutoff_time'] ?? ''));
                $kategoriLabel = "<span style='display:inline-block; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 8px; border-radius:6px; font-weight:700; font-size:12px;'>{$pEksp} &bull; {$pLayanan} (Cut-off {$pCutoff} WIB)</span>";
            } else {
                $kategoriLabel = "<span style='display:inline-block; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:2px 8px; border-radius:6px; font-weight:700; font-size:12px;'>Deteksi Otomatis Kolom / Resi</span>";
            }

            $barisUpdate = $jumlahDiperbarui > 0
                ? "<p style='margin:4px 0;'><b>Data Pending diperbarui (SLA/Ekspedisi):</b> {$jumlahDiperbarui} paket</p>"
                : "";
            
            if ($totalDiproses > 0 && $jumlahDuplikat == 0) {
                $icon = 'success';
                $title = ($jumlahBerhasil === 0 && $jumlahDiperbarui > 0)
                    ? 'Pembaruan Batch Ekspedisi & SLA Berhasil'
                    : 'Import & Kalkulasi Prioritas Berhasil';
                $message = "
                    <div style='text-align:left; font-size:13px; line-height:1.5;'>
                        <p style='margin:4px 0 8px;'><b>Kategori Ekspedisi &amp; SLA:</b><br>{$kategoriLabel}</p>
                        <p style='margin:4px 0;'><b>Jumlah data baru masuk:</b> {$jumlahBerhasil} paket</p>
                        {$barisUpdate}
                        <p style='margin:4px 0;'><b>Data duplikat dilewati:</b> {$jumlahDuplikat} paket</p>
                        <p style='margin:4px 0;'><b>Tanggal paket:</b> {$tanggalImport}</p>
                        <p style='margin:8px 0 0; color:#475569;'>Seluruh paket otomatis diurutkan menggunakan algoritma <b>Priority Scheduling</b>.</p>
                    </div>
                ";
            } elseif ($totalDiproses > 0 && $jumlahDuplikat > 0) {
                $icon = 'warning';
                $title = 'Import Excel Selesai';
                $message = "
                    <div style='text-align:left; font-size:13px; line-height:1.5;'>
                        <p style='margin:4px 0 8px;'><b>Kategori Ekspedisi &amp; SLA:</b><br>{$kategoriLabel}</p>
                        <p style='margin:4px 0;'><b>Jumlah data baru masuk:</b> {$jumlahBerhasil} paket</p>
                        {$barisUpdate}
                        <p style='margin:4px 0;'><b>Data duplikat dilewati:</b> {$jumlahDuplikat} paket</p>
                        <p style='margin:4px 0;'><b>Tanggal paket:</b> {$tanggalImport}</p>
                        <p style='margin:8px 0 0; color:#475569;'>Sebagian data dilewati karena sudah identik atau sudah selesai diproses.</p>
                    </div>
                ";
            } else {
                $icon = 'error';
                $title = 'Import Excel Ditolak';
                $message = "
                    <div style='text-align:left; font-size:13px; line-height:1.5;'>
                        <p style='margin:4px 0;'><b>Tidak ada data baru maupun perubahan SLA yang diproses.</b></p>
                        <p style='margin:4px 0;'><b>Data identik / sudah diproses:</b> {$jumlahDuplikat} paket</p>
                        <p style='margin:4px 0;'><b>Tanggal paket:</b> {$tanggalImport}</p>
                        <p style='margin:8px 0 0; color:#475569;'>Seluruh baris pada file ini sudah terdata dengan kategori Ekspedisi &amp; SLA yang sama (atau sudah berstatus selesai).</p>
                    </div>
                ";
            }

            $_SESSION['swal'] = [
                'icon'  => $icon,
                'title' => $title,
                'text'  => $message
            ];
            
            if ($this->isAjax()) {
                $redirectDate = !empty($result['raw_dates']) ? $result['raw_dates'][0] : null;
                $this->json(['success' => true, 'message' => $result['message'], 'redirect_date' => $redirectDate]);
                return;
            }
            $this->flash('success', $result['message']);
        } else {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => $result['message']], 500);
                return;
            }
            $this->flash('error', $result['message']);
        }
        
        $this->redirect('/paket');
    }

    /**
     * Export to Excel (via Native PHP XlsxWriter - dilengkapi Priority Score)
     */
    public function export(): void
    {
        $filters = $_GET;
        $filters['page']  = 1;
        $filters['limit'] = 999999;

        $startDate = trim((string)($_GET['start_date'] ?? ''));
        $endDate   = trim((string)($_GET['end_date'] ?? ''));
        $ekspedisi = strtoupper(trim((string)($_GET['ekspedisi'] ?? '')));

        if (empty($filters['tanggal']) && $startDate !== '' && $startDate === $endDate) {
            $filters['tanggal'] = $startDate;
        }
        
        $result = $this->paketService->getAllPackages($filters, 0);
        $items  = $result['items'];

        // Dukungan filter rentang tanggal & ekspedisi saat diekspor dari halaman Hasil Ekspedisi
        if ($startDate !== '' || $endDate !== '' || $ekspedisi !== '') {
            $items = array_values(array_filter($items, function ($row) use ($startDate, $endDate, $ekspedisi) {
                $rowTgl  = (string)($row['tanggal'] ?? '');
                $rowEksp = strtoupper(trim((string)($row['nama_ekspedisi'] ?? '')));
                if ($startDate !== '' && $rowTgl !== '' && $rowTgl < $startDate) {
                    return false;
                }
                if ($endDate !== '' && $rowTgl !== '' && $rowTgl > $endDate) {
                    return false;
                }
                if ($ekspedisi !== '') {
                    if ($ekspedisi === 'NON SPX' && $rowEksp === 'SPX') {
                        return false;
                    } elseif (($ekspedisi === 'INSTAN' || $ekspedisi === 'INSTANT') && !in_array($rowEksp, ['INSTAN', 'INSTANT'], true)) {
                        return false;
                    } elseif (!in_array($ekspedisi, ['NON SPX', 'INSTAN', 'INSTANT'], true) && $rowEksp !== $ekspedisi) {
                        return false;
                    }
                }
                return true;
            }));
        }

        $writer = new XlsxWriter();
        $writer->setColWidths([5, 10, 20, 30, 12, 12, 10, 14, 20, 10, 12, 14]);

        $sheetData = [];
        $cellStyles = [];

        // Title
        $sheetData[] = ["LAPORAN REKAPITULASI PICKUP PAKET (PRIORITY SCHEDULING)", "", "", "", "", "", "", "", "", "", "", ""];
        $cellStyles["A1"] = 4;
        $writer->addMergeCell("A1:L1");

        // Subtitle
        $generatedAt = date('d/m/Y - H:i');
        $sheetData[] = ["Generated at: " . $generatedAt, "", "", "", "", "", "", "", "", "", "", ""];
        $cellStyles["A2"] = 5;
        $writer->addMergeCell("A2:L2");

        // Spacer row 3
        $sheetData[] = ["", "", "", "", "", "", "", "", "", "", "", ""];

        // Headers row 4
        $headers = ["NO", "NO PAKET", "NO PESANAN", "NAMA PEMESAN", "EKSPEDISI", "LAYANAN", "CUT-OFF", "SKOR PRIORITAS", "TANGGAL MASUK", "SESI", "STATUS", "PETUGAS"];
        $sheetData[] = $headers;
        for ($c = 0; $c < 12; $c++) {
            $coord = XlsxWriter::colLetter($c) . "4";
            $cellStyles[$coord] = 3;
        }

        // Data rows starting from row 5
        $stats = ['Sudah' => 0, 'Batal' => 0, 'Retur' => 0, 'Pending' => 0];
        $startRow = 5;
        foreach ($items as $idx => $item) {
            $rNum = $startRow + $idx;
            $isEven = ($idx % 2 !== 0);
            
            $status = $item['status'] ?? 'Pending';
            if (isset($stats[$status])) {
                $stats[$status]++;
            } else {
                $stats[$status] = 1;
            }

            $rowVal = [
                $idx + 1,
                $item['no_paket'],
                $item['no_pesanan'],
                $item['nama_pemesan'],
                $item['nama_ekspedisi'] ?? 'SPX',
                $item['layanan_ekspedisi'] ?? 'REGULER',
                !empty($item['cutoff_time']) ? substr((string)$item['cutoff_time'], 0, 5) : '-',
                number_format((float)($item['priority_score'] ?? 0), 2),
                date('d/m/Y H:i', strtotime($item['tanggal_masuk'])),
                $item['sesi'],
                $status,
                $item['input_by'] ?? '-'
            ];
            $sheetData[] = $rowVal;

            for ($c = 0; $c < 12; $c++) {
                $coord = XlsxWriter::colLetter($c) . $rNum;
                if ($c === 10) { // Status column
                    if ($status === 'Sudah') {
                        $cellStyles[$coord] = $isEven ? 7 : 6;
                    } elseif ($status === 'Batal' || $status === 'Retur') {
                        $cellStyles[$coord] = $isEven ? 9 : 8;
                    } else {
                        $cellStyles[$coord] = $isEven ? 11 : 10;
                    }
                } else {
                    $cellStyles[$coord] = $isEven ? 2 : 1;
                }
            }
        }

        // Stats Footer
        $totalData = count($items);
        $totalSudah = $stats['Sudah'];
        $totalBatalRetur = ($stats['Batal'] ?? 0) + ($stats['Retur'] ?? 0);

        $footerStart = count($sheetData) + 2;

        // Spacer row
        $sheetData[] = ["", "", "", "", "", "", "", "", "", "", "", ""];

        // Total Data Paket
        $sheetData[] = ["TOTAL DATA PAKET", "", "", "", "", "", "", "", "", "", $totalData . " Data", ""];
        $writer->addMergeCell("A" . $footerStart . ":J" . $footerStart);
        for ($c = 0; $c < 10; $c++) {
            $cellStyles[XlsxWriter::colLetter($c) . $footerStart] = 12;
        }
        $cellStyles["K" . $footerStart] = 15;

        // Total Paket Diambil
        $sheetData[] = ["TOTAL PAKET DIAMBIL (SUDAH)", "", "", "", "", "", "", "", "", "", $totalSudah, ""];
        $writer->addMergeCell("A" . ($footerStart + 1) . ":J" . ($footerStart + 1));
        for ($c = 0; $c < 10; $c++) {
            $cellStyles[XlsxWriter::colLetter($c) . ($footerStart + 1)] = 12;
        }
        $cellStyles["K" . ($footerStart + 1)] = 13;

        // Total Paket Batal/Retur
        $sheetData[] = ["TOTAL PAKET BATAL / RETUR", "", "", "", "", "", "", "", "", "", $totalBatalRetur, ""];
        $writer->addMergeCell("A" . ($footerStart + 2) . ":J" . ($footerStart + 2));
        for ($c = 0; $c < 10; $c++) {
            $cellStyles[XlsxWriter::colLetter($c) . ($footerStart + 2)] = 12;
        }
        $cellStyles["K" . ($footerStart + 2)] = 14;

        $writer->writeSheet($sheetData);
        $writer->setCellStyles($cellStyles);

        $filename = "Laporan_Pickup_Paket_Prioritas_" . date('Ymd_Hi') . ".xlsx";
        $writer->download($filename);
    }

    /**
     * Bulk delete packages with status 'Sudah' (Admin Only)
     */
    public function deleteSudah(): void
    {
        if (!$this->validateCsrf()) return;

        $returnUrl   = $this->resolveSafeReturnUrl($_POST['return_url'] ?? null, '');
        $fallbackUrl = $returnUrl ?: '/paket';

        if (!isAdmin()) {
            $this->flash('error', 'Akses ditolak.');
            $this->redirect($fallbackUrl);
            return;
        }

        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($confirmPassword !== 'polosport') {
            $this->flash('error', 'Akses ditolak: Password konfirmasi salah atau tidak sah.');
            $this->redirect($fallbackUrl);
            return;
        }

        $tanggal   = trim($_POST['tanggal'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate   = trim($_POST['end_date'] ?? '');
        $scopeAll  = !empty($_POST['scope_all_dates']);

        $conds  = ["status = 'Sudah'", "is_deleted = 0"];
        $params = [];
        $labelPeriode = '';

        if ($tanggal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $conds[]      = "tanggal = ?";
            $params[]     = $tanggal;
            $labelPeriode = "tanggal {$tanggal}";
        } elseif ($startDate !== '' || $endDate !== '') {
            if ($startDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                $conds[]  = "tanggal >= ?";
                $params[] = $startDate;
            }
            if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                $conds[]  = "tanggal <= ?";
                $params[] = $endDate;
            }
            $labelPeriode = ($startDate === $endDate && $startDate !== '')
                ? "tanggal {$startDate}"
                : "rentang " . ($startDate ?: 'awal') . " s/d " . ($endDate ?: 'akhir');
        } elseif ($scopeAll) {
            $labelPeriode = "seluruh periode aktif";
        } else {
            $this->flash('error', 'Tanggal tidak valid.');
            $this->redirect($fallbackUrl);
            return;
        }

        $sql = "UPDATE paket SET is_deleted = 1, no_pesanan = CONCAT('[DEL]-', id, '-', no_pesanan) 
                WHERE " . implode(' AND ', $conds);
        
        $this->db->query($sql, $params);
        $affected = (int)($this->db->fetch("SELECT ROW_COUNT() as cnt")['cnt'] ?? 0);

        if ($affected > 0) {
            $this->flash('success', "Berhasil menghapus {$affected} paket berstatus Sudah pada {$labelPeriode}.");
        } else {
            $this->flash('info', "Tidak ada paket Sudah untuk dihapus pada {$labelPeriode}.");
        }

        if ($returnUrl !== '') {
            $this->redirect($returnUrl);
            return;
        }
        $this->redirect('/paket?tanggal=' . urlencode($tanggal ?: date('Y-m-d')));
    }

    /**
     * Bulk soft delete ALL packages on selected date/range (Admin Only)
     */
    public function deleteAll(): void
    {
        if (!$this->validateCsrf()) return;

        $returnUrl   = $this->resolveSafeReturnUrl($_POST['return_url'] ?? null, '');
        $fallbackUrl = $returnUrl ?: '/paket';

        if (!isAdmin()) {
            $this->flash('error', 'Akses ditolak.');
            $this->redirect($fallbackUrl);
            return;
        }

        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($confirmPassword !== 'polosport') {
            $this->flash('error', 'Akses ditolak: Password konfirmasi salah atau tidak sah.');
            $this->redirect($fallbackUrl);
            return;
        }

        $tanggal   = trim($_POST['tanggal'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate   = trim($_POST['end_date'] ?? '');
        $scopeAll  = !empty($_POST['scope_all_dates']);

        $conds  = ["is_deleted = 0"];
        $params = [];
        $labelPeriode = '';

        if ($tanggal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $conds[]      = "tanggal = ?";
            $params[]     = $tanggal;
            $labelPeriode = "tanggal {$tanggal}";
        } elseif ($startDate !== '' || $endDate !== '') {
            if ($startDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                $conds[]  = "tanggal >= ?";
                $params[] = $startDate;
            }
            if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                $conds[]  = "tanggal <= ?";
                $params[] = $endDate;
            }
            $labelPeriode = ($startDate === $endDate && $startDate !== '')
                ? "tanggal {$startDate}"
                : "rentang " . ($startDate ?: 'awal') . " s/d " . ($endDate ?: 'akhir');
        } elseif ($scopeAll) {
            $labelPeriode = "seluruh periode aktif";
        } else {
            $this->flash('error', 'Tanggal tidak valid.');
            $this->redirect($fallbackUrl);
            return;
        }

        $sql = "UPDATE paket SET is_deleted = 1, no_pesanan = CONCAT('[DEL]-', id, '-', no_pesanan) 
                WHERE " . implode(' AND ', $conds);
        
        $this->db->query($sql, $params);
        $affected = (int)($this->db->fetch("SELECT ROW_COUNT() as cnt")['cnt'] ?? 0);

        if ($affected > 0) {
            $this->flash('success', "Berhasil menghapus seluruh data paket pada {$labelPeriode} ({$affected} paket).");
        } else {
            $this->flash('info', "Tidak ada data paket aktif pada {$labelPeriode}.");
        }

        if ($returnUrl !== '') {
            $this->redirect($returnUrl);
            return;
        }
        $this->redirect('/paket?tanggal=' . urlencode($tanggal ?: date('Y-m-d')));
    }

    /**
     * Soft delete package (Admin Only)
     */
    public function delete(string $id): void
    {
        if (!$this->validateCsrf()) return;

        $result = $this->paketService->softDeletePackage((int)$id);

        if ($result) {
            $this->flash('success', 'Paket berhasil dihapus.');
        } else {
            $this->flash('error', 'Gagal menghapus paket.');
        }
        
        $returnUrl = $_POST['return_url'] ?? '/paket';
        $this->redirect($returnUrl);
    }

    /**
     * AJAX: Get all selectable IDs based on current filters
     */
    public function getSelectableIds(): void
    {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $userId = isAdmin() ? 0 : (auth()['id'] ?? 0);
        
        $result = (new Paket())->getSelectableIds($search, $status, $userId, $tanggal);
        
        $this->json([
            'success' => true,
            'ids'     => $result['ids'],
            'count'   => $result['count']
        ]);
    }
}

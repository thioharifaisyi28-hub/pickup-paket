<?php
/**
 * NumberingController.php - Numbering Paket Feature (Terintegrasi Priority Scheduling)
 */

class NumberingController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // =============================================
    //  INDEX - Main page (all roles)
    // =============================================
    public function index(): void
    {
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $this->view('numbering.index', [
            'title'   => 'Numbering Paket',
            'tanggal' => $tanggal
        ]);
    }

    // =============================================
    //  GET ALL PAKETS (AJAX) with real-time search
    // =============================================
    public function getData(): void
    {
        $search  = trim($_GET['search'] ?? '');
        $filter  = $_GET['filter'] ?? 'all';
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $page    = (int)($_GET['page'] ?? 1);
        $limit   = 10;
        $offset  = ($page - 1) * $limit;

        $where = "WHERE p.is_deleted = 0";
        $params = [];
        
        if ($tanggal) {
            $where .= " AND p.tanggal = :tanggal";
            $params['tanggal'] = $tanggal;
        }

        if ($search !== '') {
            $where .= " AND (
                p.nama_pemesan   LIKE :s1 OR
                p.no_pesanan     LIKE :s2 OR
                p.no_paket       LIKE :s3 OR
                p.kode           LIKE :s4 OR
                p.nama_ekspedisi LIKE :s5
            )";
            $params['s1'] = "%{$search}%";
            $params['s2'] = "%{$search}%";
            $params['s3'] = "%{$search}%";
            $params['s4'] = "%{$search}%";
            $params['s5'] = "%{$search}%";
        }

        if ($filter === 'selected') {
            $where .= " AND p.is_selected = 1";
        } elseif ($filter === 'not_selected') {
            $where .= " AND p.is_selected = 0";
        }

        $countSql = "SELECT COUNT(*) as total FROM paket p $where";
        $totalCount = $this->db->fetch($countSql, $params)['total'] ?? 0;
        $totalPages = ceil($totalCount / $limit);

        $selectedCountSql = "SELECT COUNT(*) as total FROM paket p WHERE p.is_selected = 1 AND p.is_deleted = 0";
        if ($tanggal) {
            $selectedCountSql .= " AND p.tanggal = :tanggal_badge";
            $totalSelected = $this->db->fetch($selectedCountSql, ['tanggal_badge' => $tanggal])['total'] ?? 0;
        } else {
            $totalSelected = $this->db->fetch($selectedCountSql)['total'] ?? 0;
        }

        // Paket yang belum dipilih diurutkan berdasarkan Priority Score tertinggi
        $sql = "
            SELECT p.id, p.no_paket, p.no_pesanan, p.nama_pemesan, p.tanggal_masuk,
                   p.tanggal, p.kode, p.sesi, p.jam, p.is_selected, p.nomor_urut,
                   p.nama_ekspedisi, p.layanan_ekspedisi, p.priority_score,
                   u.username as petugas
            FROM paket p
            LEFT JOIN users u ON p.user_id = u.id
            $where
            ORDER BY p.is_selected DESC, p.nomor_urut ASC, p.priority_score DESC, p.id ASC
            LIMIT $limit OFFSET $offset
        ";

        $rows = $this->db->fetchAll($sql, $params);
        $this->json([
            'success'        => true, 
            'data'           => $rows,
            'total_selected' => $totalSelected,
            'pagination'     => [
                'current_page'  => $page,
                'total_pages'   => $totalPages,
                'total_records' => $totalCount,
                'limit'         => $limit
            ]
        ]);
    }

    // =============================================
    //  TOGGLE SELECT (AJAX)
    // =============================================
    public function toggleSelect(string $id): void
    {
        if (!$this->validateCsrf()) return;

        $paketId = (int)$id;
        $row = $this->db->fetch("SELECT id, is_selected, tanggal FROM paket WHERE id = :id AND is_deleted = 0", ['id' => $paketId]);

        if (!$row) {
            $this->json(['success' => false, 'message' => 'Paket tidak ditemukan.'], 404);
            return;
        }

        if ($row['is_selected']) {
            $this->db->query(
                "UPDATE paket SET is_selected = 0, nomor_urut = NULL WHERE id = :id",
                ['id' => $paketId]
            );
            $paketTanggal = date('Y-m-d', strtotime($row['tanggal']));
            $this->resequence($paketTanggal);
            $this->json(['success' => true, 'is_selected' => false, 'message' => 'Paket berhasil dibatalkan pilihan.']);
        } else {
            $paketTanggal = date('Y-m-d', strtotime($row['tanggal']));
            $maxResult = $this->db->fetch("SELECT MAX(nomor_urut) as mx FROM paket WHERE is_selected = 1 AND is_deleted = 0 AND tanggal = :tanggal", ['tanggal' => $paketTanggal]);
            $nextNum = ((int)($maxResult['mx'] ?? 0)) + 1;
            $currentUserId = $this->user()['id'];

            $this->db->query(
                "UPDATE paket SET is_selected = 1, nomor_urut = :num, user_id = :uid WHERE id = :id",
                ['num' => $nextNum, 'uid' => $currentUserId, 'id' => $paketId]
            );
            $this->json(['success' => true, 'is_selected' => true, 'nomor_urut' => $nextNum, 'message' => 'Paket berhasil dipilih.']);
        }
    }

    // =============================================
    //  RESET ALL SELECTIONS (Admin only)
    // =============================================
    public function resetAll(): void
    {
        if (!$this->validateCsrf()) return;

        if (!isAdmin()) {
            $this->json(['success' => false, 'message' => 'Akses ditolak. Hanya Admin.'], 403);
            return;
        }

        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $this->db->query("UPDATE paket SET is_selected = 0, nomor_urut = NULL WHERE is_deleted = 0 AND tanggal = :tanggal", ['tanggal' => $tanggal]);
        $this->json(['success' => true, 'message' => 'Semua pilihan paket pada tanggal tersebut berhasil direset.']);
    }

    // =============================================
    //  DELETE SELECTED PAKETS (Admin only)
    // =============================================
    public function deleteSelected(): void
    {
        if (!$this->validateCsrf()) return;

        if (!isAdmin()) {
            $this->json(['success' => false, 'message' => 'Akses ditolak. Hanya Admin.'], 403);
            return;
        }

        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $this->db->query(
            "UPDATE paket SET is_deleted = 1, no_pesanan = CONCAT('[DEL]-', id, '-', no_pesanan)
             WHERE is_selected = 1 AND is_deleted = 0 AND tanggal = :tanggal",
            ['tanggal' => $tanggal]
        );

        $affected = $this->db->fetch("SELECT ROW_COUNT() as cnt")['cnt'] ?? 0;

        $this->json(['success' => true, 'message' => "Berhasil menghapus paket yang dipilih.", 'deleted' => $affected]);
    }

    private function resequence(string $tanggal): void
    {
        $rows = $this->db->fetchAll(
            "SELECT id FROM paket WHERE is_selected = 1 AND is_deleted = 0 AND tanggal = :tanggal ORDER BY nomor_urut ASC, id ASC",
            ['tanggal' => $tanggal]
        );

        foreach ($rows as $i => $row) {
            $this->db->query(
                "UPDATE paket SET nomor_urut = :num WHERE id = :id",
                ['num' => $i + 1, 'id' => $row['id']]
            );
        }
    }

    // =============================================
    //  EXPORT TO EXCEL (Custom Numbering)
    // =============================================
    public function export(): void
    {
        $search  = trim($_GET['search'] ?? '');
        $filter  = $_GET['filter'] ?? 'all';
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');

        $where = "WHERE p.is_deleted = 0";
        $params = [];
        
        if ($tanggal) {
            $where .= " AND p.tanggal = :tanggal";
            $params['tanggal'] = $tanggal;
        }

        if ($search !== '') {
            $where .= " AND (p.nama_pemesan LIKE :s1 OR p.no_pesanan LIKE :s2 OR p.no_paket LIKE :s3 OR p.kode LIKE :s4)";
            $params['s1'] = "%{$search}%";
            $params['s2'] = "%{$search}%";
            $params['s3'] = "%{$search}%";
            $params['s4'] = "%{$search}%";
        }

        if ($filter === 'selected') {
            $where .= " AND p.is_selected = 1";
        } elseif ($filter === 'not_selected') {
            $where .= " AND p.is_selected = 0";
        }

        $sql = "
            SELECT p.id, p.no_paket, p.no_pesanan, p.nama_pemesan, p.tanggal, p.kode, p.sesi, p.jam, 
                   p.is_selected, p.nomor_urut, p.priority_score, u.username as petugas
            FROM paket p
            LEFT JOIN users u ON p.user_id = u.id
            $where
            ORDER BY p.is_selected DESC, p.nomor_urut ASC, p.priority_score DESC, p.id ASC
        ";

        $items = $this->db->fetchAll($sql, $params);

        $writer = new XlsxWriter();
        $writer->setColWidths([5, 18, 20, 35, 15, 12, 12, 15, 18]);

        $sheetData = [];
        $cellStyles = [];

        $sheetData[] = ["LAPORAN NUMBERING & PENOMORAN PAKET", "", "", "", "", "", "", "", ""];
        $cellStyles["A1"] = 4;
        $writer->addMergeCell("A1:I1");

        $generatedAt = date('d/m/Y - H:i');
        $sheetData[] = ["Generated at: " . $generatedAt, "", "", "", "", "", "", "", ""];
        $cellStyles["A2"] = 5;
        $writer->addMergeCell("A2:I2");

        $sheetData[] = ["", "", "", "", "", "", "", "", ""];

        $headers = ["NO", "NO PAKET", "NO PESANAN", "NAMA PEMESAN", "TANGGAL", "KODE", "SESI", "PETUGAS", "STATUS PILIHAN"];
        $sheetData[] = $headers;
        for ($c = 0; $c < 9; $c++) {
            $coord = XlsxWriter::colLetter($c) . "4";
            $cellStyles[$coord] = 3;
        }

        $totalSelected = 0;
        $startRow = 5;
        foreach ($items as $idx => $item) {
            $rNum = $startRow + $idx;
            
            $is_selected = ($item['is_selected'] == 1);
            $status_text = $is_selected ? "TERPILIH" : "BELUM TERPILIH";
            
            if ($is_selected) {
                $totalSelected++;
            }

            $no_paket_display = ($is_selected && $item['nomor_urut']) 
                ? "(" . $item['nomor_urut'] . ") " . $item['no_paket'] 
                : $item['no_paket'];

            $rowVal = [
                $idx + 1,
                $no_paket_display,
                $item['no_pesanan'],
                $item['nama_pemesan'],
                date('d/m/Y', strtotime($item['tanggal'])),
                $item['kode'],
                $item['sesi'],
                $item['petugas'] ?? '-',
                $status_text
            ];
            $sheetData[] = $rowVal;

            $rowStyle = $is_selected ? 16 : 18;
            $hlStyle  = $is_selected ? 17 : 19;

            for ($c = 0; $c < 9; $c++) {
                $coord = XlsxWriter::colLetter($c) . $rNum;
                if ($c === 1 || $c === 8) {
                    $cellStyles[$coord] = $hlStyle;
                } else {
                    $cellStyles[$coord] = $rowStyle;
                }
            }
        }

        $footerStart = count($sheetData) + 2;
        $totalNotSelected = count($items) - $totalSelected;

        $sheetData[] = ["", "", "", "", "", "", "", "", ""];

        $sheetData[] = ["TOTAL PAKET TERPILIH", "", "", "", "", "", "", "", $totalSelected . " Paket"];
        $writer->addMergeCell("A" . $footerStart . ":H" . $footerStart);
        for ($c = 0; $c < 8; $c++) {
            $cellStyles[XlsxWriter::colLetter($c) . $footerStart] = 20;
        }
        $cellStyles["I" . $footerStart] = 22;

        $sheetData[] = ["TOTAL PAKET BELUM TERPILIH", "", "", "", "", "", "", "", $totalNotSelected . " Paket"];
        $writer->addMergeCell("A" . ($footerStart + 1) . ":H" . ($footerStart + 1));
        for ($c = 0; $c < 8; $c++) {
            $cellStyles[XlsxWriter::colLetter($c) . ($footerStart + 1)] = 21;
        }
        $cellStyles["I" . ($footerStart + 1)] = 23;

        $writer->writeSheet($sheetData);
        $writer->setCellStyles($cellStyles);

        $filename = "Laporan_Numbering_Paket_" . date('Ymd_Hi') . ".xlsx";
        $writer->download($filename);
    }
}

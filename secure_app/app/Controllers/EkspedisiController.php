<?php
/**
 * EkspedisiController.php - Controller untuk manajemen foto serah terima ekspedisi
 * & Verifikasi Integritas Data Kriptografis SHA-256
 */

require_once BASE_PATH . '/app/Models/FotoEkspedisi.php';
require_once BASE_PATH . '/app/Services/IntegritySha256Service.php';

class EkspedisiController extends Controller
{
    private FotoEkspedisi $model;
    private IntegritySha256Service $integrityService;
    private Database $db;

    public function __construct()
    {
        $this->model            = new FotoEkspedisi();
        $this->integrityService = new IntegritySha256Service();
        $this->db               = Database::getInstance();
    }

    /**
     * Halaman utama ekspedisi (List & Filter)
     */
    public function index(string $ekspedisi): void
    {
        $ekspedisi = strtoupper($ekspedisi);
        $allowed = ['SPX', 'LEX', 'JNE', 'JNT', 'ID', 'NINJA', 'SICEPAT'];
        
        if (!in_array($ekspedisi, $allowed, true)) {
            App::abort(404, 'Ekspedisi tidak ditemukan.');
        }

        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        if (empty($tanggal)) {
            $tanggal = date('Y-m-d');
        }

        $photos = $this->model->getAll([
            'ekspedisi' => $ekspedisi,
            'tanggal'   => $tanggal
        ]);

        $targetQty  = 0;
        $targetInfo = [];
        if (isAdmin()) {
            $targetInfo = $this->model->getTargetQtyInfo($ekspedisi, $tanggal);
            $targetQty  = (int)($targetInfo['total_qty'] ?? 0);
        }

        $this->view('ekspedisi.index', [
            'title'      => "Ekspedisi $ekspedisi",
            'ekspedisi'  => $ekspedisi,
            'photos'     => $photos,
            'targetQty'  => $targetQty,
            'targetInfo' => $targetInfo,
            'tanggal'    => $tanggal
        ]);
    }

    /**
     * Halaman ambil foto serah terima (Khusus Petugas / Staff)
     */
    public function capture(string $ekspedisi): void
    {
        if (isAdmin()) {
            $this->flash('warning', 'Akun Administrator dikhususkan untuk memantau & audit data logistik. Fitur ambil foto/dokumentasi fisik dilakukan oleh akun Petugas/Staff.');
            $this->redirect(url('/ekspedisi/' . strtolower($ekspedisi)));
            return;
        }

        $ekspedisi = strtoupper($ekspedisi);
        $today = date('Y-m-d');
        $targetInfo = $this->model->getTargetQtyInfo($ekspedisi, $today);

        $this->view('ekspedisi.capture', [
            'title'      => "Ambil Foto $ekspedisi",
            'ekspedisi'  => $ekspedisi,
            'targetInfo' => $targetInfo
        ]);
    }

    /**
     * Simpan Target Total QTY per Ekspedisi berdasarkan Hari / Tanggal (KHUSUS ADMIN)
     */
    public function updateQty(): void
    {
        if (!isAdmin()) {
            $this->json([
                'success' => false, 
                'message' => 'Akses ditolak. Fitur ini hanya khusus untuk Admin.'
            ], 403);
            return;
        }

        if (!$this->validateCsrf()) return;

        $ekspedisi = strtoupper(trim($_POST['ekspedisi'] ?? ''));
        $allowed = ['SPX', 'LEX', 'JNE', 'JNT', 'ID', 'NINJA', 'SICEPAT'];

        if (!in_array($ekspedisi, $allowed, true)) {
            $this->json(['success' => false, 'message' => 'Ekspedisi tidak valid.'], 400);
            return;
        }

        $tanggal = trim($_POST['tanggal'] ?? date('Y-m-d'));
        if (empty($tanggal) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $tanggal = date('Y-m-d');
        }

        $qty = max(0, (int)($_POST['total_qty'] ?? 0));

        $saved = $this->model->setTargetQty($ekspedisi, $qty, $tanggal);

        if ($saved) {
            $tglFmt = date('d M Y', strtotime($tanggal));
            $this->json([
                'success'   => true,
                'message'   => "Target Total QTY untuk $ekspedisi pada $tglFmt berhasil disimpan ($qty paket).",
                'total_qty' => $qty,
                'tanggal'   => $tanggal
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Gagal menyimpan target QTY ke database.'], 500);
        }
    }

    /**
     * Simpan foto serah terima, validasi QTY, & terbitkan Segel Kriptografi SHA-256 (POLO-SHA256-V1)
     */
    public function store(): void
    {
        if (isAdmin()) {
            $this->json([
                'success' => false,
                'message' => 'Akses dibatasi: Akun Administrator dikhususkan untuk memantau data. Dokumentasi fisik dan pengambilan foto serah terima dilakukan oleh akun Petugas/Staff.'
            ], 403);
            return;
        }

        if (!$this->validateCsrf()) return;

        $ekspedisi    = strtoupper($_POST['ekspedisi'] ?? '');
        $nama_petugas = auth()['username'] ?? 'Unknown';
        $user_id      = auth()['id'] ?? null;
        $nama_kurir   = trim($_POST['nama_kurir'] ?? '-');
        $no_polisi    = strtoupper(trim($_POST['no_polisi_kurir'] ?? '-'));

        if ($nama_kurir === '') $nama_kurir = '-';
        if ($no_polisi === '')  $no_polisi  = '-';

        $today = date('Y-m-d');

        // 1. Validasi Input Quantity dari Staff
        $staffQtyInput = $_POST['qty'] ?? null;
        if ($staffQtyInput === null || $staffQtyInput === '') {
            $this->json([
                'success' => false, 
                'message' => 'Mohon masukkan Quantity (QTY) fisik paket terlebih dahulu.'
            ], 400);
            return;
        }

        $staffQty = (int)$staffQtyInput;

        // 2. Ambil acuan Target QTY dari Manifest Hasil Ekspedisi (otomatis) / fallback target Admin
        $targetInfo     = $this->model->getTargetQtyInfo($ekspedisi, $today);
        $targetAdminQty = (int)($targetInfo['total_qty'] ?? 0);

        if ($targetAdminQty <= 0) {
            $this->json([
                'success' => false,
                'status'  => 'no_manifest',
                'message' => "Data serah terima belum dapat diproses. Belum ada file Excel manifest {$ekspedisi} yang diimport di Hasil Ekspedisi untuk tanggal hari ini (" . date('d M Y', strtotime($today)) . "). Silakan hubungi Admin untuk import file manifest terlebih dahulu."
            ], 422);
            return;
        }

        // 3. Pencocokan QTY Staff vs Target Manifest Admin
        if ($staffQty !== $targetAdminQty) {
            $diff = abs($targetAdminQty - $staffQty);
            $ket  = ($staffQty < $targetAdminQty) ? "kurang {$diff}" : "lebih {$diff}";
            $this->json([
                'success' => false,
                'status'  => 'mismatch',
                'message' => "Data paket tidak bisa berhasil disimpan. Paket {$ket} (Fisik diinput: {$staffQty} vs Manifest Excel: {$targetAdminQty}). Mohon dicek kembali dan menghubungi Mba Putri. Terima kasih."
            ], 422);
            return;
        }

        // 4. Simpan berkas foto ke public_html/storage
        $relativeDir = "storage/hasil_ekspedisi/" . date('Y') . "/" . date('m');
        $storageDir  = BASE_PATH . "/../public_html/" . $relativeDir;

        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0777, true);
        }

        $maxSize = 5 * 1024 * 1024;
        $imageData = null;
        $isRawData = false;
        $detectedType = 'webp';

        if (isset($_FILES['foto_file']) && $_FILES['foto_file']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['foto_file']['size'] > $maxSize) {
                $this->json(['success' => false, 'message' => 'Ukuran file terlalu besar (Maks 5MB).'], 400);
                return;
            }
            $imageData = $_FILES['foto_file']['tmp_name'];
            $isRawData = false;
            $detectedType = strtolower(pathinfo($_FILES['foto_file']['name'], PATHINFO_EXTENSION));
        } else {
            $photoData = $_POST['photo'] ?? '';
            if (empty($photoData)) {
                $this->json(['success' => false, 'message' => 'Tidak ada data foto.'], 400);
                return;
            }
            if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $matches)) {
                $detectedType = strtolower($matches[1]);
                $photoData = substr($photoData, strpos($photoData, ',') + 1);
                $imageData = base64_decode($photoData);
                $isRawData = true;
            }
        }

        if (!$imageData) {
            $this->json(['success' => false, 'message' => 'Gagal memproses data gambar.'], 400);
            return;
        }

        $extension = function_exists('imagecreatefromstring') ? 'webp' : $detectedType;
        
        $petugasBersih = preg_replace('/[^A-Za-z0-9]/', '_', $nama_petugas);
        $petugasBersih = preg_replace('/_+/', '_', $petugasBersih);
        $petugasBersih = trim($petugasBersih, '_');
        
        $handoverTimestamp = date('Y-m-d H:i:s');
        $waktuOnly         = date('H:i:s', strtotime($handoverTimestamp));
        $filename          = "{$ekspedisi}_{$petugasBersih}_" . date('Ymd_His', strtotime($handoverTimestamp)) . ".{$extension}";
        $targetPath        = $storageDir . '/' . $filename;

        $processResult = $this->processImage($imageData, $targetPath, $isRawData, $ekspedisi, $nama_petugas);

        if ($processResult['success']) {
            $dbPath = $relativeDir . '/' . $filename;

            // Cek manifest resi hari ini untuk diikat ke dalam Canonical Payload (jika sudah diunggah)
            $manifestRow = $this->db->fetch(
                "SELECT id, manifest_hash FROM hasil_ekspedisi
                 WHERE ekspedisi = :eksp AND DATE(tanggal_upload) = :tgl AND deleted_at IS NULL
                 ORDER BY id DESC LIMIT 1",
                ['eksp' => $ekspedisi, 'tgl' => $today]
            );
            $linkedManifestHash = (!empty($manifestRow['manifest_hash'])) ? (string)$manifestRow['manifest_hash'] : 'NONE';

            // 5. Pembentukan Segel Kriptografi SHA-256 (POLO-SHA256-V1)
            $seal = $this->integrityService->createHandoverSeal([
                'nama_ekspedisi'  => $ekspedisi,
                'user_id'         => $user_id ?? 0,
                'nama_petugas'    => $nama_petugas,
                'nama_kurir'      => $nama_kurir,
                'no_polisi_kurir' => $no_polisi,
                'qty_admin'       => $targetAdminQty,
                'qty_staff'       => $staffQty,
                'timestamp'       => $handoverTimestamp,
                'manifest_hash'   => $linkedManifestHash,
            ], $targetPath);

            $savedId = $this->model->add([
                'nama_petugas'      => $nama_petugas,
                'nama_kurir'        => $nama_kurir,
                'no_polisi_kurir'   => $no_polisi,
                'user_id'           => $user_id,
                'nama_ekspedisi'    => $ekspedisi,
                'qty_admin'         => $targetAdminQty,
                'qty_staff'         => $staffQty,
                'foto'              => $dbPath,
                'photo_hash'        => $seal['photo_hash'],
                'waktu'             => $waktuOnly,
                'hari'              => $this->getIndonesianDay(date('l', strtotime($handoverTimestamp))),
                'hash_blockchain'   => $seal['verification_hash'],
                'payload_string'    => $seal['payload_string'],
                'status_verifikasi' => 'VALID',
                'created_at'        => $seal['timestamp'],
            ]);

            if ($savedId) {
                // Tautkan manifest hari ini ke bukti foto fisik jika ada
                if (!empty($manifestRow['id'])) {
                    $this->db->query(
                        "UPDATE hasil_ekspedisi SET foto_ekspedisi_id = :fid WHERE id = :mid AND foto_ekspedisi_id IS NULL",
                        ['fid' => (int)$savedId, 'mid' => (int)$manifestRow['id']]
                    );
                }

                $this->json([
                    'success'           => true, 
                    'status'            => 'match',
                    'message'           => 'Berhasil! Bukti serah terima tersimpan dan disegel dengan SHA-256.',
                    'verification_hash' => $seal['verification_hash'],
                    'photo_hash'        => $seal['photo_hash'],
                    'redirect'          => "/ekspedisi/" . strtolower($ekspedisi)
                ]);
            } else {
                @unlink($targetPath);
                $this->json(['success' => false, 'message' => 'Gagal menyimpan ke database.'], 500);
            }
        } else {
            $this->json(['success' => false, 'message' => $processResult['message']], 500);
        }
    }

    /**
     * Endpoint Audit & Verifikasi Integritas SHA-256 pada 1 Rekaman Serah Terima
     */
    public function verify(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$this->validateCsrf()) {
            return;
        }

        $fotoId    = (int)$id;
        $auditorId = auth()['id'] ?? null;
        $report    = $this->integrityService->verifyFotoEkspedisi($fotoId, $auditorId, true);

        if (!$report['success']) {
            $this->json($report, 404);
            return;
        }

        $record    = $this->model->find($fotoId);
        $auditLogs = $this->model->getAuditHistory($fotoId, 5);

        $this->json([
            'success'      => true,
            'verification' => $report,
            'record'       => [
                'id'                => $record['id'] ?? $fotoId,
                'nama_ekspedisi'    => $record['nama_ekspedisi'] ?? '-',
                'nama_petugas'      => $record['nama_petugas'] ?? '-',
                'nama_kurir'        => $record['nama_kurir'] ?? '-',
                'no_polisi_kurir'   => $record['no_polisi_kurir'] ?? '-',
                'qty_admin'         => $record['qty_admin'] ?? 0,
                'qty_staff'         => $record['qty_staff'] ?? 0,
                'created_at'        => $record['created_at'] ?? '-',
                'status_verifikasi' => $record['status_verifikasi'] ?? $report['status'],
                'verified_at'       => $record['verified_at'] ?? date('Y-m-d H:i:s'),
            ],
            'audit_logs'   => $auditLogs,
        ]);
    }

    /**
     * Endpoint Audit Massal (Batch Verification) seluruh bukti ekspedisi pada tanggal tertentu
     */
    public function verifyBatch(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$this->validateCsrf()) {
            return;
        }

        $ekspedisi = strtoupper(trim($_REQUEST['ekspedisi'] ?? ''));
        $tanggal   = trim($_REQUEST['tanggal'] ?? date('Y-m-d'));

        $filters = ['tanggal' => $tanggal];
        if ($ekspedisi !== '') {
            $filters['ekspedisi'] = $ekspedisi;
        }

        $records   = $this->model->getAll($filters);
        $auditorId = auth()['id'] ?? null;

        $summary = [
            'total'    => count($records),
            'valid'    => 0,
            'legacy'   => 0,
            'tampered' => 0,
            'items'    => [],
        ];

        foreach ($records as $rec) {
            $res = $this->integrityService->verifyFotoEkspedisi((int)$rec['id'], $auditorId, true);
            $st  = $res['status'] ?? 'TAMPERED';
            if ($st === 'VALID') {
                $summary['valid']++;
            } elseif ($st === 'LEGACY') {
                $summary['legacy']++;
            } else {
                $summary['tampered']++;
            }
            $summary['items'][] = [
                'id'            => (int)$rec['id'],
                'status'        => $st,
                'is_valid'      => $res['is_valid'] ?? false,
                'discrepancies' => $res['discrepancies'] ?? [],
            ];
        }

        $this->json([
            'success' => true,
            'summary' => $summary,
            'message' => sprintf(
                'Audit SHA-256 selesai untuk %d rekaman: %d VALID, %d LEGACY, %d TAMPERED.',
                $summary['total'],
                $summary['valid'],
                $summary['legacy'],
                $summary['tampered']
            ),
        ]);
    }

    /**
     * Soft Delete Foto
     */
    public function delete(string $id): void
    {
        if (!isAdmin()) {
            $this->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            return;
        }

        if ($this->model->softDelete((int)$id)) {
            $this->json(['success' => true, 'message' => 'Foto berhasil dihapus.']);
        } else {
            $this->json(['success' => false, 'message' => 'Gagal menghapus foto.']);
        }
    }

    /**
     * Process Image: Watermark & Compress to WebP
     */
    private function processImage($source, string $targetPath, bool $isRawData, $ekspedisi, $petugas): array
    {
        if (!function_exists('imagecreatefromstring')) {
            try {
                if ($isRawData) {
                    file_put_contents($targetPath, $source);
                } else {
                    copy($source, $targetPath);
                }
                return ['success' => true, 'message' => 'Foto disimpan tanpa watermark (GD Library tidak aktif).'];
            } catch (\Exception $e) {
                return ['success' => false, 'message' => 'Gagal menyimpan file: ' . $e->getMessage()];
            }
        }

        try {
            $imgString = $isRawData ? $source : file_get_contents($source);
            $image = @imagecreatefromstring($imgString);

            if (!$image) {
                return ['success' => false, 'message' => 'Format gambar tidak didukung.'];
            }

            $hari    = $this->getIndonesianDay(date('l'));
            $tanggal = date('d/m/Y');
            $waktu   = date('H:i:s');
            
            $this->addWatermark($image, $petugas, $hari, $tanggal, $waktu, $ekspedisi);

            if (imagewebp($image, $targetPath, 80)) {
                imagedestroy($image);
                return ['success' => true];
            }

            imagedestroy($image);
            return ['success' => false, 'message' => 'Gagal menyimpan file WebP.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function addWatermark($image, $petugas, $hari, $tanggal, $waktu, $ekspedisi): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        
        $fontPath = null;
        $possibleFonts = [
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/segoeui.ttf',
            'C:/Windows/Fonts/calibri.ttf',
        ];
        foreach ($possibleFonts as $f) {
            if (file_exists($f)) {
                $fontPath = $f;
                break;
            }
        }

        $fontBoldPath = null;
        $possibleBoldFonts = [
            'C:/Windows/Fonts/arialbd.ttf',
            'C:/Windows/Fonts/segoeuib.ttf',
            'C:/Windows/Fonts/calibrib.ttf',
            'C:/Windows/Fonts/arial.ttf',
        ];
        foreach ($possibleBoldFonts as $f) {
            if (file_exists($f)) {
                $fontBoldPath = $f;
                break;
            }
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $shadow = imagecolorallocate($image, 0, 0, 0);
        $scale = max($width / 1280, 0.5);
        
        if ($fontPath && function_exists('imagettftext')) {
            $badgeFontSize = max(16, round(22 * $scale));
            $badgeText = strtoupper($ekspedisi);
            $badgeBox = imagettfbbox($badgeFontSize, 0, $fontBoldPath ?? $fontPath, $badgeText);
            $badgeTextW = abs($badgeBox[2] - $badgeBox[0]);
            $badgeTextH = abs($badgeBox[7] - $badgeBox[1]);
            
            $badgePadX = round(16 * $scale);
            $badgePadY = round(10 * $scale);
            $badgeMargin = round(16 * $scale);
            
            $badgeBg = imagecolorallocatealpha($image, 59, 130, 246, 15);
            imagefilledrectangle(
                $image,
                $badgeMargin,
                $badgeMargin,
                $badgeMargin + $badgeTextW + ($badgePadX * 2),
                $badgeMargin + $badgeTextH + ($badgePadY * 2),
                $badgeBg
            );
            
            $badgeX = $badgeMargin + $badgePadX;
            $badgeY = $badgeMargin + $badgePadY + $badgeTextH;
            imagettftext($image, $badgeFontSize, 0, $badgeX + 1, $badgeY + 1, $shadow, $fontBoldPath ?? $fontPath, $badgeText);
            imagettftext($image, $badgeFontSize, 0, $badgeX, $badgeY, $white, $fontBoldPath ?? $fontPath, $badgeText);

            $barHeight = round(80 * $scale);
            for ($i = 0; $i < $barHeight; $i++) {
                $alpha = (int)(127 - (127 * ($i / $barHeight) * 0.85));
                $gradColor = imagecolorallocatealpha($image, 0, 0, 0, $alpha);
                imageline($image, 0, $height - $barHeight + $i, $width, $height - $barHeight + $i, $gradColor);
            }

            $padding = round(18 * $scale);
            
            $petugasFontSize = max(11, round(15 * $scale));
            $petugasText = "Petugas: " . $petugas;
            $petugasY = $height - $padding - round(22 * $scale);
            imagettftext($image, $petugasFontSize, 0, $padding + 1, $petugasY + 1, $shadow, $fontBoldPath ?? $fontPath, $petugasText);
            imagettftext($image, $petugasFontSize, 0, $padding, $petugasY, $white, $fontBoldPath ?? $fontPath, $petugasText);

            $dateFontSize = max(9, round(12 * $scale));
            $dateText = "$hari, $tanggal - $waktu WIB";
            $dateY = $height - $padding;
            $dateColor = imagecolorallocate($image, 200, 210, 220);
            imagettftext($image, $dateFontSize, 0, $padding + 1, $dateY + 1, $shadow, $fontPath, $dateText);
            imagettftext($image, $dateFontSize, 0, $padding, $dateY, $dateColor, $fontPath, $dateText);

            $companyFontSize = max(8, round(10 * $scale));
            $companyText = "PT. POLO SPORT INDONESIA";
            $companyBox = imagettfbbox($companyFontSize, 0, $fontPath, $companyText);
            $companyTextW = abs($companyBox[2] - $companyBox[0]);
            $companyX = $width - $companyTextW - $padding;
            $companyY = $height - $padding;
            $companyColor = imagecolorallocate($image, 180, 190, 200);
            imagettftext($image, $companyFontSize, 0, $companyX + 1, $companyY + 1, $shadow, $fontPath, $companyText);
            imagettftext($image, $companyFontSize, 0, $companyX, $companyY, $companyColor, $fontPath, $companyText);
        } else {
            $fontSize = 5;
            $charW = imagefontwidth($fontSize);
            $charH = imagefontheight($fontSize);
            $padding = 12;

            $badgeText = strtoupper($ekspedisi);
            $badgeW = $charW * strlen($badgeText);
            $badgeBg = imagecolorallocatealpha($image, 59, 130, 246, 20);
            imagefilledrectangle($image, $padding, $padding, $padding + $badgeW + 16, $padding + $charH + 10, $badgeBg);
            imagestring($image, $fontSize, $padding + 9, $padding + 6, $badgeText, $shadow);
            imagestring($image, $fontSize, $padding + 8, $padding + 5, $badgeText, $white);

            $barH = ($charH * 3) + 20;
            $barBg = imagecolorallocatealpha($image, 0, 0, 0, 40);
            imagefilledrectangle($image, 0, $height - $barH, $width, $height, $barBg);

            $line1 = "Petugas: $petugas";
            $y1 = $height - $barH + 6;
            imagestring($image, $fontSize, $padding + 1, $y1 + 1, $line1, $shadow);
            imagestring($image, $fontSize, $padding, $y1, $line1, $white);

            $line2 = "$hari, $tanggal - $waktu WIB";
            $y2 = $y1 + $charH + 4;
            $dateColor = imagecolorallocate($image, 200, 210, 220);
            imagestring($image, $fontSize, $padding + 1, $y2 + 1, $line2, $shadow);
            imagestring($image, $fontSize, $padding, $y2, $line2, $dateColor);

            $line3 = "PT. POLO SPORT INDONESIA";
            $companyW = $charW * strlen($line3);
            $companyX = $width - $companyW - $padding;
            $y3 = $y2 + $charH + 4;
            $companyColor = imagecolorallocate($image, 180, 190, 200);
            imagestring($image, $fontSize, $companyX + 1, $y3 + 1, $line3, $shadow);
            imagestring($image, $fontSize, $companyX, $y3, $line3, $companyColor);
        }
    }

    private function getIndonesianDay(string $day): string
    {
        $days = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu'
        ];
        return $days[$day] ?? $day;
    }
}

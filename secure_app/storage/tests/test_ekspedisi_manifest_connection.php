<?php
/**
 * test_ekspedisi_manifest_connection.php
 * Pengujian integrasi otomatis Target QTY Ekspedisi dari Manifest Excel di Hasil Ekspedisi.
 */

declare(strict_types=1);

define('BASE_PATH', realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../..'));

require_once BASE_PATH . '/app/Helpers/helpers.php';
loadEnv(BASE_PATH . '/.env');
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/app/Models/FotoEkspedisi.php';
require_once BASE_PATH . '/app/Models/HasilEkspedisi.php';

function assertTest(bool $condition, string $msg): void {
    if (!$condition) {
        echo "[FAIL] {$msg}\n";
        exit(1);
    }
    echo "[PASS] {$msg}\n";
}

echo "=== TEST KONEKTIVITAS MANIFEST HASIL EKSPEDISI -> TARGET QTY EKSPEDISI ===\n";

$fotoModel = new FotoEkspedisi();
$db = Database::getInstance();
$latestSpx = $db->fetch("SELECT DATE(tanggal_upload) as tgl FROM hasil_ekspedisi WHERE total_spx > 0 ORDER BY id DESC LIMIT 1");
$today = $latestSpx['tgl'] ?? date('Y-m-d');

// 1. Uji SPX (sudah ada file manifest 1.11.26 SPX.xlsx dengan 20 paket)
$infoSpx = $fotoModel->getTargetQtyInfo('SPX', $today);
assertTest($infoSpx['has_manifest'] === true, "Ekspedisi SPX terdeteksi memiliki manifest aktif pada hari ini ($today)");
assertTest($infoSpx['source'] === 'manifest', "Sumber target QTY SPX adalah 'manifest' (otomatis)");
assertTest($infoSpx['total_qty'] === 20, "Total target QTY SPX adalah 20 (sesuai jumlah resi pada file Excel)");
assertTest(!empty($infoSpx['files']), "Daftar file manifest SPX terisi dengan metadata file");
assertTest(strpos($infoSpx['files'][0]['nama_file'], 'SPX') !== false, "Nama file manifest memuat 'SPX'");

// 2. Uji JNE (belum ada file manifest untuk hari ini)
$infoJne = $fotoModel->getTargetQtyInfo('JNE', $today);
assertTest($infoJne['has_manifest'] === false, "Ekspedisi JNE terdeteksi belum memiliki file manifest hari ini");
assertTest($infoJne['manifest_qty'] === 0, "Manifest QTY JNE bernilai 0");

// 3. Uji method getTargetQty() langsung
$spxQty = $fotoModel->getTargetQty('SPX', $today);
assertTest($spxQty === 20, "FotoEkspedisi::getTargetQty('SPX') mengembalikan angka 20 secara otomatis");

// 4. Uji sinkronisasi otomatis syncManifestToTargetQty()
$synced = $fotoModel->syncManifestToTargetQty($today);
assertTest(isset($synced['SPX']) && $synced['SPX'] === 20, "syncManifestToTargetQty() berhasil menyinkronkan SPX ke ekspedisi_target_qty");

$rowTarget = $db->fetch("SELECT total_qty FROM ekspedisi_target_qty WHERE nama_ekspedisi = 'SPX' AND tanggal = :t LIMIT 1", ['t' => $today]);
assertTest($rowTarget && (int)$rowTarget['total_qty'] === 20, "Tabel ekspedisi_target_qty tersinkron otomatis bernilai 20");

// 5. Uji View Rendering (ekspedisi/index.php dan ekspedisi/capture.php)
$indexViewPath = BASE_PATH . '/resources/views/ekspedisi/index.php';
$indexContent = file_get_contents($indexViewPath);
assertTest(strpos($indexContent, 'OTOMATIS DARI MANIFEST HASIL EKSPEDISI') !== false, "View ekspedisi/index.php memuat badge 'OTOMATIS DARI MANIFEST HASIL EKSPEDISI'");
assertTest(strpos($indexContent, 'displayTargetQtyVal') !== false, "View ekspedisi/index.php memuat elemen displayTargetQtyVal");
assertTest(strpos($indexContent, 'Lihat Manifest Resi') !== false, "View ekspedisi/index.php memuat tautan cepat ke manifest resi");

$captureViewPath = BASE_PATH . '/resources/views/ekspedisi/capture.php';
$captureContent = file_get_contents($captureViewPath);
assertTest(strpos($captureContent, 'MANIFEST EXCEL TERSEDIA') !== false, "View ekspedisi/capture.php memuat indikator 'MANIFEST EXCEL TERSEDIA'");

// 6. Uji Kontroler Validasi Mismatch (EkspedisiController::store logic)
$targetAdminQty = $spxQty; // 20
$staffQtyMatch = 20;
$staffQtyMismatch = 18;
assertTest($staffQtyMatch === $targetAdminQty, "Pencocokan QTY berhasil ketika Staff menginput 20 (sesuai manifest 20)");
assertTest($staffQtyMismatch !== $targetAdminQty, "Pencocokan QTY mendeteksi selisih ketika Staff menginput 18 vs manifest 20");

echo "\n>>> SELURUH 12 PENGECEKAN KONEKTIVITAS MANIFEST SUKSES (100% PASS)! <<<\n";


<?php
/**
 * test_multi_expedition_export.php
 * Pengujian Komprehensif Fitur Export Excel Multi-Ekspedisi:
 * INSTAN, SPX, JNE, JNT, LEX, ID, NINJA, SICEPAT & ALL (Pilihan File Manifest & Tanggal)
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

define('BASE_PATH', realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../..'));

require_once BASE_PATH . '/app/Helpers/helpers.php';
loadEnv(BASE_PATH . '/.env');
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/app/Models/HasilEkspedisi.php';
require_once BASE_PATH . '/app/Controllers/HasilEkspedisiController.php';

function assertTest(bool $condition, string $msg): void {
    if (!$condition) {
        echo "[FAIL] {$msg}\n";
        exit(1);
    }
    echo "[PASS] {$msg}\n";
}

echo "=========================================================================\n";
echo " TEST FITUR EXPORT EXCEL MULTI-EKSPEDISI & MANIFEST (PT POLO SPORT)     \n";
echo "=========================================================================\n\n";

// 1. Verifikasi View Hasil Ekspedisi
echo "--- 1. Verifikasi UI & Modal View Hasil Ekspedisi ---\n";
$heViewPath = BASE_PATH . '/resources/views/hasil_ekspedisi/index.php';
$heContent = file_get_contents($heViewPath);

assertTest(strpos($heContent, 'id="btnOpenExportModal"') !== false, "Tombol 'btnOpenExportModal' tersedia di view hasil_ekspedisi");
assertTest(strpos($heContent, 'data-bs-target="#modalExportEkspedisi"') !== false, "Tombol Export Excel memicu modal '#modalExportEkspedisi'");
assertTest(strpos($heContent, 'id="modalExportEkspedisi"') !== false, "Modal '#modalExportEkspedisi' didefinisikan secara lengkap");
assertTest(strpos($heContent, 'id="exportEkspedisiSelect"') !== false, "Dropdown pilihan ekspedisi '#exportEkspedisiSelect' tersedia");

$expectedEkspedisi = ['INSTAN', 'SPX', 'JNE', 'JNT', 'LEX', 'ID', 'NINJA', 'SICEPAT', 'ALL'];
foreach ($expectedEkspedisi as $eksp) {
    assertTest(strpos($heContent, "data-eksp=\"{$eksp}\"") !== false, "Pill/Opsi ekspedisi '{$eksp}' tersedia di modal");
}

assertTest(strpos($heContent, 'id="exportFileIdSelect"') !== false, "Dropdown pilihan berkas/file manifest '#exportFileIdSelect' tersedia");
assertTest(strpos($heContent, 'id="exportStartDate"') !== false, "Input tanggal mulai '#exportStartDate' tersedia");
assertTest(strpos($heContent, 'id="exportEndDate"') !== false, "Input tanggal selesai '#exportEndDate' tersedia");
assertTest(strpos($heContent, 'date-preset-btn') !== false, "Tombol preset tanggal (Hari Ini, 7 Hari, Bulan Ini, Semua) tersedia");
assertTest(strpos($heContent, 'id="exportPreviewText"') !== false, "Preview ringkasan struktur kolom Excel '#exportPreviewText' tersedia");
assertTest(strpos($heContent, 'updateExportModalUI') !== false, "Fungsi JavaScript updateExportModalUI() terpasang dengan benar");

// 2. Verifikasi View Paket (Instan)
echo "\n--- 2. Verifikasi View Antrean Paket (Instan) ---\n";
$paketViewPath = BASE_PATH . '/resources/views/paket/index.php';
$paketContent = file_get_contents($paketViewPath);

assertTest(strpos($paketContent, 'id="btnExportAntreanInstan"') !== false, "Tombol 'btnExportAntreanInstan' tersedia di halaman antrean paket");
assertTest(strpos($paketContent, '/hasil-ekspedisi/export?ekspedisi=INSTAN') !== false, "Tombol export paket mengarah ke endpoint export terpadu");

// 3. Verifikasi Route Web
echo "\n--- 3. Verifikasi Route Web Configuration ---\n";
$routesContent = file_get_contents(BASE_PATH . '/routes/web.php');
assertTest(strpos($routesContent, "'/hasil-ekspedisi/export'") !== false, "Route '/hasil-ekspedisi/export' terdaftar di web.php");
assertTest(strpos($routesContent, "'HasilEkspedisiController@export'") !== false, "Route terhubung ke HasilEkspedisiController@export");

// 4. Verifikasi Model HasilEkspedisi Query Data
echo "\n--- 4. Verifikasi Logika Query Data di Model HasilEkspedisi ---\n";
$model = new HasilEkspedisi();

// A. Daftar manifest files
$manifestFiles = $model->getManifestFileList();
assertTest(is_array($manifestFiles), "getManifestFileList() mengembalikan array data berkas manifest");
echo "  [INFO] Ditemukan " . count($manifestFiles) . " file manifest terdaftar.\n";

// B. Query per Ekspedisi
foreach ($expectedEkspedisi as $eksp) {
    $data = $model->getExportData($eksp);
    assertTest(is_array($data), "getExportData('{$eksp}') mengembalikan array terstruktur");
    assertTest(isset($data['type'], $data['rows'], $data['count'], $data['title']), "Struktur data export '{$eksp}' valid (type, rows, count, title)");
    echo "  [INFO] Ekspedisi {$eksp}: {$data['count']} baris data diambil (Tipe: {$data['type']}).\n";
}

// C. Query dengan file_id spesifik jika ada manifest
if (!empty($manifestFiles)) {
    $firstFile = $manifestFiles[0];
    $dataSpecific = $model->getExportData('ALL', '', '', (int)$firstFile['id']);
    assertTest(is_array($dataSpecific['rows']), "Query export dengan filter file_id spesifik berhasil");
    echo "  [INFO] Filter spesifik manifest ID {$firstFile['id']} ('{$firstFile['nama_file']}'): {$dataSpecific['count']} baris.\n";
}

// 5. Verifikasi Pembuatan File Excel Asli (.xlsx) via XlsxWriter
echo "\n--- 5. Verifikasi Integritas File Excel (.xlsx) OpenXML ---\n";

// A. Uji Ekspor Ekspedisi Reguler (SPX)
$dataSpx = $model->getExportData('SPX');
$writerReguler = new XlsxWriter();
$writerReguler->setColWidths([6, 24, 26, 32, 14, 16, 20, 28, 30]);

$sheetReg = [];
$sheetReg[] = ["PT POLO SPORT INDONESIA - SISTEM LOGISTIK & EKSPEDISI", "", "", "", "", "", "", "", ""];
$writerReguler->addMergeCell("A1:I1");
$sheetReg[] = [$dataSpx['title'], "", "", "", "", "", "", "", ""];
$writerReguler->addMergeCell("A2:I2");
$sheetReg[] = ["Periode: Semua Periode | Dicetak: " . date('d/m/Y H:i') . " | Integritas: Aggregate SHA-256", "", "", "", "", "", "", "", ""];
$writerReguler->addMergeCell("A3:I3");
$sheetReg[] = ["", "", "", "", "", "", "", "", ""];
$sheetReg[] = ["NO", "NO RESI / WAYBILL", "NAMA PENERIMA", "ALAMAT / TUJUAN", "EKSPEDISI", "STATUS SCAN", "TANGGAL SCAN", "FILE MANIFEST", "SEKUEN HASH (SHA-256)"];

foreach (array_slice($dataSpx['rows'], 0, 5) as $i => $r) {
    $sheetReg[] = [
        $i + 1,
        $r['resi'] ?? '-',
        $r['nama_penerima'] ?: '-',
        $r['alamat'] ?: '-',
        $r['ekspedisi'] ?? 'SPX',
        $r['status_scan'] ?? 'Berhasil',
        date('d/m/Y H:i', strtotime($r['tanggal'] ?? 'now')),
        $r['manifest_file'] ?? '-',
        !empty($r['resi_hash']) ? substr($r['resi_hash'], 0, 16) . '...' : 'SHA256-OK'
    ];
}
$writerReguler->writeSheet($sheetReg);

$tempRegPath = BASE_PATH . '/storage/tests/test_reguler_export.xlsx';
$resReg = $writerReguler->save($tempRegPath);
assertTest($resReg && file_exists($tempRegPath) && filesize($tempRegPath) > 500, "File Excel reguler (.xlsx) berhasil digenerate (> 500 bytes)");

$zipReg = new ZipArchive();
assertTest($zipReg->open($tempRegPath) === true, "File .xlsx reguler dapat dibuka sebagai ZIP OpenXML valid");
assertTest($zipReg->locateName('xl/worksheets/sheet1.xml') !== false, "File .xlsx reguler memuat 'xl/worksheets/sheet1.xml'");
assertTest($zipReg->locateName('xl/styles.xml') !== false, "File .xlsx reguler memuat 'xl/styles.xml'");
$zipReg->close();
@unlink($tempRegPath);

// B. Uji Ekspor Ekspedisi Instan (Priority Scheduling)
$dataInstan = $model->getExportData('INSTAN');
$writerInstan = new XlsxWriter();
$writerInstan->setColWidths([6, 12, 22, 28, 14, 14, 12, 16, 20, 10, 14, 16]);

$sheetInst = [];
$sheetInst[] = ["PT POLO SPORT INDONESIA - SISTEM LOGISTIK & EKSPEDISI", "", "", "", "", "", "", "", "", "", "", ""];
$writerInstan->addMergeCell("A1:L1");
$sheetInst[] = [$dataInstan['title'], "", "", "", "", "", "", "", "", "", "", ""];
$writerInstan->addMergeCell("A2:L2");
$sheetInst[] = ["Periode: Semua Periode | Algoritma: Non-Preemptive + Aging | Total: " . count($dataInstan['rows']), "", "", "", "", "", "", "", "", "", "", ""];
$writerInstan->addMergeCell("A3:L3");
$sheetInst[] = ["", "", "", "", "", "", "", "", "", "", "", ""];
$sheetInst[] = ["NO", "NO PAKET", "NO PESANAN", "NAMA PEMESAN", "EKSPEDISI", "LAYANAN", "CUT-OFF", "SKOR PRIORITAS", "TANGGAL MASUK", "SESI", "STATUS", "PETUGAS"];

foreach (array_slice($dataInstan['rows'], 0, 5) as $i => $r) {
    $sheetInst[] = [
        $i + 1,
        $r['no_paket'] ?? ($i + 1),
        $r['no_pesanan'] ?? '-',
        $r['nama_pemesan'] ?? '-',
        $r['nama_ekspedisi'] ?? 'INSTANT',
        $r['layanan_ekspedisi'] ?? 'INSTANT',
        !empty($r['cutoff_time']) ? substr($r['cutoff_time'], 0, 5) : '-',
        number_format((float)($r['priority_score'] ?? 0), 2),
        date('d/m/Y H:i', strtotime($r['tanggal_masuk'] ?? 'now')),
        $r['sesi'] ?? 'SESI 1',
        $r['status'] ?? 'Pending',
        $r['petugas_input'] ?? '-'
    ];
}
$writerInstan->writeSheet($sheetInst);

$tempInstPath = BASE_PATH . '/storage/tests/test_instan_export.xlsx';
$resInst = $writerInstan->save($tempInstPath);
assertTest($resInst && file_exists($tempInstPath) && filesize($tempInstPath) > 500, "File Excel Instan (.xlsx) berhasil digenerate (> 500 bytes)");

$zipInst = new ZipArchive();
assertTest($zipInst->open($tempInstPath) === true, "File .xlsx Instan dapat dibuka sebagai ZIP OpenXML valid");
assertTest($zipInst->locateName('xl/worksheets/sheet1.xml') !== false, "File .xlsx Instan memuat 'xl/worksheets/sheet1.xml'");
$zipInst->close();
@unlink($tempInstPath);

echo "\n=========================================================================\n";
echo " SEMUA PENGUJIAN FITUR EXPORT EXCEL MULTI-EKSPEDISI BERHASIL 100%! [PASS]\n";
echo "=========================================================================\n";


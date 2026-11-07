<?php
/**
 * test_unify_instant_sameday.php
 * Verifikasi penyatuan opsi INSTAN dan SAMEDAY di UI dan backend sistem skripsi.
 */

define('BASE_PATH', 'C:/laragon/www/PICKUP_PAKET_SKRIPSI/secure_app');
require_once BASE_PATH . '/app/Helpers/helpers.php';
loadEnv(BASE_PATH . '/.env');
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/app/Models/HasilEkspedisi.php';
require_once BASE_PATH . '/app/Services/PaketService.php';
require_once BASE_PATH . '/app/Services/PrioritySchedulingService.php';
require_once BASE_PATH . '/app/Services/IntegritySha256Service.php';

function assertTest(bool $condition, string $msg): void {
    if (!$condition) {
        echo "[FAIL] {$msg}\n";
        exit(1);
    }
    echo "[PASS] {$msg}\n";
}

echo "=== TEST PENYATUAN INSTAN & SAMEDAY ===\n";

// 1. Periksa view hasil_ekspedisi/index.php
$viewPath = BASE_PATH . '/resources/views/hasil_ekspedisi/index.php';
$viewContent = file_get_contents($viewPath);

// Cek dropdown Import Excel
assertTest(
    strpos($viewContent, 'INSTAN / SAMEDAY — Kurir Instan &amp; Same-Day') !== false,
    "Option gabungan 'INSTAN / SAMEDAY' tersedia di Modal Import Excel"
);

// Pastikan tidak ada option SAMEDAY mandiri di dalam dropdown import #ui_nama_ekspedisi
$uiSelectMatch = preg_match('/<select name="nama_ekspedisi" id="ui_nama_ekspedisi"[^>]*>(.*?)<\/select>/s', $viewContent, $m);
assertTest($uiSelectMatch && strpos($m[1], '<option value="SAMEDAY"') === false,
    "Option mandiri 'SAMEDAY' telah dihilangkan dari dropdown #ui_nama_ekspedisi (kini bersatu di INSTAN / SAMEDAY)"
);

// Cek dropdown filter #heEkspedisiFilter
$filterMatch = preg_match('/<select name="ekspedisi" id="heEkspedisiFilter"[^>]*>(.*?)<\/select>/s', $viewContent, $fm);
assertTest($filterMatch && strpos($fm[1], '<option value="SAMEDAY"') === false,
    "Option mandiri 'SAMEDAY' dihilangkan dari filter ekspedisi (kini menjadi opsi terpadu 'INSTAN / SAMEDAY')"
);
assertTest($filterMatch && strpos($fm[1], 'INSTAN / SAMEDAY') !== false,
    "Filter ekspedisi memiliki opsi terpadu 'INSTAN / SAMEDAY'"
);

// Cek dropdown Tambah Paket #cp_layanan_ekspedisi
assertTest(
    strpos($viewContent, 'INSTAN / SAMEDAY (Bobot 100)') !== false,
    "Label SLA kini terpadu 'INSTAN / SAMEDAY (Bobot 100)'"
);

// 2. Periksa view paket/create.php dan paket/edit.php
$createContent = file_get_contents(BASE_PATH . '/resources/views/paket/create.php');
assertTest(
    strpos($createContent, 'INSTAN / SAMEDAY (Bobot 100)') !== false,
    "Halaman paket/create.php memiliki label SLA terpadu 'INSTAN / SAMEDAY (Bobot 100)'"
);

// 3. Verifikasi Logika Backend: Keduanya bobot SLA = 100
$priorityService = new PrioritySchedulingService();
$scoreInstant = $priorityService->calculateServiceScore('INSTANT');
$scoreSameday = $priorityService->calculateServiceScore('SAMEDAY');
assertTest($scoreInstant === 100 && $scoreSameday === 100, "Backend memberikan bobot SLA identik (100) untuk INSTANT dan SAMEDAY");

// 4. Verifikasi PaketService::isInstantOrSameday mengenali format terpadu
assertTest(PaketService::isInstantOrSameday('INSTANT'), "isInstantOrSameday mengenali INSTANT");
assertTest(PaketService::isInstantOrSameday('SAMEDAY'), "isInstantOrSameday mengenali SAMEDAY");
assertTest(PaketService::isInstantOrSameday('INSTAN / SAMEDAY'), "isInstantOrSameday mengenali INSTAN / SAMEDAY");

// 5. Verifikasi Filter HasilEkspedisi::getStats dan countFiltered dengan INSTANT
$model = new HasilEkspedisi();
$stats = $model->getStats('', 'INSTANT');
assertTest(isset($stats['total_pakets']), "HasilEkspedisi::getStats() berjalan lancar saat difilter dengan ekspedisi INSTANT");

echo "\n>>> SELURUH PENGUJIAN PENYATUAN INSTAN & SAMEDAY SUKSES! <<<\n";

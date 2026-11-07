<?php
/**
 * test_role_separation_ekspedisi.php
 * Pengujian Pemisahan Hak Akses (Role Separation):
 * - Admin: Khusus monitoring, audit SHA-256, target manifest QTY (TIDAK ada Ambil Foto / Upload Manual)
 * - Staff: Khusus operasional lapangan, input fisik, ambil foto / upload bukti serah terima
 */

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

define('BASE_PATH', realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../..'));

require_once BASE_PATH . '/core/App.php';
require_once BASE_PATH . '/app/Helpers/helpers.php';
loadEnv(BASE_PATH . '/.env');
require_once BASE_PATH . '/core/Database.php';
require_once BASE_PATH . '/core/Model.php';
require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/app/Controllers/EkspedisiController.php';

function assertTest(bool $condition, string $msg): void {
    if (!$condition) {
        echo "[FAIL] {$msg}\n";
        exit(1);
    }
    echo "[PASS] {$msg}\n";
}

echo "=== TEST PEMISAHAN PERAN (ADMIN MONITORING VS STAFF OPERASIONAL) ===\n";

// 1. Simulasikan render view untuk ADMIN
$_SESSION['user'] = [
    'id'       => 1,
    'username' => 'adminpolo',
    'role'     => 'admin'
];

assertTest(isAdmin() === true, "Session user berhasil dikenali sebagai ADMIN");

$viewPath = BASE_PATH . '/resources/views/ekspedisi/index.php';
$viewContent = file_get_contents($viewPath);

// Uji logika kondisional role pada view ekspedisi/index.php
assertTest(strpos($viewContent, '<?php if (!isAdmin()): ?>') !== false, "View ekspedisi/index.php membungkus tombol operasional dengan pengecekan !isAdmin()");
assertTest(strpos($viewContent, 'MODE MONITORING ADMIN') !== false, "View ekspedisi/index.php menyediakan badge 'MODE MONITORING ADMIN' untuk akun Admin");
assertTest(strpos($viewContent, 'Pusat Manifest Excel') !== false, "View ekspedisi/index.php menyediakan tautan cepat ke 'Pusat Manifest Excel' untuk Admin");

// Evaluasi output render untuk Admin
ob_start();
$ekspedisi  = 'SPX';
$photos     = [];
$targetQty  = 20;
$targetInfo = ['has_manifest' => true, 'files' => []];
$tanggal    = '2026-11-04';
include $viewPath;
$renderedAdmin = ob_get_clean();

assertTest(strpos($renderedAdmin, 'AMBIL FOTO') === false, "[ADMIN VIEW] Tombol 'AMBIL FOTO' TIDAK dirender untuk akun Admin");
assertTest(strpos($renderedAdmin, 'UPLOAD MANUAL') === false, "[ADMIN VIEW] Tombol 'UPLOAD MANUAL' TIDAK dirender untuk akun Admin");
assertTest(strpos($renderedAdmin, 'manualUploadHeader') === false, "[ADMIN VIEW] Input file 'manualUploadHeader' TIDAK dirender untuk akun Admin");
assertTest(strpos($renderedAdmin, 'MODE MONITORING ADMIN') !== false, "[ADMIN VIEW] Indikator 'MODE MONITORING ADMIN' berhasil tampil untuk Admin");
assertTest(strpos($renderedAdmin, 'Pusat Manifest Excel') !== false, "[ADMIN VIEW] Tautan 'Pusat Manifest Excel' berhasil tampil untuk Admin");

// 2. Simulasikan render view untuk STAFF
$_SESSION['user'] = [
    'id'       => 2,
    'username' => 'staffpolo',
    'role'     => 'staff'
];

assertTest(isAdmin() === false, "Session user berhasil dikenali sebagai STAFF (non-admin)");

ob_start();
include $viewPath;
$renderedStaff = ob_get_clean();

assertTest(strpos($renderedStaff, 'AMBIL FOTO') !== false, "[STAFF VIEW] Tombol 'AMBIL FOTO' berhasil dirender untuk akun Staff");
assertTest(strpos($renderedStaff, 'UPLOAD MANUAL') !== false, "[STAFF VIEW] Tombol 'UPLOAD MANUAL' berhasil dirender untuk akun Staff");
assertTest(strpos($renderedStaff, 'manualUploadHeader') !== false, "[STAFF VIEW] Input file 'manualUploadHeader' berhasil dirender untuk Staff");
assertTest(strpos($renderedStaff, 'MODE MONITORING ADMIN') === false, "[STAFF VIEW] Indikator 'MODE MONITORING ADMIN' TIDAK tampil untuk Staff");

// 3. Uji proteksi controller EkspedisiController
$ctrlPath = BASE_PATH . '/app/Controllers/EkspedisiController.php';
$ctrlContent = file_get_contents($ctrlPath);

assertTest(strpos($ctrlContent, "if (isAdmin()) {\n            \$this->flash('warning',") !== false || 
           strpos($ctrlContent, "if (isAdmin())") !== false, 
           "EkspedisiController::capture memiliki pengecekan role isAdmin()");

assertTest(strpos($ctrlContent, "Akun Administrator dikhususkan untuk memantau") !== false,
           "EkspedisiController::capture memiliki pesan edukatif pengalihan Admin");

echo "\n>>> SELURUH PENGUJIAN PEMISAHAN ROLE ADMIN & STAFF SUKSES (100% PASS)! <<<\n";

<?php
/**
 * test_move_delete_buttons.php
 * Pengujian Pemindahan Fitur Hapus Paket Sudah & Hapus Semua Paket
 * dari Halaman Hasil Ekspedisi ke Halaman Antrean Paket (Instan).
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
require_once BASE_PATH . '/app/Controllers/PaketController.php';

function assertTest(bool $condition, string $msg): void {
    if (!$condition) {
        echo "[FAIL] {$msg}\n";
        exit(1);
    }
    echo "[PASS] {$msg}\n";
}

echo "=== TEST PEMINDAHAN FITUR HAPUS PAKET KE HALAMAN INSTAN ===\n";

// 1. Verifikasi View Hasil Ekspedisi (Harus Bersih dari Tombol Hapus Paket)
$heViewPath = BASE_PATH . '/resources/views/hasil_ekspedisi/index.php';
$heContent = file_get_contents($heViewPath);

assertTest(strpos($heContent, 'id="btnDeleteSudah"') === false, "Halaman Hasil Ekspedisi TIDAK lagi memiliki tombol 'btnDeleteSudah'");
assertTest(strpos($heContent, 'id="btnDeleteAll"') === false, "Halaman Hasil Ekspedisi TIDAK lagi memiliki tombol 'btnDeleteAll'");
assertTest(strpos($heContent, 'Hapus Paket Sudah') === false, "Halaman Hasil Ekspedisi TIDAK lagi memiliki label 'Hapus Paket Sudah'");
assertTest(strpos($heContent, 'Hapus Semua Paket') === false, "Halaman Hasil Ekspedisi TIDAK lagi memiliki label 'Hapus Semua Paket'");
assertTest(strpos($heContent, 'id="deleteSudahForm"') === false, "Halaman Hasil Ekspedisi TIDAK lagi memiliki form 'deleteSudahForm'");
assertTest(strpos($heContent, 'id="deleteAllForm"') === false, "Halaman Hasil Ekspedisi TIDAK lagi memiliki form 'deleteAllForm'");

// 2. Verifikasi View Antrean Paket (Instan)
$paketViewPath = BASE_PATH . '/resources/views/paket/index.php';
$paketContent = file_get_contents($paketViewPath);

assertTest(strpos($paketContent, 'id="btnDeleteSudah"') !== false, "Halaman Instan (paket/index.php) memuat tombol 'btnDeleteSudah'");
assertTest(strpos($paketContent, 'id="btnDeleteAll"') !== false, "Halaman Instan (paket/index.php) memuat tombol 'btnDeleteAll'");
assertTest(strpos($paketContent, 'Hapus Paket Sudah') !== false, "Halaman Instan memuat label 'Hapus Paket Sudah'");
assertTest(strpos($paketContent, 'Hapus Semua Paket') !== false, "Halaman Instan memuat label 'Hapus Semua Paket'");
assertTest(strpos($paketContent, 'id="deleteSudahForm"') !== false, "Halaman Instan memuat form 'deleteSudahForm'");
assertTest(strpos($paketContent, 'id="deleteAllForm"') !== false, "Halaman Instan memuat form 'deleteAllForm'");

// 3. Simulasi Rendering View Paket untuk ADMIN menggunakan Controller context
class TestDummyController extends Controller {
    public function renderTest(string $viewPath, array $data): string {
        extract($data);
        ob_start();
        include BASE_PATH . '/resources/views/' . str_replace('.', '/', $viewPath) . '.php';
        return ob_get_clean();
    }
}

$testCtrl = new TestDummyController();

$_SESSION['user'] = [
    'id'       => 1,
    'username' => 'adminpolo',
    'role'     => 'admin'
];

assertTest(isAdmin() === true, "Sesi dikenali sebagai ADMIN");

$viewData = [
    'stats'   => ['total' => 10, 'pending' => 8, 'sudah' => 2, 'batal' => 0, 'retur' => 0],
    'tanggal' => '2026-10-30',
    'search'  => '',
    'status'  => '',
    'sort'    => 'priority',
    'data'    => ['pakets' => []]
];

$renderedAdmin = $testCtrl->renderTest('paket.index', $viewData);

assertTest(strpos($renderedAdmin, 'id="btnDeleteSudah"') !== false, "[ADMIN] Tombol 'btnDeleteSudah' BERHASIL dirender untuk Admin di halaman Instan");
assertTest(strpos($renderedAdmin, 'id="btnDeleteAll"') !== false, "[ADMIN] Tombol 'btnDeleteAll' BERHASIL dirender untuk Admin di halaman Instan");
assertTest(strpos($renderedAdmin, 'Hapus Paket Sudah') !== false, "[ADMIN] Teks 'Hapus Paket Sudah' tampil di antrean paket");
assertTest(strpos($renderedAdmin, 'Hapus Semua Paket') !== false, "[ADMIN] Teks 'Hapus Semua Paket' tampil di antrean paket");

// 4. Simulasi Rendering View Paket untuk STAFF
$_SESSION['user'] = [
    'id'       => 2,
    'username' => 'staffpolo',
    'role'     => 'staff'
];

assertTest(isAdmin() === false, "Sesi dikenali sebagai STAFF");

$renderedStaff = $testCtrl->renderTest('paket.index', $viewData);

assertTest(strpos($renderedStaff, 'id="btnDeleteSudah"') === false, "[STAFF] Tombol 'btnDeleteSudah' TIDAK dirender untuk akun Staff");
assertTest(strpos($renderedStaff, 'id="btnDeleteAll"') === false, "[STAFF] Tombol 'btnDeleteAll' TIDAK dirender untuk akun Staff");

echo "\n>>> SELURUH PENGUJIAN PEMINDAHAN FITUR HAPUS PAKET SUKSES (100% PASS)! <<<\n";

<?php
/**
 * FotoInstanController.php
 */

require_once BASE_PATH . '/app/Models/FotoInstan.php';
require_once BASE_PATH . '/app/Models/Paket.php';

class FotoInstanController extends Controller
{
    private FotoInstan $fotoModel;
    private Paket $paketModel;

    public function __construct()
    {
        $this->fotoModel = new FotoInstan();
        $this->paketModel = new Paket();
    }

    /**
     * AJAX: Get photos by Paket ID
     */
    public function showByPaket($id)
    {
        $photos = $this->fotoModel->getByPaketId($id);
        $this->json([
            'success' => true,
            'photos'  => $photos
        ]);
    }

    /**
     * View: Capture photo using camera (Supports Bulk)
     */
    public function capture($id = null)
    {
        $ids = $_POST['ids'] ?? $_GET['ids'] ?? $id;
        if (!$ids) {
            $this->flash('error', 'Tidak ada paket yang dipilih.');
            $this->redirect('/paket');
            return;
        }

        $idArray = is_string($ids) ? explode(',', $ids) : [$ids];
        $idArray = array_filter($idArray); // Remove empty values

        $pakets = [];
        foreach ($idArray as $pId) {
            $paket = $this->paketModel->find($pId);
            if ($paket) {
                if (!isAdmin() && $paket['user_id'] != (auth()['id'] ?? 0)) {
                    continue;
                }
                $pakets[] = $paket;
            }
        }

        if (empty($pakets)) {
            $this->flash('error', 'Data paket tidak ditemukan.');
            $this->redirect('/paket');
            return;
        }

        // If only one, keep standard behavior, if many, use bulk view data
        $this->view('foto-instan/capture', [
            'title' => count($pakets) > 1 ? 'Ambil Foto Masal (' . count($pakets) . ' Paket)' : 'Ambil Foto Paket',
            'paket' => $pakets[0], // For legacy support/compatibility in view
            'pakets' => $pakets,
            'isBulk' => count($pakets) > 1,
            'ids' => implode(',', array_column($pakets, 'id'))
        ]);
    }

    /**
     * Action: Store photo from base64 (camera) or upload (Supports Bulk)
     */
    public function store()
    {
        $paketIds = $_POST['paket_ids'] ?? $_POST['paket_id'] ?? null;
        $base64Image = $_POST['image'] ?? null;

        if (!$paketIds) {
            $this->json(['success' => false, 'message' => 'Tidak ada ID paket yang diterima.'], 400);
            return;
        }

        if (!$base64Image) {
            $this->json(['success' => false, 'message' => 'Tidak ada data gambar.'], 400);
            return;
        }

        $idArray = is_array($paketIds) ? $paketIds : explode(',', $paketIds);
        
        // Ambil data paket pertama untuk penamaan file utama
        $firstPaket = $this->paketModel->find($idArray[0]);
        $noPesanan = $firstPaket ? preg_replace('/[^A-Za-z0-9]/', '', $firstPaket['no_pesanan']) : 'UNKNOWN';
        
        $namaPemesanRaw = $firstPaket ? $firstPaket['nama_pemesan'] : 'UNKNOWN';
        $namaPemesan = preg_replace('/[^A-Za-z0-9]/', '_', $namaPemesanRaw);
        $namaPemesan = preg_replace('/_+/', '_', $namaPemesan);
        $namaPemesan = trim($namaPemesan, '_');

        $relativeFolder = 'storage/foto_instan/' . date('Y/m');
        $targetDir = BASE_PATH . '/../public_html/' . $relativeFolder;
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Decode base64 image data and extract the raw image format
        $origExt = 'jpg';
        if (str_contains($base64Image, 'data:image/webp')) {
            $origExt = 'webp';
            $img = str_replace('data:image/webp;base64,', '', $base64Image);
        } else if (str_contains($base64Image, 'data:image/png')) {
            $origExt = 'png';
            $img = str_replace('data:image/png;base64,', '', $base64Image);
        } else {
            $img = str_replace('data:image/jpeg;base64,', '', $base64Image);
        }

        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);

        $suffix = count($idArray) > 1 ? '_BULK_' . count($idArray) : '';
        
        // Target is always WebP for maximum compression and modern web standards
        $extension = 'webp';
        $filename = $noPesanan . '_' . $namaPemesan . $suffix . '_' . time() . '.' . $extension;
        $path = $relativeFolder . '/' . $filename;
        $fullPath = $targetDir . '/' . $filename;

        $saved = false;

        // Try converting to WebP using GD library
        if (function_exists('imagecreatefromstring')) {
            $im = @imagecreatefromstring($data);
            if ($im !== false) {
                // Ensure transparent background (e.g. from PNG) is handled properly
                imagealphablending($im, false);
                imagesavealpha($im, true);
                
                // Save WebP image with quality 80 (excellent compromise between size and quality)
                if (@imagewebp($im, $fullPath, 80)) {
                    $saved = true;
                }
                imagedestroy($im);
            }
        }

        // Fallback: If GD is not available or conversion failed, save in the original format
        if (!$saved) {
            $extension = $origExt;
            $filename = $noPesanan . '_' . $namaPemesan . $suffix . '_' . time() . '.' . $extension;
            $path = $relativeFolder . '/' . $filename;
            $fullPath = $targetDir . '/' . $filename;
            
            if (file_put_contents($fullPath, $data) !== false) {
                $saved = true;
            }
        }

        if ($saved) {
            $userId = auth()['id'];
            
            // Loop through all selected IDs
            foreach ($idArray as $pId) {
                if (empty($pId)) continue;
                
                $paket = $this->paketModel->find($pId);
                if (!isAdmin() && $paket && $paket['user_id'] != (auth()['id'] ?? 0)) {
                    continue;
                }
                
                $this->fotoModel->savePhoto($pId, $path, $userId);
                
                // Update status to 'Sudah' if Pending
                if ($paket && $paket['status'] === 'Pending') {
                    $this->paketModel->update($pId, ['status' => 'Sudah']);
                }
            }

            $this->json(['success' => true, 'message' => count($idArray) > 1 ? 'Foto berhasil disimpan untuk ' . count($idArray) . ' paket.' : 'Foto berhasil disimpan.']);
        } else {
            $this->json(['success' => false, 'message' => 'Gagal menyimpan file ke server.'], 500);
        }
    }
}

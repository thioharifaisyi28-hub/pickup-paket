<div class="capture-container animate__animated animate__fadeIn" style="max-width: 600px; margin: 0 auto; min-height: calc(100vh - 130px); display: flex; flex-direction: column; justify-content: center; padding: 1rem;">
    <!-- Header with Back Button -->
    <div class="d-flex align-items-center mb-4" style="gap: 1.25rem;">
        <a href="<?= url('/paket') ?>" class="btn-back-circle">
            <i data-lucide="arrow-left" style="width: 22px; height: 22px;"></i>
        </a>
        <div style="flex: 1;">
            <h1 class="page-title mb-0" style="font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1e293b;">AMBIL FOTO PAKET INSTAN</h1>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-soft-primary text-primary px-2 py-1" style="font-size: 10px; font-weight: 700; border-radius: 4px;">PETUGAS</span>
                <span class="text-muted small fw-bold"><?= e(auth()['username']) ?></span>
            </div>
        </div>
    </div>

    <!-- Info Paket Banner -->
    <div class="alert alert-primary border-0 shadow-sm rounded-4 mb-3" style="background: white; padding: 1rem;">
        <div class="d-flex align-items-start gap-3">
            <div style="width: 40px; height: 40px; background: #eff6ff; color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="package" style="width: 20px; height: 20px;"></i>
            </div>
            <div style="flex: 1;">
                <?php if (isset($isBulk) && $isBulk): ?>
                    <div style="font-size: 13px; font-weight: 800; color: #1e293b; margin-bottom: 8px;">
                        MEMPROSES <?= count($pakets) ?> PAKET TERPILIH:
                    </div>
                    <div style="max-height: 100px; overflow-y: auto; padding-right: 5px;">
                        <?php foreach ($pakets as $p): ?>
                            <div class="d-flex justify-content-between align-items-center mb-1 pb-1" style="border-bottom: 1px dashed #f1f5f9;">
                                <span style="font-size: 11px; font-weight: 700; color: #475569;"><?= e($p['no_pesanan']) ?></span>
                                <span style="font-size: 10px; color: #94a3b8;"><?= e($p['nama_pemesan']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="font-size: 12px; font-weight: 700; color: #1e293b;"><?= e($paket['no_pesanan']) ?></div>
                    <div style="font-size: 11px; color: #64748b;"><?= e($paket['nama_pemesan']) ?> (<?= e($paket['sesi']) ?>)</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Card with Tabs -->
    <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 20px; background: white; border: 1px solid #e2e8f0;">
        <div class="card-header" style="background: #f8fafc; padding: 0.75rem 1.25rem; border-bottom: 1px solid #e2e8f0;">
             <div style="display: flex; gap: 0.75rem; width: 100%; justify-content: center;">
                <button type="button" class="btn-tab active" id="tabCamera" onclick="switchTab('camera')">
                    <i data-lucide="camera" style="width: 14px; height: 14px;"></i> Kamera
                </button>
                <button type="button" class="btn-tab" id="tabUpload" onclick="switchTab('upload')">
                    <i data-lucide="upload" style="width: 14px; height: 14px;"></i> Upload
                </button>
            </div>
        </div>
        
        <div class="card-body" style="padding: 2rem 2rem 4rem 2rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <!-- Camera View -->
            <div id="cameraContent" style="width: 100%; max-width: 450px; display: flex; flex-direction: column; align-items: center;">
                <div id="cameraPreviewContainer" style="width: 100%; aspect-ratio: 4/3; background: #000; border-radius: 16px; overflow: hidden; position: relative; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2); border: 3px solid #334155;">
                    <video id="video" width="100%" height="100%" autoplay playsinline style="object-fit: cover;"></video>
                    <canvas id="canvas" style="display: none;"></canvas>
                    
                    <!-- Viewfinder Overlay -->
                    <div style="position: absolute; inset: 25px; border: 1px dashed rgba(255,255,255,0.4); border-radius: 12px; pointer-events: none;"></div>
                    
                    <!-- Loading State -->
                    <div id="cameraLoading" style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; background: rgba(15, 23, 42, 0.95);">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 2rem; height: 2rem; border-width: 0.25em;"></div>
                        <p style="font-size: 12px; font-weight: 600; letter-spacing: 1px;">MENGHUBUNGKAN KAMERA...</p>
                    </div>
                </div>
                
                <div style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; margin-top: 2rem;">
                    <button class="btn-capture-main" id="btnCapture">
                        <div class="inner-circle"></div>
                    </button>
                    <div style="margin-top: 1rem; font-size: 11px; color: #475569; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;">AMBIL FOTO</div>
                </div>
            </div>

            <!-- Upload View -->
            <div id="uploadContent" style="display: none; width: 100%;">
                <div id="dropZone" class="upload-dropzone" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon-wrapper">
                        <i data-lucide="image-plus" style="width: 24px; height: 24px; color: #3b82f6;"></i>
                    </div>
                    <p style="font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; font-size: 14px;">Klik untuk Pilih Foto</p>
                    <p style="font-size: 11px; color: #94a3b8;">Format: JPG, PNG, WEBP (Maks 5MB)</p>
                    <input type="file" id="fileInput" accept="image/*" style="display: none;">
                </div>
            </div>

            <!-- Result View -->
            <div id="resultContent" style="display: none; width: 100%;">
                <div style="width: 100%; aspect-ratio: 4/3; border-radius: 12px; overflow: hidden; background: #000; border: 2px solid #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                    <img id="photoPreview" src="" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; justify-content: center;">
                    <button class="btn btn-light" onclick="retakePhoto()" style="flex: 1; padding: 10px; border-radius: 10px; font-weight: 600; font-size: 13px; border: 1px solid #e2e8f0;">
                        <i data-lucide="refresh-ccw" style="width: 14px; height: 14px; margin-right: 6px;"></i> ULANGI
                    </button>
                    <button class="btn btn-success" id="btnSave" style="flex: 2; padding: 10px; border-radius: 10px; background: #10b981; border: none; color: white; font-weight: 700; font-size: 13px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);">
                        <i data-lucide="save" style="width: 14px; height: 14px; margin-right: 6px;"></i> SIMPAN FOTO
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-back-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        text-decoration: none;
    }
    .btn-back-circle:hover {
        background: #f8fafc;
        color: #1e293b;
        transform: translateX(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn-tab {
        padding: 6px 20px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-tab.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
    }
    .btn-tab:hover:not(.active) {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .btn-capture-main {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: white;
        border: 5px solid #3b82f6;
        padding: 5px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    .btn-capture-main:active { transform: scale(0.9); }
    .btn-capture-main .inner-circle {
        width: 100%;
        height: 100%;
        background: #3b82f6;
        border-radius: 50%;
        transition: background 0.2s;
    }

    .upload-dropzone {
        width: 100%;
        aspect-ratio: 4/3;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8fafc;
    }
    .upload-dropzone:hover {
        background: #eff6ff;
        border-color: #3b82f6;
    }
    .upload-icon-wrapper {
        width: 48px;
        height: 48px;
        background: #eff6ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
    }
    .bg-soft-primary { background: #eff6ff; }
</style>

<script>
let stream = null;
let currentMode = 'camera';
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const photoPreview = document.getElementById('photoPreview');
const paketIds = "<?= $ids ?? $paket['id'] ?>";
const isBulk = <?= isset($isBulk) && $isBulk ? 'true' : 'false' ?>;
const firstPackageName = "<?= addslashes($paket['nama_pemesan']) ?>";
const firstPackageOrder = "<?= addslashes($paket['no_pesanan']) ?>";
const bulkCount = <?= isset($pakets) ? count($pakets) : 1 ?>;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    switchTab('camera');
});

function switchTab(mode) {
    currentMode = mode;
    document.getElementById('tabCamera').classList.toggle('active', mode === 'camera');
    document.getElementById('tabUpload').classList.toggle('active', mode === 'upload');
    
    document.getElementById('cameraContent').style.display = mode === 'camera' ? 'flex' : 'none';
    document.getElementById('uploadContent').style.display = mode === 'upload' ? 'block' : 'none';
    document.getElementById('resultContent').style.display = 'none';
    
    if (mode === 'camera') startCamera(); else stopCamera();
    if (window.lucide) lucide.createIcons();
}

async function startCamera() {
    document.getElementById('cameraLoading').style.display = 'flex';
    try {
        const constraints = {
            video: { 
                facingMode: { ideal: 'environment' },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        stream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            document.getElementById('cameraLoading').style.display = 'none';
            video.play();
        };
    } catch (err) {
        console.error(err);
        // Fallback to any camera
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.onloadedmetadata = () => {
                document.getElementById('cameraLoading').style.display = 'none';
                video.play();
            };
        } catch (e) {
            document.getElementById('cameraLoading').innerHTML = '<div class="p-3 text-center"><i data-lucide="camera-off" class="text-danger mb-2"></i><p style="font-size: 11px;" class="text-danger mb-0">Gagal akses kamera. Gunakan mode upload.</p></div>';
            if (window.lucide) lucide.createIcons();
        }
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(t => t.stop());
        stream = null;
    }
}

// Camera Capture
document.getElementById('btnCapture').onclick = function() {
    // Flash Effect
    const flash = document.createElement('div');
    flash.style = 'position:fixed; top:0; left:0; width:100%; height:100%; background:white; z-index:9999; pointer-events:none;';
    document.body.appendChild(flash);
    $(flash).fadeOut(300, () => flash.remove());

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    // Apply watermark label
    let labelName = firstPackageName;
    let labelOrder = firstPackageOrder;
    if (isBulk) {
        labelName = firstPackageName + " & " + (bulkCount - 1) + " Lainnya";
        labelOrder = "BULK UPLOAD (" + bulkCount + " Paket)";
    }

    applyLabel(
        canvas, 
        labelName, 
        labelOrder,
        '<?= addslashes(auth()["username"] ?? "Petugas") ?>'
    );
    
    photoPreview.src = canvas.toDataURL('image/webp', 0.8);
    document.getElementById('cameraContent').style.display = 'none';
    document.getElementById('resultContent').style.display = 'block';
    stopCamera();
    if (window.lucide) lucide.createIcons();
};

// File Upload
document.getElementById('fileInput').onchange = function(e) {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'Ukuran file maksimal 5MB', 'error');
            this.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(ex) {
            const img = new Image();
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                
                // Apply watermark label
                let labelName = firstPackageName;
                let labelOrder = firstPackageOrder;
                if (isBulk) {
                    labelName = firstPackageName + " & " + (bulkCount - 1) + " Lainnya";
                    labelOrder = "BULK UPLOAD (" + bulkCount + " Paket)";
                }

                applyLabel(
                    canvas, 
                    labelName, 
                    labelOrder,
                    '<?= addslashes(auth()["username"] ?? "Petugas") ?>'
                );
                
                photoPreview.src = canvas.toDataURL('image/webp', 0.8);
                document.getElementById('uploadContent').style.display = 'none';
                document.getElementById('resultContent').style.display = 'block';
                if (window.lucide) lucide.createIcons();
            };
            img.src = ex.target.result;
        };
        reader.readAsDataURL(file);
    }
};

function applyLabel(canvas, nama, nopesanan, petugas) {
    const ctx = canvas.getContext('2d');
    const width = canvas.width;
    const height = canvas.height;
    
    const fontSize = Math.max(14, Math.floor(width / 35));
    const padding = fontSize;
    const barHeight = fontSize * 3.5;
    
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    ctx.fillRect(0, height - barHeight, width, barHeight);
    
    ctx.fillStyle = 'white';
    ctx.textBaseline = 'top';
    ctx.font = `bold ${fontSize}px sans-serif`;
    
    const textMain = `${nama} - ${nopesanan}`;
    ctx.fillText(textMain, padding, height - barHeight + padding);
    
    const now = new Date();
    const timeString = now.toLocaleString('id-ID', { 
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
    
    ctx.font = `${fontSize * 0.8}px sans-serif`;
    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
    ctx.fillText(`${timeString} | Petugas: ${petugas}`, padding, height - (fontSize * 1.5));
    
    const brand = 'PT. POLO SPORT INDONESIA';
    ctx.font = `italic ${fontSize * 0.6}px sans-serif`;
    const brandWidth = ctx.measureText(brand).width;
    ctx.fillText(brand, width - brandWidth - padding, height - (fontSize * 1.2));
}

function retakePhoto() {
    if (currentMode === 'camera') switchTab('camera'); else switchTab('upload');
}

document.getElementById('btnSave').onclick = function() {
    Swal.fire({ 
        title: 'Menyimpan Foto...', 
        text: 'Sedang memproses data',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading() 
    });
    
    $.ajax({
        url: "<?= url('/foto-instan/store') ?>",
        type: 'POST',
        data: {
            paket_ids: paketIds,
            image: photoPreview.src,
            _csrf_token: "<?= csrf_token() ?>"
        },
        success: (res) => {
            if(res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = "<?= url('/paket') ?>";
                });
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        },
        error: () => {
            Swal.fire('Error', 'Terjadi kesalahan sistem.', 'error');
        }
    });
};
</script>

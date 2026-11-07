<div class="capture-container animate__animated animate__fadeIn" style="max-width: 620px; margin: 0 auto; min-height: calc(100vh - 130px); display: flex; flex-direction: column; justify-content: center; padding: 1rem;">
    <!-- Header with Back Button -->
    <div class="d-flex align-items-center mb-4" style="gap: 1.25rem;">
        <a href="<?= url("/ekspedisi/" . strtolower($ekspedisi)) ?>" class="btn-back-circle">
            <i data-lucide="arrow-left" style="width: 22px; height: 22px;"></i>
        </a>
        <div style="flex: 1;">
            <h1 class="page-title mb-0" style="font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1e293b;">SERAH TERIMA & FOTO <?= e($ekspedisi) ?></h1>
            <div class="d-flex align-items-center flex-wrap gap-2 mt-1">
                <span class="badge bg-soft-primary text-primary px-2 py-1" style="font-size: 10px; font-weight: 700; border-radius: 4px;">PETUGAS: <?= e(auth()['username']) ?></span>
                <span style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;">
                    <i data-lucide="shield-check" style="width: 11px; height: 11px;"></i> PROTEKSI SHA-256 (POLO-SHA256-V1)
                </span>
                <?php if (!empty($targetInfo['has_manifest'])): ?>
                <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;" title="Manifest Excel <?= e($ekspedisi) ?> telah diimport di Hasil Ekspedisi">
                    <i data-lucide="file-check" style="width: 11px; height: 11px;"></i> MANIFEST EXCEL TERSEDIA
                </span>
                <?php else: ?>
                <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px;" title="Admin belum mengimpor file manifest di Hasil Ekspedisi">
                    <i data-lucide="alert-circle" style="width: 11px; height: 11px;"></i> MANIFEST EXCEL BELUM TERDATA
                </span>
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
        
        <div class="card-body" style="padding: 1.75rem 1.75rem 2.75rem 1.75rem; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <!-- Camera View -->
            <div id="cameraContent" style="width: 100%; max-width: 470px; display: flex; flex-direction: column; align-items: center;">
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
                
                <!-- Input Metadata Serah Terima (QTY, Kurir, Nopol) under Camera View -->
                <div class="w-100 mt-3" style="max-width: 470px;">
                    <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
                        <span class="d-flex align-items-center gap-1">
                            <i data-lucide="package" style="width: 15px; height: 15px; color: #3b82f6;"></i>
                            <span>Quantity (QTY) Fisik Paket:</span>
                        </span>
                        <span class="text-danger fw-bold" style="font-size: 10px;">*WAJIB DIISI</span>
                    </label>
                    <div class="input-group shadow-sm mb-2" style="border-radius: 12px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0 text-muted" style="border-color: #cbd5e1;">
                            <i data-lucide="hash" style="width: 16px; height: 16px;"></i>
                        </span>
                        <input type="number" class="form-control border-start-0 fw-bold fs-6 staff-qty-input" 
                               placeholder="Contoh: 100" min="1" required 
                               oninput="syncField('.staff-qty-input', this.value)"
                               style="border-color: #cbd5e1; height: 42px;">
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-7">
                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-1">
                                    <i data-lucide="user-check" style="width: 14px; height: 14px; color: #059669;"></i>
                                    <span>Nama Kurir:</span>
                                </span>
                                <span class="text-danger fw-bold" style="font-size: 9.5px;">*WAJIB</span>
                            </label>
                            <input type="text" class="form-control shadow-sm staff-kurir-input"
                                   placeholder="Contoh: Andi (SPX)"
                                   oninput="syncField('.staff-kurir-input', this.value)"
                                   style="border-radius: 10px; border-color: #cbd5e1; height: 40px; font-size: 13px; font-weight: 600;">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                <i data-lucide="truck" style="width: 14px; height: 14px; color: #64748b;"></i>
                                <span>No. Polisi:</span>
                            </label>
                            <input type="text" class="form-control shadow-sm staff-nopol-input"
                                   placeholder="B 1234 XYZ"
                                   oninput="syncField('.staff-nopol-input', this.value.toUpperCase())"
                                   style="border-radius: 10px; border-color: #cbd5e1; height: 40px; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                        </div>
                    </div>
                </div>

                <div style="width: 100% !important; display: flex !important; flex-direction: column !important; align-items: center !important; justify-content: center !important; margin-top: 1.5rem !important;">
                    <button class="btn-capture-main" id="btnCapture" style="margin: 0 auto !important; display: inline-flex !important;">
                        <div class="inner-circle"></div>
                    </button>
                    <div style="margin-top: 0.75rem !important; font-size: 11px !important; color: #475569 !important; font-weight: 800 !important; letter-spacing: 1.5px !important; text-transform: uppercase !important; text-align: center !important; width: 100% !important;">AMBIL FOTO BUKTI</div>
                </div>
            </div>

            <!-- Upload View -->
            <div id="uploadContent" style="display: none; width: 100%; max-width: 470px;">
                <div id="dropZone" class="upload-dropzone" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon-wrapper">
                        <i data-lucide="image-plus" style="width: 24px; height: 24px; color: #3b82f6;"></i>
                    </div>
                    <p style="font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; font-size: 14px;">Klik untuk Pilih Foto Serah Terima</p>
                    <p style="font-size: 11px; color: #94a3b8;">Format: JPG, PNG, WEBP (Maks 5MB)</p>
                    <input type="file" id="fileInput" accept="image/*" style="display: none;">
                </div>

                <!-- Input Metadata Serah Terima under Upload View -->
                <div class="w-100 mt-3">
                    <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
                        <span class="d-flex align-items-center gap-1">
                            <i data-lucide="package" style="width: 15px; height: 15px; color: #3b82f6;"></i>
                            <span>Quantity (QTY) Fisik Paket:</span>
                        </span>
                        <span class="text-danger fw-bold" style="font-size: 10px;">*WAJIB DIISI</span>
                    </label>
                    <div class="input-group shadow-sm mb-2" style="border-radius: 12px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0 text-muted" style="border-color: #cbd5e1;">
                            <i data-lucide="hash" style="width: 16px; height: 16px;"></i>
                        </span>
                        <input type="number" class="form-control border-start-0 fw-bold fs-6 staff-qty-input" 
                               placeholder="Contoh: 100" min="1" required 
                               oninput="syncField('.staff-qty-input', this.value)"
                               style="border-color: #cbd5e1; height: 42px;">
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-7">
                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-1">
                                    <i data-lucide="user-check" style="width: 14px; height: 14px; color: #059669;"></i>
                                    <span>Nama Kurir:</span>
                                </span>
                                <span class="text-danger fw-bold" style="font-size: 9.5px;">*WAJIB</span>
                            </label>
                            <input type="text" class="form-control shadow-sm staff-kurir-input"
                                   placeholder="Contoh: Andi (SPX)"
                                   oninput="syncField('.staff-kurir-input', this.value)"
                                   style="border-radius: 10px; border-color: #cbd5e1; height: 40px; font-size: 13px; font-weight: 600;">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                <i data-lucide="truck" style="width: 14px; height: 14px; color: #64748b;"></i>
                                <span>No. Polisi:</span>
                            </label>
                            <input type="text" class="form-control shadow-sm staff-nopol-input"
                                   placeholder="B 1234 XYZ"
                                   oninput="syncField('.staff-nopol-input', this.value.toUpperCase())"
                                   style="border-radius: 10px; border-color: #cbd5e1; height: 40px; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result View -->
            <div id="resultContent" style="display: none; width: 100%; max-width: 470px;">
                <div style="width: 100%; aspect-ratio: 4/3; border-radius: 12px; overflow: hidden; background: #000; border: 2px solid #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                    <img id="photoPreview" src="" style="width: 100%; height: 100%; object-fit: contain;">
                </div>

                <!-- Konfirmasi Metadata Serah Terima & SHA-256 -->
                <div class="w-100 mt-3">
                    <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
                        <span class="d-flex align-items-center gap-1">
                            <i data-lucide="package" style="width: 15px; height: 15px; color: #3b82f6;"></i>
                            <span>Konfirmasi Quantity (QTY) Fisik Paket:</span>
                        </span>
                        <span class="text-danger fw-bold" style="font-size: 10px;">*WAJIB DIISI</span>
                    </label>
                    <div class="input-group shadow-sm mb-2" style="border-radius: 12px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0 text-muted" style="border-color: #cbd5e1;">
                            <i data-lucide="hash" style="width: 16px; height: 16px;"></i>
                        </span>
                        <input type="number" class="form-control border-start-0 fw-bold fs-6 staff-qty-input text-primary" 
                               placeholder="Contoh: 100" min="1" required 
                               oninput="syncField('.staff-qty-input', this.value)"
                               style="border-color: #cbd5e1; height: 42px;">
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-7">
                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-1">
                                    <i data-lucide="user-check" style="width: 14px; height: 14px; color: #059669;"></i>
                                    <span>Nama Kurir Penjemput:</span>
                                </span>
                                <span class="text-danger fw-bold" style="font-size: 9.5px;">*WAJIB</span>
                            </label>
                            <input type="text" class="form-control shadow-sm staff-kurir-input"
                                   placeholder="Contoh: Andi (SPX)"
                                   oninput="syncField('.staff-kurir-input', this.value)"
                                   style="border-radius: 10px; border-color: #cbd5e1; height: 40px; font-size: 13px; font-weight: 600;">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                <i data-lucide="truck" style="width: 14px; height: 14px; color: #64748b;"></i>
                                <span>No. Polisi:</span>
                            </label>
                            <input type="text" class="form-control shadow-sm staff-nopol-input"
                                   placeholder="B 1234 XYZ"
                                   oninput="syncField('.staff-nopol-input', this.value.toUpperCase())"
                                   style="border-radius: 10px; border-color: #cbd5e1; height: 40px; font-size: 13px; font-weight: 700; text-transform: uppercase;">
                        </div>
                    </div>

                    <div style="margin-top: 8px; padding: 8px 10px; border-radius: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; font-size: 11px; color: #166534;">
                        <i data-lucide="lock" style="width: 12px; height: 12px; vertical-align: middle;"></i>
                        Data QTY, identitas kurir, waktu, dan biner foto akan dikunci menggunakan fungsi hash <strong>SHA-256</strong>.
                    </div>
                </div>

                <div style="margin-top: 1.25rem; display: flex; gap: 0.75rem; justify-content: center;">
                    <button class="btn btn-light" onclick="retakePhoto()" style="flex: 1; padding: 10px; border-radius: 10px; font-weight: 600; font-size: 13px; border: 1px solid #e2e8f0;">
                        <i data-lucide="refresh-ccw" style="width: 14px; height: 14px; margin-right: 6px;"></i> ULANGI
                    </button>
                    <button class="btn btn-success" id="btnSave" style="flex: 2; padding: 10px; border-radius: 10px; background: #10b981; border: none; color: white; font-weight: 700; font-size: 13px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);">
                        <i data-lucide="shield-check" style="width: 15px; height: 15px; margin-right: 6px;"></i> SIMPAN & SEGEL SHA-256
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
</style>

<script>
let stream = null;
let currentMode = 'camera';
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const photoPreview = document.getElementById('photoPreview');

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
                facingMode: 'environment',
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
        document.getElementById('cameraLoading').innerHTML = '<div class="p-3 text-center"><i data-lucide="camera-off" class="text-danger mb-2"></i><p style="font-size: 11px;" class="text-danger mb-0">Gagal akses kamera. Gunakan mode upload.</p></div>';
        if (window.lucide) lucide.createIcons();
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(t => t.stop());
        stream = null;
    }
}

function getSyncedValue(selector) {
    let val = '';
    document.querySelectorAll(selector).forEach(el => {
        if (el.value && !val) val = el.value.trim();
    });
    return val;
}

// Camera Capture — apply label before preview
document.getElementById('btnCapture').onclick = function() {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    applyLabel(canvas, '<?= e($ekspedisi) ?>', '<?= addslashes(auth()["username"] ?? "Petugas") ?>');
    
    photoPreview.src = canvas.toDataURL('image/webp', 0.8);
    document.getElementById('cameraContent').style.display = 'none';
    document.getElementById('resultContent').style.display = 'block';
    stopCamera();
    if (window.lucide) lucide.createIcons();
};

// File Upload — apply label before preview
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
                
                applyLabel(canvas, '<?= e($ekspedisi) ?>', '<?= addslashes(auth()["username"] ?? "Petugas") ?>');
                
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

/**
 * Apply Professional Watermark Label onto Canvas
 */
function applyLabel(canvas, ekspedisi, petugas) {
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
    
    const kurirVal = getSyncedValue('.staff-kurir-input');
    const textMain = kurirVal ? `${ekspedisi} - ${petugas} (Kurir: ${kurirVal})` : `${ekspedisi} - ${petugas}`;
    ctx.fillText(textMain, padding, height - barHeight + padding);
    
    const now = new Date();
    const timeString = now.toLocaleString('id-ID', { 
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
    ctx.font = `${fontSize * 0.8}px sans-serif`;
    ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
    ctx.fillText(timeString, padding, height - (fontSize * 1.5));
    
    const brand = 'PT. POLO SPORT INDONESIA';
    ctx.font = `italic ${fontSize * 0.6}px sans-serif`;
    const brandWidth = ctx.measureText(brand).width;
    ctx.fillText(brand, width - brandWidth - padding, height - (fontSize * 1.2));
}

function retakePhoto() {
    if (currentMode === 'camera') switchTab('camera'); else switchTab('upload');
}

// Sinkronisasi nilai input antar tab (Kamera, Upload, Preview)
function syncField(selector, val) {
    document.querySelectorAll(selector).forEach(input => {
        if (input.value !== val) {
            input.value = val;
        }
    });
}

document.getElementById('btnSave').onclick = function(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    const qtyVal = getSyncedValue('.staff-qty-input');
    const kurirVal = getSyncedValue('.staff-kurir-input');
    const nopolVal = getSyncedValue('.staff-nopol-input') || '-';

    if (!qtyVal || parseInt(qtyVal) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'QTY Wajib Diisi',
            text: 'Silakan masukkan Quantity (QTY) fisik paket terlebih dahulu sebelum menyimpan!',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Mengerti'
        });
        return;
    }

    if (!kurirVal) {
        Swal.fire({
            icon: 'warning',
            title: 'Nama Kurir Wajib Diisi',
            text: 'Silakan masukkan Nama Kurir Penjemput untuk diikat ke dalam segel integritas SHA-256!',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Mengerti'
        });
        return;
    }

    Swal.fire({ 
        title: 'Memvalidasi QTY & Menyegel SHA-256...', 
        text: 'Mencocokkan kuantitas fisik & menerbitkan segel kriptografi POLO-SHA256-V1...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading() 
    });
    
    const formData = new FormData();
    formData.append('ekspedisi', '<?= e($ekspedisi) ?>');
    formData.append('qty', qtyVal);
    formData.append('nama_kurir', kurirVal);
    formData.append('no_polisi_kurir', nopolVal);
    formData.append('_csrf_token', '<?= csrf_token() ?>');
    formData.append('photo', photoPreview.src);

    fetch('<?= url("/ekspedisi/store") ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const vHash = res.verification_hash || '-';
            const pHash = res.photo_hash || '-';
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan & Tersegel SHA-256!',
                html: `
                    <div style="text-align: left; font-size: 13px; color: #334155;">
                        <p style="margin-bottom: 10px;">${res.message || 'Bukti serah terima berhasil disimpan.'}</p>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px;">
                            <div style="font-size: 10.5px; font-weight: 800; color: #059669; text-transform: uppercase; margin-bottom: 3px;">Segel Transaksi (SHA-256 Seal):</div>
                            <code style="display: block; word-break: break-all; font-size: 11px; color: #0f172a; margin-bottom: 8px;">${vHash}</code>
                            <div style="font-size: 10.5px; font-weight: 800; color: #2563eb; text-transform: uppercase; margin-bottom: 3px;">Hash Biner Foto Fisik:</div>
                            <code style="display: block; word-break: break-all; font-size: 11px; color: #334155;">${pHash}</code>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Lihat Riwayat & Audit',
                confirmButtonColor: '#10b981'
            }).then(() => {
                window.location.href = '<?= url("/ekspedisi/" . strtolower($ekspedisi)) ?>';
            });
        } else {
            const namaEkspedisi = '<?= e($ekspedisi) ?>';
            const waPhone = '6285772700656';
            const waText = `Hallo Mba Putri,\nTotal quantity ${namaEkspedisi} yang di input oleh saya sejumlah ${qtyVal} apakah sudah sesuai?\nTerima Kasih.`;
            const waLink = `https://wa.me/${waPhone}?text=${encodeURIComponent(waText)}`;

            Swal.fire({
                icon: 'warning',
                title: 'Validasi Gagal!',
                html: `
                    <div style="font-size: 14.5px; color: #475569; line-height: 1.6; margin-top: 6px;">
                        ${res.message || 'Data paket tidak bisa berhasil disimpan. Paket kurang, mohon dicek kembali dan menghubungi <strong>Mba Putri</strong>. Terima kasih.'}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Hubungi WA',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#25D366',
                cancelButtonColor: '#ef4444',
                reverseButtons: false,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    const waWindow = window.open(waLink, '_blank', 'noopener,noreferrer');
                    if (waWindow) {
                        waWindow.opener = null;
                    }
                }
            });
        }
    })
    .catch(e => {
        console.error(e);
        Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan foto.', 'error');
    });
};
</script>

<div class="animate__animated animate__fadeIn">
    <!-- Header Section (Topbar / Page Header) -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 pt-1">
        <!-- Sisi Kiri: Judul Utama & Badge Integritas SHA-256 -->
        <div class="d-flex flex-column gap-1.5">
            <h1 class="page-title text-uppercase fw-bold mb-0 text-slate-800" style="font-size: 1.25rem; letter-spacing: -0.2px; color: #1e293b; line-height: 1.25;">
                Logistik &amp; Segel SHA-256 Ekspedisi
            </h1>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge-integrity-sha256">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px; color: #059669; flex-shrink: 0;"></i>
                    <span>Integritas Data Terlindungi Algoritma SHA-256 (POLO-SHA256-V1)</span>
                </span>
            </div>
        </div>

        <!-- Sisi Kanan: Kelompok Tombol Aksi & Mode Peran -->
        <div class="header-action-group d-flex align-items-center flex-wrap gap-3" style="gap: 12px;">
            <?php if (!empty($photos)): ?>
            <button type="button" id="btnVerifyBatchSha256" class="btn btn-dark shadow-sm fw-bold d-inline-flex align-items-center gap-2 px-3.5 py-2" style="font-size: 12.5px; border-radius: 10px; background: #0f172a; border-color: #0f172a; white-space: nowrap;">
                <i data-lucide="file-check-2" style="width: 15px; height: 15px;"></i>
                <span>AUDIT SEMUA SHA-256</span>
            </button>
            <?php endif; ?>

            <?php if (!isAdmin()): ?>
            <input type="file" id="manualUploadHeader" class="d-none" accept="image/*">
            <button type="button" class="btn btn-outline-primary shadow-sm fw-bold d-inline-flex align-items-center gap-2 px-3.5 py-2" onclick="document.getElementById('manualUploadHeader').click()" style="font-size: 12.5px; border-radius: 10px; white-space: nowrap;">
                <i data-lucide="upload" style="width: 15px; height: 15px;"></i>
                <span>UPLOAD MANUAL</span>
            </button>
            
            <a href="<?= url("/ekspedisi/" . strtolower($ekspedisi) . "/capture") ?>" class="btn btn-primary shadow-sm fw-bold d-inline-flex align-items-center gap-2 px-3.5 py-2" style="font-size: 12.5px; border-radius: 10px; white-space: nowrap;">
                <i data-lucide="camera" style="width: 15px; height: 15px;"></i>
                <span>AMBIL FOTO</span>
            </a>
            <?php else: ?>
            <div class="mode-monitoring-admin-pill">
                <i data-lucide="eye" style="width: 15px; height: 15px; color: #2563eb; flex-shrink: 0;"></i>
                <span>MODE MONITORING ADMIN</span>
            </div>
            <a href="<?= url('/hasil-ekspedisi') ?>" class="btn-pusat-manifest" title="Kelola data manifest Excel di Hasil Ekspedisi">
                <i data-lucide="file-spreadsheet" style="width: 16px; height: 16px; color: #059669; flex-shrink: 0;"></i>
                <span>Pusat Manifest Excel</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expedition Tabs Navigation Sub-bar -->
    <div class="custom-tabs-wrapper mb-4">
        <div class="custom-tabs shadow-sm">
            <?php 
            $tabs = ['SPX', 'LEX', 'JNE', 'JNT', 'ID', 'NINJA', 'SICEPAT'];
            foreach ($tabs as $tab): 
                $isActiveTab = ($tab === $ekspedisi);
                $tabLower = strtolower($tab);
            ?>
                <a href="<?= url("/ekspedisi/$tabLower") ?>" class="tab-item <?= $isActiveTab ? 'active' : '' ?>">
                    <span class="tab-dot dot-<?= $tabLower ?>"></span>
                    <span><?= $tab ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (isAdmin()): ?>
    <!-- Admin Only: Monitoring & Sinkronisasi Otomatis Target QTY dari Hasil Ekspedisi -->
    <?php
    $hasManifest = !empty($targetInfo['has_manifest']);
    $manifestFiles = $targetInfo['files'] ?? [];
    $firstFile = $manifestFiles[0] ?? null;
    $firstFileName = $firstFile['nama_file'] ?? '';
    $firstFileTime = !empty($firstFile['tanggal_upload']) ? date('H:i', strtotime($firstFile['tanggal_upload'])) : '';
    $moreFilesCount = count($manifestFiles) - 1;
    ?>
    <div class="card border-0 shadow-sm mb-3 mb-md-4" style="border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); border: 1px solid <?= $hasManifest ? '#bbf7d0' : '#fed7aa' ?> !important;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: <?= $hasManifest ? '#10b981' : '#f59e0b' ?>; color: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); flex-shrink: 0;">
                        <i data-lucide="<?= $hasManifest ? 'check-circle-2' : 'alert-triangle' ?>" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h6 class="mb-0 fw-bold text-dark fs-6">Target Serah Terima <?= e($ekspedisi) ?></h6>
                            <?php if ($hasManifest): ?>
                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1" style="font-size: 10px; font-weight: 700; letter-spacing: 0.3px;">
                                    <i data-lucide="link" style="width: 11px; height: 11px; vertical-align: middle;"></i> OTOMATIS DARI MANIFEST HASIL EKSPEDISI
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1" style="font-size: 10px; font-weight: 700; letter-spacing: 0.3px;">
                                    BELUM ADA MANIFEST EXCEL
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-danger text-white rounded-pill px-2 py-1" style="font-size: 9.5px; font-weight: 700;">KHUSUS ADMIN</span>
                        </div>
                        <div class="text-muted small mb-0 mt-1 d-flex align-items-center gap-2 flex-wrap">
                            <?php if ($hasManifest): ?>
                                <span>
                                    Sumber: <strong><?= e($firstFileName) ?><?= $moreFilesCount > 0 ? " (+{$moreFilesCount} file lainnya)" : '' ?></strong> (Upload <?= $firstFileTime ?> WIB)
                                </span>
                                <span>&bull;</span>
                                <a href="<?= url('/hasil-ekspedisi?ekspedisi=' . urlencode($ekspedisi) . '&start_date=' . urlencode($tanggal) . '&end_date=' . urlencode($tanggal)) ?>" class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center gap-1" target="_blank" title="Buka data manifest di Hasil Ekspedisi">
                                    <span>Lihat Manifest Resi</span>
                                    <i data-lucide="external-link" style="width: 12px; height: 12px;"></i>
                                </a>
                            <?php else: ?>
                                <span>
                                    Belum ada berkas Excel <?= e($ekspedisi) ?> di Hasil Ekspedisi pada <strong><?= date('d M Y', strtotime($tanggal)) ?></strong>. Staff membutuhkan acuan QTY untuk menyimpan bukti foto.
                                </span>
                                <a href="<?= url('/hasil-ekspedisi') ?>" class="fw-bold text-success text-decoration-none d-inline-flex align-items-center gap-1" title="Import file Excel di Hasil Ekspedisi">
                                    <span>+ Import Excel di Hasil Ekspedisi</span>
                                    <i data-lucide="arrow-up-right" style="width: 12px; height: 12px;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Display QTY & Form Override Opsional -->
                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    <div style="background: white; border: 1px solid #cbd5e1; border-radius: 12px; padding: 6px 14px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.03);">
                        <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Target QTY Sah</div>
                        <div style="font-size: 19px; font-weight: 800; color: #0f172a; line-height: 1.1;" id="displayTargetQtyVal">
                            <?= (int)$targetQty ?> <span style="font-size: 11px; font-weight: 600; color: #64748b;">Paket</span>
                        </div>
                    </div>

                    <form id="formAdminTargetQty" class="ajax-form d-flex align-items-center gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="ekspedisi" value="<?= e($ekspedisi) ?>">
                        <input type="hidden" name="tanggal" id="adminTargetDateInput" value="<?= e($tanggal ?? date('Y-m-d')) ?>">
                        
                        <div class="input-group shadow-sm" style="border-radius: 10px; overflow: hidden; max-width: 175px;">
                            <span class="input-group-text bg-white border-end-0 text-muted" style="border-color: #cbd5e1; font-size: 11px;" title="Override target manual jika diperlukan">
                                <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                            </span>
                            <input type="number" name="total_qty" id="adminTotalQtyInput" class="form-control border-start-0 text-center fw-bold text-primary" 
                                   value="<?= (int)($targetQty ?? 0) ?>" min="0" placeholder="0" required 
                                   title="Total QTY (Otomatis dari manifest, dapat diubah jika darurat)"
                                   style="border-color: #cbd5e1; height: 36px; font-size: 13px;">
                            <button type="submit" class="btn btn-outline-secondary px-2 d-inline-flex align-items-center gap-1" id="btnSaveAdminQty" title="Simpan target override manual" style="font-size: 11.5px; font-weight: 700;">
                                <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm overflow-hidden mb-4" style="border-radius: 16px;">
        <div class="card-header bg-white py-3 py-md-4 px-3 px-md-4 border-bottom-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary d-flex align-items-center justify-content-center">
                        <i data-lucide="database" size="18"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0 fw-bold text-dark fs-6 fs-md-5">Riwayat Serah Terima <?= e($ekspedisi) ?></h5>
                            <?php if ($tanggal === date('Y-m-d')): ?>
                                <span class="badge bg-soft-success text-success px-2 py-1 rounded-pill fw-bold" style="font-size: 10px;">HARI INI</span>
                            <?php else: ?>
                                <span class="badge bg-soft-warning text-warning px-2 py-1 rounded-pill fw-bold" style="font-size: 10px;">HISTORI REPORT</span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted" style="font-size: 11px;">
                            Data: <strong><?= date('d M Y', strtotime($tanggal)) ?></strong> • Klik tombol <strong>Audit</strong> untuk memverifikasi keaslian biner foto & metadata via SHA-256
                        </small>
                    </div>
                </div>

                <!-- Date Picker Filter -->
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <form action="<?= url('/ekspedisi/' . strtolower($ekspedisi)) ?>" method="GET" class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-sm shadow-sm" style="border-radius: 10px; overflow: hidden; width: auto;">
                            <span class="input-group-text bg-white border-end-0 text-muted" style="border-color: #cbd5e1;">
                                <i data-lucide="calendar" style="width: 14px; height: 14px;"></i>
                            </span>
                            <input type="date" name="tanggal" class="form-control border-start-0 fw-bold text-dark" 
                                   value="<?= e($tanggal) ?>" 
                                   onchange="this.form.submit()" 
                                   title="Pilih tanggal untuk melihat laporan"
                                   style="border-color: #cbd5e1; height: 33px; font-size: 12px;">
                        </div>
                        <?php if ($tanggal !== date('Y-m-d')): ?>
                            <a href="<?= url('/ekspedisi/' . strtolower($ekspedisi)) ?>" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 10px; height: 33px; display: inline-flex; align-items: center; gap: 4px; font-size: 12px; white-space: nowrap;" title="Kembali ke data hari ini">
                                <i data-lucide="rotate-ccw" style="width: 13px; height: 13px;"></i> Hari Ini
                            </a>
                        <?php endif; ?>
                    </form>

                    <?php if (isAdmin()): ?>
                        <span class="badge bg-soft-info text-info px-3 py-2 rounded-pill fw-bold" id="badgeTargetAdminQty" style="font-size: 11px;">
                            TARGET: <?= (int)($targetQty ?? 0) ?> QTY <?= $hasManifest ? '(MANIFEST)' : '' ?>
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill fw-bold" style="font-size: 11px;">
                        <?= count($photos) ?> TOTAL BUKTI
                    </span>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 ekspedisi-table">
                <thead style="background-color: #f8fafc; border-bottom: 1px solid #edf2f7;">
                    <tr>
                        <th class="ps-3 ps-md-4 py-3 text-uppercase small fw-bold text-muted text-center col-foto">Foto</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted text-center col-petugas">Petugas & Kurir</th>
                        <th class="py-3 text-uppercase small fw-bold text-muted text-center col-datetime">Hari, Tanggal & Jam</th>
                        <?php if (isAdmin()): ?>
                        <th class="py-3 text-uppercase small fw-bold text-muted text-center col-qty">QTY Validasi</th>
                        <?php endif; ?>
                        <th class="py-3 text-uppercase small fw-bold text-muted text-center col-status">Segel Integritas SHA-256</th>
                        <th class="pe-3 pe-md-4 py-3 text-uppercase small fw-bold text-muted text-center col-aksi">Aksi & Audit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($photos)): ?>
                        <tr>
                            <td colspan="<?= isAdmin() ? 6 : 5 ?>" class="text-center py-5">
                                <div class="py-5">
                                    <div class="bg-light d-inline-flex p-4 rounded-circle mb-3">
                                        <i data-lucide="image-off" size="40" class="text-muted opacity-50"></i>
                                    </div>
                                    <h6 class="text-dark fw-bold">Belum ada data bukti serah terima pada <?= date('d M Y', strtotime($tanggal)) ?>.</h6>
                                    <p class="text-muted small">Tabel otomatis kosong setiap hari baru (00:00). <?= isAdmin() ? 'Data serah terima fisik dicatat oleh akun Petugas/Staff saat kurir ekspedisi menjemput paket.' : 'Mulai dokumentasi dengan tombol Ambil Foto di atas atau pilih tanggal lain.' ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($photos as $photo): 
                            $fotoDate    = date('d/m/Y', strtotime($photo['created_at']));
                            $fotoTime    = $photo['waktu'] ?? date('H:i:s', strtotime($photo['created_at']));
                            $captionText = e($photo['nama_ekspedisi']) . ' • ' . $photo['hari'] . ', ' . $fotoDate . ' ' . $fotoTime;
                            $verStatus   = strtoupper((string)($photo['status_verifikasi'] ?? 'VALID'));
                            $hashFull    = (string)($photo['hash_blockchain'] ?? '');
                            $hashShort   = $hashFull !== '' ? (substr($hashFull, 0, 10) . '...' . substr($hashFull, -6)) : 'Tanpa Hash';
                            $kurirName   = trim((string)($photo['nama_kurir'] ?? '-'));
                            $nopolKurir  = trim((string)($photo['no_polisi_kurir'] ?? '-'));
                        ?>
                            <tr id="handoverRow_<?= (int)$photo['id'] ?>">
                                <td class="ps-3 ps-md-4">
                                    <div class="d-flex justify-content-center">
                                        <div class="photo-thumbnail-ekspedisi shadow-sm" onclick="showPreview('<?= url('/' . $photo['foto']) ?>', '<?= $captionText ?>', '<?= e($photo['nama_petugas']) ?>')">
                                            <img src="<?= url('/' . $photo['foto']) ?>" alt="Photo">
                                            <div class="photo-info-overlay">
                                                <span class="photo-badge-ekspedisi"><?= e($photo['nama_ekspedisi']) ?></span>
                                                <span class="photo-datetime"><?= $fotoDate ?>, <?= $fotoTime ?></span>
                                            </div>
                                            <div class="photo-hover-overlay">
                                                <i data-lucide="zoom-in" size="16"></i>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px;">
                                        <span class="fw-bold text-dark"><?= e($photo['nama_petugas']) ?></span>
                                        <?php if ($kurirName !== '' && $kurirName !== '-'): ?>
                                            <span style="font-size: 11px; color: #059669; font-weight: 700; background: #ecfdf5; padding: 1px 8px; border-radius: 999px; border: 1px solid #d1fae5;">
                                                Kurir: <?= e($kurirName) ?><?= ($nopolKurir !== '' && $nopolKurir !== '-') ? ' • ' . e($nopolKurir) : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size: 10.5px; color: #94a3b8;">Kurir: -</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div style="line-height: 1.4;">
                                        <div class="fw-bold text-dark"><?= e($photo['hari']) ?>, <?= date('d M Y', strtotime($photo['created_at'])) ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= e($fotoTime) ?> WIB</div>
                                    </div>
                                </td>
                                <?php if (isAdmin()): ?>
                                <td class="text-center">
                                    <span class="badge bg-soft-primary text-primary fw-bold px-2 py-1" style="font-size: 12px; border-radius: 6px;">
                                        <?= (int)($photo['qty_staff'] ?? 0) ?> Paket
                                    </span>
                                </td>
                                <?php endif; ?>
                                <td class="text-center" id="statusBadgeCell_<?= (int)$photo['id'] ?>">
                                    <div class="d-inline-flex flex-column align-items-center gap-1">
                                        <?php if ($verStatus === 'TAMPERED'): ?>
                                            <span class="badge-status-tampered">
                                                <i data-lucide="alert-triangle" size="12"></i> TAMPERED / RUSAK
                                            </span>
                                        <?php elseif ($verStatus === 'LEGACY'): ?>
                                            <span class="badge-status-legacy">
                                                <i data-lucide="archive" size="12"></i> LEGACY (PRA-V1)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status-valid">
                                                <i data-lucide="shield-check" size="12"></i> VALID • SHA-256
                                            </span>
                                        <?php endif; ?>
                                        <code style="font-size: 10px; color: #64748b; background: #f8fafc; padding: 1px 6px; border-radius: 4px; border: 1px solid #e2e8f0;" title="<?= e($hashFull) ?>">
                                            <?= e($hashShort) ?>
                                        </code>
                                    </div>
                                </td>
                                <td class="pe-3 pe-md-4 text-center">
                                    <div class="d-flex justify-content-center align-items-center gap-2">
                                        <button type="button" class="btn-verify-sha256" data-id="<?= (int)$photo['id'] ?>" title="Audit & Verifikasi Ulang Segel SHA-256">
                                            <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                                            <span>Audit</span>
                                        </button>
                                        <button class="btn-action-icon btn-view" title="Lihat Foto" onclick="showPreview('<?= url('/' . $photo['foto']) ?>', '<?= $captionText ?>', '<?= e($photo['nama_petugas']) ?>')">
                                            <i data-lucide="eye" size="16"></i>
                                        </button>
                                        <?php if (isAdmin()): ?>
                                            <form action="<?= url("/ekspedisi/delete/{$photo['id']}") ?>" method="POST" class="ajax-form d-inline">
                                                <?= csrf_field() ?>
                                                <button type="button" class="btn-action-icon btn-delete btn-soft-delete-ekspedisi" title="Hapus">
                                                    <i data-lucide="trash-2" size="16"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Preview Foto -->
<div id="previewModal" class="modal-overlay" onclick="closePreview()">
    <div class="modal-container" onclick="event.stopPropagation()">
        <button class="modal-close-btn" onclick="closePreview()">
            <i data-lucide="x"></i>
        </button>
        <div class="modal-img-wrapper">
            <img id="modalImg" src="" alt="Preview">
            <div class="modal-photo-info">
                <div class="modal-info-top">
                    <span id="modalEkspedisiBadge" class="modal-ekspedisi-badge"></span>
                </div>
                <div class="modal-info-bottom">
                    <div class="modal-info-left">
                        <span id="modalPetugas" class="modal-petugas-text"></span>
                        <span id="modalDateTime" class="modal-datetime-text"></span>
                    </div>
                    <span class="modal-company-text">PT. POLO SPORT INDONESIA</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Audit Forensik SHA-256 -->
<div id="sha256AuditModal" class="modal-overlay" onclick="closeAuditModal()">
    <div class="audit-modal-card" onclick="event.stopPropagation()">
        <div style="background: #0f172a; color: white; padding: 1.15rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.2); color: #34d399; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h5 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #fff;">Audit Keaslian Data & Foto (SHA-256)</h5>
                    <div style="font-size: 11.5px; color: #94a3b8;">Protokol Verifikasi Integritas Kriptografis 256-Bit (POLO-SHA256-V1)</div>
                </div>
            </div>
            <button type="button" onclick="closeAuditModal()" style="background: rgba(255,255,255,0.1); border: none; color: #fff; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <div id="auditModalBody" style="padding: 1.5rem; max-height: 78vh; overflow-y: auto; background: #f8fafc;">
            <!-- Diisi secara dinamis oleh JS -->
        </div>
    </div>
</div>

<style>
    /* Header Topbar & Action Button Styling */
    .badge-integrity-sha256 {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 11px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .header-action-group {
        display: inline-flex;
        align-items: center;
        gap: 12px !important;
    }

    .mode-monitoring-admin-pill {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.2px;
        padding: 8px 15px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        white-space: nowrap;
        margin-right: 0 !important;
    }

    .btn-pusat-manifest {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        font-size: 12.5px;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 10px;
        text-decoration: none;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: all 0.18s ease;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-pusat-manifest:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #0f172a;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.06);
    }

    /* Sub-bar Navigasi Tab Ekspedisi */
    .custom-tabs-wrapper { 
        width: 100%;
        overflow-x: auto; 
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding-bottom: 2px; 
    }
    .custom-tabs-wrapper::-webkit-scrollbar { display: none; }
    
    .custom-tabs { 
        background: #ffffff !important; 
        border-radius: 12px !important; 
        padding: 5px !important; 
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        display: inline-flex !important; 
        align-items: center !important;
        gap: 6px !important; 
        min-width: max-content; 
    }
    
    .custom-tabs .tab-item { 
        padding: 7px 15px !important; 
        border-radius: 8px !important; 
        color: #475569 !important; 
        text-decoration: none !important; 
        font-weight: 700 !important; 
        font-size: 12px !important; 
        display: inline-flex !important; 
        align-items: center !important; 
        gap: 7px !important; 
        transition: all 0.18s ease !important; 
        border: 1px solid transparent !important; 
        white-space: nowrap !important;
        background: transparent !important;
        line-height: 1.2 !important;
    }
    .custom-tabs .tab-item:hover { 
        background: #f1f5f9 !important; 
        color: #0f172a !important; 
        border-color: #e2e8f0 !important; 
    }
    .custom-tabs .tab-item.active { 
        background: #2563eb !important; 
        color: #ffffff !important; 
        border-color: #2563eb !important;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.28) !important; 
    }
    
    .tab-dot { 
        width: 8px !important; 
        height: 8px !important; 
        border-radius: 50% !important; 
        flex-shrink: 0 !important;
        display: inline-block !important;
        transition: all 0.18s ease !important;
    }
    .dot-spx { background: #ff5722 !important; } 
    .dot-lex { background: #0284c7 !important; } 
    .dot-jne { background: #dc2626 !important; }
    .dot-jnt { background: #f59e0b !important; } 
    .dot-id { background: #ef4444 !important; } 
    .dot-ninja { background: #0f172a !important; }
    .dot-sicepat { background: #ea580c !important; }
    .custom-tabs .tab-item.active .tab-dot { 
        background: #ffffff !important; 
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .ekspedisi-table {
        min-width: 760px;
        width: 100%;
        margin-bottom: 0;
    }
    .ekspedisi-table th,
    .ekspedisi-table td {
        white-space: nowrap;
        vertical-align: middle;
    }
    .col-foto { width: 100px; min-width: 90px; }
    .col-petugas { min-width: 140px; }
    .col-datetime { width: 200px; min-width: 180px; }
    .col-status { width: 190px; min-width: 175px; }
    .col-aksi { width: 160px; min-width: 150px; }

    .photo-thumbnail-ekspedisi { 
        width: 80px; height: 80px; border-radius: 12px; overflow: hidden; 
        position: relative; cursor: pointer; border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .photo-thumbnail-ekspedisi:hover { 
        border-color: #3b82f6; transform: scale(1.05); 
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
    }
    .photo-thumbnail-ekspedisi img { width: 100%; height: 100%; object-fit: cover; }

    .photo-info-overlay {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.85));
        padding: 16px 5px 4px 5px;
        display: flex; flex-direction: column; align-items: flex-start; gap: 1px;
    }
    .photo-badge-ekspedisi {
        background: #3b82f6; color: #fff;
        font-size: 7px; font-weight: 800; letter-spacing: 0.5px;
        padding: 1px 5px; border-radius: 3px;
        text-transform: uppercase;
    }
    .photo-datetime {
        color: rgba(255,255,255,0.9);
        font-size: 6.5px; font-weight: 600;
    }

    .photo-hover-overlay { 
        position: absolute; inset: 0; background: rgba(0,0,0,0.4); 
        display: flex; align-items: center; justify-content: center; 
        color: #fff; opacity: 0; transition: opacity 0.2s; 
    }
    .photo-thumbnail-ekspedisi:hover .photo-hover-overlay { opacity: 1; }

    /* Status Badges */
    .badge-status-valid {
        background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px;
        font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #a7f3d0;
    }
    .badge-status-legacy {
        background: #f1f5f9; color: #475569; padding: 4px 12px; border-radius: 20px;
        font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #cbd5e1;
    }
    .badge-status-tampered {
        background: #fef2f2; color: #dc2626; padding: 4px 12px; border-radius: 20px;
        font-size: 10px; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; border: 1px solid #fecaca;
    }

    /* Action Buttons */
    .btn-verify-sha256 {
        height: 34px; padding: 0 12px; border-radius: 8px;
        background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
        font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;
        cursor: pointer; transition: all 0.2s;
    }
    .btn-verify-sha256:hover {
        background: #2563eb; color: #fff; border-color: #2563eb;
    }
    .btn-action-icon { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; background: #fff; }
    .btn-view { color: #64748b; } .btn-view:hover { background: #f1f5f9; color: #1e293b; }
    .btn-delete { color: #ef4444; } .btn-delete:hover { background: #fee2e2; border-color: #fecaca; }

    /* Modal Styling */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center; padding: 20px; }
    .modal-overlay.show { display: flex; }
    .modal-container { position: relative; max-width: 900px; width: 100%; animation: zoomIn 0.25s ease-out; }
    .audit-modal-card { width: 720px; max-width: 96%; border-radius: 20px; overflow: hidden; background: #fff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.45); animation: zoomIn 0.25s ease-out; }
    .modal-img-wrapper { border-radius: 20px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); background: #000; position: relative; }
    .modal-img-wrapper img { width: auto; max-width: 100%; max-height: 80vh; display: block; margin: 0 auto; object-fit: contain; }
    .modal-close-btn { position: absolute; top: -15px; right: -15px; width: 40px; height: 40px; border-radius: 50%; background: #fff; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 10; }

    .modal-photo-info {
        position: absolute; inset: 0;
        display: flex; flex-direction: column; justify-content: space-between;
        pointer-events: none;
    }
    .modal-info-top { padding: 16px 20px; display: flex; justify-content: flex-start; }
    .modal-ekspedisi-badge {
        background: rgba(59, 130, 246, 0.9); color: #fff;
        font-size: 13px; font-weight: 800; letter-spacing: 1px;
        padding: 6px 16px; border-radius: 8px;
        text-transform: uppercase;
        backdrop-filter: blur(4px);
    }
    .modal-info-bottom {
        background: linear-gradient(transparent, rgba(0,0,0,0.75));
        padding: 40px 20px 16px 20px;
        display: flex; justify-content: space-between; align-items: flex-end;
    }
    .modal-info-left { display: flex; flex-direction: column; gap: 2px; }
    .modal-petugas-text { color: #fff; font-size: 16px; font-weight: 700; }
    .modal-datetime-text { color: rgba(255,255,255,0.85); font-size: 12px; font-weight: 500; }
    .modal-company-text { color: rgba(255,255,255,0.7); font-size: 11px; font-weight: 600; letter-spacing: 0.5px; }

    @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    @media (max-width: 768px) {
        .page-title { font-size: 1.35rem !important; }
        .photo-thumbnail-ekspedisi { width: 72px; height: 72px; }
    }
</style>

<script>
const IS_ADMIN_USER = <?= isAdmin() ? 'true' : 'false' ?>;
const CSRF_TOKEN_VAL = '<?= csrf_token() ?>';

function escapeHtmlStr(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showPreview(src, caption, petugas) {
    const modal = document.getElementById('previewModal');
    const img = document.getElementById('modalImg');
    const parts = caption.split(' • ');
    document.getElementById('modalEkspedisiBadge').innerText = parts[0] || '';
    document.getElementById('modalPetugas').innerText = 'Petugas: ' + (petugas || '-');
    document.getElementById('modalDateTime').innerText = parts[1] || caption;
    img.src = src;
    modal.classList.add('show');
}

function closePreview() {
    document.getElementById('previewModal').classList.remove('show');
}

function closeAuditModal() {
    document.getElementById('sha256AuditModal').classList.remove('show');
}

function updateRowStatusBadge(id, status, hashFull) {
    const cell = document.getElementById('statusBadgeCell_' + id);
    if (!cell) return;
    const st = String(status || 'VALID').toUpperCase();
    const shortHash = hashFull && hashFull.length > 18
        ? (hashFull.substring(0, 10) + '...' + hashFull.substring(hashFull.length - 6))
        : (hashFull || '-');

    let badgeHtml = '';
    if (st === 'TAMPERED' || st === 'FILE_MISSING') {
        badgeHtml = '<span class="badge-status-tampered"><i data-lucide="alert-triangle" size="12"></i> TAMPERED / RUSAK</span>';
    } else if (st === 'LEGACY') {
        badgeHtml = '<span class="badge-status-legacy"><i data-lucide="archive" size="12"></i> LEGACY (PRA-V1)</span>';
    } else {
        badgeHtml = '<span class="badge-status-valid"><i data-lucide="shield-check" size="12"></i> VALID • SHA-256</span>';
    }

    cell.innerHTML = `
        <div class="d-inline-flex flex-column align-items-center gap-1">
            ${badgeHtml}
            <code style="font-size: 10px; color: #64748b; background: #f8fafc; padding: 1px 6px; border-radius: 4px; border: 1px solid #e2e8f0;" title="${escapeHtmlStr(hashFull)}">${escapeHtmlStr(shortHash)}</code>
        </div>
    `;
    if (window.lucide) lucide.createIcons();
}

// Handler Klik Tombol Audit Tunggal SHA-256
document.querySelectorAll('.btn-verify-sha256').forEach(btn => {
    btn.addEventListener('click', function () {
        const fotoId = this.getAttribute('data-id');
        const modal = document.getElementById('sha256AuditModal');
        const body = document.getElementById('auditModalBody');

        body.innerHTML = `
            <div style="text-align: center; padding: 2.5rem 1rem;">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <div style="font-weight: 700; color: #1e293b;">Menghitung Ulang Hash SHA-256...</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Memeriksa biner berkas foto fisik & merekonstruksi string kanonikal POLO-SHA256-V1</div>
            </div>
        `;
        modal.classList.add('show');

        const fd = new FormData();
        fd.append('_csrf_token', CSRF_TOKEN_VAL);

        fetch('<?= url("/ekspedisi/verify/") ?>' + fotoId, {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (!res || !res.success) {
                body.innerHTML = `<div class="alert alert-danger mb-0">${escapeHtmlStr(res?.message || 'Gagal melakukan audit SHA-256.')}</div>`;
                return;
            }

            const v = res.verification || {};
            const rec = res.record || {};
            const logs = res.audit_logs || [];
            const st = String(v.status || 'TAMPERED').toUpperCase();

            updateRowStatusBadge(fotoId, st === 'FILE_MISSING' ? 'TAMPERED' : st, v.recorded_hash || '');

            let bannerBg = '#ecfdf5', bannerBdr = '#6ee7b7', bannerText = '#065f46', bannerTitle = 'TERVERIFIKASI SAH (VALID)';
            if (st === 'LEGACY') {
                bannerBg = '#f1f5f9'; bannerBdr = '#cbd5e1'; bannerText = '#334155'; bannerTitle = 'REKAMAN HISTORIS (LEGACY)';
            } else if (st !== 'VALID') {
                bannerBg = '#fef2f2'; bannerBdr = '#fca5a5'; bannerText = '#991b1b'; bannerTitle = 'PERINGATAN MANIPULASI DATA (' + st + ')';
            }

            let discrepancyHtml = '';
            if (Array.isArray(v.discrepancies) && v.discrepancies.length > 0) {
                const items = v.discrepancies.map(d => `<li style="margin-bottom:4px;">${escapeHtmlStr(d)}</li>`).join('');
                discrepancyHtml = `
                    <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 12px 14px; margin-bottom: 1rem; color: #9f1239; font-size: 12.5px;">
                        <div style="font-weight: 800; margin-bottom: 6px; text-transform: uppercase; font-size: 11px;">Temuan Forensik Ketidaksesuaian:</div>
                        <ul style="margin: 0; padding-left: 18px;">${items}</ul>
                    </div>
                `;
            }

            const qtyMetaHtml = IS_ADMIN_USER ? `
                <div>
                    <div style="font-size:10.5px; color:#64748b; font-weight:700;">QTY ADMIN / FISIK</div>
                    <div style="font-size:13px; font-weight:800; color:#1e293b;">${escapeHtmlStr(rec.qty_admin)} / ${escapeHtmlStr(rec.qty_staff)} Paket</div>
                </div>
            ` : '';

            let logsHtml = '';
            if (logs.length > 0) {
                const logRows = logs.map(l => {
                    const lst = String(l.result_status || '').toUpperCase();
                    const badgeCol = lst === 'VALID' ? 'color:#059669;background:#ecfdf5;' : 'color:#dc2626;background:#fef2f2;';
                    return `
                        <tr>
                            <td style="font-size:11.5px; padding:6px 10px;">${escapeHtmlStr(l.created_at)}</td>
                            <td style="font-size:11.5px; padding:6px 10px; font-weight:600;">${escapeHtmlStr(l.auditor_name || 'System')}</td>
                            <td style="font-size:11px; padding:6px 10px;"><span style="padding:2px 8px; border-radius:999px; font-weight:800; ${badgeCol}">${escapeHtmlStr(lst)}</span></td>
                            <td style="font-size:11px; padding:6px 10px; color:#64748b;">${escapeHtmlStr(l.notes || '-')}</td>
                        </tr>
                    `;
                }).join('');
                logsHtml = `
                    <div style="margin-top: 1rem;">
                        <div style="font-size: 11.5px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px;">5 Riwayat Audit Terakhir</div>
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                            <table class="table mb-0">
                                <thead style="background:#f8fafc; font-size:10.5px; color:#64748b; text-transform:uppercase;">
                                    <tr>
                                        <th style="padding:6px 10px;">Waktu</th>
                                        <th style="padding:6px 10px;">Auditor</th>
                                        <th style="padding:6px 10px;">Hasil</th>
                                        <th style="padding:6px 10px;">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>${logRows}</tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            const hashMatchColor = v.is_valid ? '#059669' : '#dc2626';

            body.innerHTML = `
                <div style="background: ${bannerBg}; border: 1px solid ${bannerBdr}; color: ${bannerText}; border-radius: 12px; padding: 12px 16px; margin-bottom: 1rem;">
                    <div style="font-weight: 900; font-size: 14px; margin-bottom: 2px;">${bannerTitle}</div>
                    <div style="font-size: 12.5px;">${escapeHtmlStr(v.message || '')}</div>
                </div>

                ${discrepancyHtml}

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 1rem;">
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lapis 1: Biner Foto Fisik</div>
                        <div style="font-size: 13px; font-weight: 800; margin-top: 2px; color: ${v.photo_file_valid ? '#059669' : '#dc2626'};">
                            ${v.photo_file_valid ? '✓ IDENTIK & UTUH' : '✗ BERUBAH / HILANG'}
                        </div>
                    </div>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                        <div style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lapis 2: Segel Payload Metadata</div>
                        <div style="font-size: 13px; font-weight: 800; margin-top: 2px; color: ${v.payload_valid ? '#059669' : '#dc2626'};">
                            ${v.payload_valid ? '✓ COCOK (HASH MATCH)' : '✗ TIDAK COCOK (MISMATCH)'}
                        </div>
                    </div>
                </div>

                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; margin-bottom:1rem; display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px;">
                    <div>
                        <div style="font-size:10.5px; color:#64748b; font-weight:700;">ID & EKSPEDISI</div>
                        <div style="font-size:13px; font-weight:800; color:#1e293b;">#${escapeHtmlStr(rec.id)} • ${escapeHtmlStr(rec.nama_ekspedisi)}</div>
                    </div>
                    <div>
                        <div style="font-size:10.5px; color:#64748b; font-weight:700;">PETUGAS GUDANG</div>
                        <div style="font-size:13px; font-weight:800; color:#1e293b;">${escapeHtmlStr(rec.nama_petugas)}</div>
                    </div>
                    <div>
                        <div style="font-size:10.5px; color:#64748b; font-weight:700;">KURIR & NOPOL</div>
                        <div style="font-size:13px; font-weight:800; color:#1e293b;">${escapeHtmlStr(rec.nama_kurir)} (${escapeHtmlStr(rec.no_polisi_kurir)})</div>
                    </div>
                    ${qtyMetaHtml}
                    <div>
                        <div style="font-size:10.5px; color:#64748b; font-weight:700;">WAKTU SERAH TERIMA</div>
                        <div style="font-size:12.5px; font-weight:700; color:#1e293b;">${escapeHtmlStr(rec.created_at)}</div>
                    </div>
                </div>

                <div style="background:#0f172a; color:#e2e8f0; border-radius:12px; padding:12px 14px; font-size:11.5px;">
                    <div style="margin-bottom:8px;">
                        <div style="color:#94a3b8; font-size:10px; font-weight:700; text-transform:uppercase;">Recorded Hash (Segel Tersimpan di Database):</div>
                        <code style="color:#38bdf8; word-break:break-all; font-size:11px;">${escapeHtmlStr(v.recorded_hash || '-')}</code>
                    </div>
                    <div style="margin-bottom:8px;">
                        <div style="color:#94a3b8; font-size:10px; font-weight:700; text-transform:uppercase;">Computed Hash (Hasil Hitung Ulang Saat Ini):</div>
                        <code style="color:${hashMatchColor === '#059669' ? '#4ade80' : '#f87171'}; word-break:break-all; font-size:11px;">${escapeHtmlStr(v.computed_hash || '-')}</code>
                    </div>
                    ${v.reconstructed_payload ? `
                    <div>
                        <div style="color:#94a3b8; font-size:10px; font-weight:700; text-transform:uppercase;">Canonical Payload String (POLO-SHA256-V1):</div>
                        <code style="color:#cbd5e1; word-break:break-all; font-size:10.5px;">${escapeHtmlStr(v.reconstructed_payload)}</code>
                    </div>` : ''}
                </div>

                ${logsHtml}
            `;
        })
        .catch(err => {
            console.error(err);
            body.innerHTML = `<div class="alert alert-danger mb-0">Terjadi kesalahan jaringan saat memverifikasi segel SHA-256.</div>`;
        });
    });
});

// Handler Tombol Audit Massal SHA-256
document.getElementById('btnVerifyBatchSha256')?.addEventListener('click', function () {
    Swal.fire({
        title: 'Mengaudit Seluruh Bukti...',
        text: 'Menghitung ulang SHA-256 untuk seluruh foto & metadata serah terima pada tanggal ini...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    const fd = new FormData();
    fd.append('_csrf_token', CSRF_TOKEN_VAL);
    fd.append('ekspedisi', '<?= e($ekspedisi) ?>');
    fd.append('tanggal', '<?= e($tanggal) ?>');

    fetch('<?= url("/ekspedisi/verify-batch") ?>', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (!res || !res.success) {
            Swal.fire('Gagal', res?.message || 'Audit massal gagal dijalankan.', 'error');
            return;
        }
        const s = res.summary || { total: 0, valid: 0, legacy: 0, tampered: 0, items: [] };
        (s.items || []).forEach(it => {
            const cell = document.getElementById('statusBadgeCell_' + it.id);
            const codeEl = cell ? cell.querySelector('code') : null;
            const fullH = codeEl ? codeEl.getAttribute('title') : '';
            updateRowStatusBadge(it.id, it.status === 'FILE_MISSING' ? 'TAMPERED' : it.status, fullH);
        });

        const iconType = s.tampered > 0 ? 'error' : 'success';
        Swal.fire({
            icon: iconType,
            title: s.tampered > 0 ? 'Terdeteksi Ketidaksesuaian!' : 'Seluruh Data Terverifikasi Utuh!',
            html: `
                <div style="text-align:left; font-size:13.5px; color:#334155;">
                    <p style="margin-bottom:10px;">${escapeHtmlStr(res.message)}</p>
                    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:8px; text-align:center;">
                        <div style="background:#ecfdf5; border:1px solid #a7f3d0; padding:8px; border-radius:10px;">
                            <div style="font-size:18px; font-weight:900; color:#059669;">${s.valid}</div>
                            <div style="font-size:10.5px; font-weight:700; color:#065f46;">VALID (SHA-256)</div>
                        </div>
                        <div style="background:#f1f5f9; border:1px solid #cbd5e1; padding:8px; border-radius:10px;">
                            <div style="font-size:18px; font-weight:900; color:#475569;">${s.legacy}</div>
                            <div style="font-size:10.5px; font-weight:700; color:#334155;">LEGACY</div>
                        </div>
                        <div style="background:#fef2f2; border:1px solid #fecaca; padding:8px; border-radius:10px;">
                            <div style="font-size:18px; font-weight:900; color:#dc2626;">${s.tampered}</div>
                            <div style="font-size:10.5px; font-weight:700; color:#991b1b;">TAMPERED</div>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonColor: s.tampered > 0 ? '#ef4444' : '#10b981'
        });
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Gagal menghubungi server untuk audit massal.', 'error');
    });
});

<?php if (isAdmin()): ?>
// Admin Target Date Input Handler
document.getElementById('adminTargetDateInput')?.addEventListener('change', function() {
    const selectedDate = this.value;
    if (selectedDate && selectedDate !== '<?= e($tanggal) ?>') {
        window.location.href = '<?= url("/ekspedisi/" . strtolower($ekspedisi)) ?>?tanggal=' + selectedDate;
    }
});

// Admin Target QTY Form Handler (Admin Only)
document.getElementById('formAdminTargetQty')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const qtyInput = document.getElementById('adminTotalQtyInput');
    const qtyVal = parseInt(qtyInput.value) || 0;
    const targetDateInput = document.getElementById('adminTargetDateInput');
    const targetDateVal = targetDateInput ? targetDateInput.value : '<?= e($tanggal) ?>';
    
    const formData = new FormData(this);

    Swal.fire({
        title: 'Menyimpan Target QTY...',
        text: 'Memperbarui data acuan validasi ekspedisi...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('<?= url("/ekspedisi/update-qty") ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: res.message || 'Target Total QTY <?= e($ekspedisi) ?> berhasil diperbarui.',
                timer: 1800,
                showConfirmButton: false
            });
            if (targetDateVal === '<?= e($tanggal) ?>') {
                const badgeTarget = document.getElementById('badgeTargetAdminQty');
                if (badgeTarget) {
                    badgeTarget.innerText = 'TARGET: ' + qtyVal + ' QTY';
                }
                const displayVal = document.getElementById('displayTargetQtyVal');
                if (displayVal) {
                    displayVal.innerHTML = qtyVal + ' <span style="font-size: 11px; font-weight: 600; color: #64748b;">Paket</span>';
                }
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: res.message || 'Gagal menyimpan target QTY.',
                confirmButtonColor: '#ef4444'
            });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error!', 'Terjadi kesalahan sistem saat menyimpan.', 'error');
    });
});
<?php endif; ?>

<?php if (!isAdmin()): ?>
// Manual Upload Handler — Input QTY, Nama Kurir, Nopol & Segel SHA-256
document.getElementById('manualUploadHeader')?.addEventListener('change', function(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const file = e.target.files[0];
    if (!file) return;

    Swal.fire({
        title: 'Data Serah Terima <?= e($ekspedisi) ?>',
        html: `
            <div style="text-align:left; font-size:13px;">
                <p class="text-muted mb-3" style="font-size:12px;">Masukkan kuantitas fisik & identitas kurir untuk dikunci ke dalam segel <strong>SHA-256</strong>:</p>
                <label class="fw-bold mb-1">Quantity (QTY) Fisik Paket <span class="text-danger">*</span></label>
                <input type="number" id="swalStaffQty" class="form-control mb-2" placeholder="Contoh: 100" min="1">
                <label class="fw-bold mb-1">Nama Kurir Penjemput <span class="text-danger">*</span></label>
                <input type="text" id="swalNamaKurir" class="form-control mb-2" placeholder="Contoh: Andi (Kurir <?= e($ekspedisi) ?>)">
                <label class="fw-bold mb-1">No. Polisi Kendaraan (Opsional)</label>
                <input type="text" id="swalNopolKurir" class="form-control" placeholder="Contoh: B 1234 XYZ" style="text-transform:uppercase;">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Validasi & Segel SHA-256',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#64748b',
        preConfirm: () => {
            const qty = document.getElementById('swalStaffQty').value.trim();
            const kurir = document.getElementById('swalNamaKurir').value.trim();
            const nopol = document.getElementById('swalNopolKurir').value.trim().toUpperCase() || '-';
            if (!qty || parseInt(qty, 10) <= 0) {
                Swal.showValidationMessage('Quantity (QTY) fisik wajib diisi dengan benar!');
                return false;
            }
            if (!kurir) {
                Swal.showValidationMessage('Nama Kurir Penjemput wajib diisi!');
                return false;
            }
            return { qty, kurir, nopol };
        }
    }).then((formRes) => {
        if (!formRes.isConfirmed || !formRes.value) {
            e.target.value = '';
            return;
        }

        const { qty: staffQty, kurir: namaKurir, nopol: nopolKurir } = formRes.value;

        Swal.fire({
            title: 'Memproses & Menyegel SHA-256...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const reader = new FileReader();
        reader.onload = function(ex) {
            const img = new Image();
            img.onload = function() {
                const cvs = document.createElement('canvas');
                cvs.width = img.width;
                cvs.height = img.height;
                const ctx = cvs.getContext('2d');
                ctx.drawImage(img, 0, 0);

                applyLabelManual(cvs, '<?= e($ekspedisi) ?>', '<?= addslashes(auth()["username"] ?? "Petugas") ?>', namaKurir);

                const dataUrl = cvs.toDataURL('image/webp', 0.8);

                const formData = new FormData();
                formData.append('photo', dataUrl);
                formData.append('ekspedisi', '<?= e($ekspedisi) ?>');
                formData.append('qty', staffQty);
                formData.append('nama_kurir', namaKurir);
                formData.append('no_polisi_kurir', nopolKurir);
                formData.append('_csrf_token', CSRF_TOKEN_VAL);

                fetch('<?= url("/ekspedisi/store") ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ 
                            icon: 'success', 
                            title: 'Tersimpan & Tersegel SHA-256!', 
                            html: `
                                <div style="text-align:left; font-size:12.5px;">
                                    <p>${escapeHtmlStr(data.message)}</p>
                                    <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:8px;">
                                        <div style="font-size:10px; font-weight:800; color:#059669;">VERIFICATION HASH (SHA-256):</div>
                                        <code style="word-break:break-all; font-size:11px;">${escapeHtmlStr(data.verification_hash || '-')}</code>
                                    </div>
                                </div>
                            `,
                            confirmButtonColor: '#10b981',
                            confirmButtonText: 'OK'
                        }).then(() => location.reload());
                    } else {
                        const uploadInput = document.getElementById('manualUploadHeader');
                        if (uploadInput) uploadInput.value = '';

                        const namaEkspedisi = '<?= e($ekspedisi) ?>';
                        const waPhone = '6285772700656';
                        const waText = `Hallo Mba Putri,\nTotal quantity ${namaEkspedisi} yang di input oleh saya sejumlah ${staffQty} apakah sudah sesuai?\nTerima Kasih.`;
                        const waLink = `https://wa.me/${waPhone}?text=${encodeURIComponent(waText)}`;

                        Swal.fire({
                            icon: 'warning',
                            title: 'Validasi Gagal!',
                            html: `
                                <div style="font-size: 14.5px; color: #475569; line-height: 1.6; margin-top: 6px;">
                                    Data paket tidak bisa berhasil disimpan. Paket kurang, mohon dicek kembali dan menghubungi <strong>Mba Putri</strong>. Terima kasih.
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Hubungi WA',
                            cancelButtonText: 'Tutup',
                            confirmButtonColor: '#25D366',
                            cancelButtonColor: '#ef4444',
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const waWindow = window.open(waLink, '_blank', 'noopener,noreferrer');
                                if (waWindow) waWindow.opener = null;
                            }
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error!', 'Gagal mengunggah foto.', 'error');
                });
            };
            img.src = ex.target.result;
        };
        reader.readAsDataURL(file);
    });
});

function applyLabelManual(canvas, ekspedisi, petugas, kurir) {
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
    const labelMain = kurir ? `${ekspedisi} - ${petugas} (Kurir: ${kurir})` : `${ekspedisi} - ${petugas}`;
    ctx.fillText(labelMain, padding, height - barHeight + padding);
    
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
<?php endif; ?>

// Soft Delete Handler for Admins
document.querySelectorAll('.btn-soft-delete-ekspedisi').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const form = this.closest('form');
        const url = form.getAttribute('action');
        const token = form.querySelector('input[name="_csrf_token"]').value;

        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: "Data akan dihapus dan tidak akan tampil di riwayat.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Menghapus...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('_csrf_token', token);

                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Terjadi kesalahan sistem saat menghapus.', 'error');
                });
            }
        });
    });
});
</script>

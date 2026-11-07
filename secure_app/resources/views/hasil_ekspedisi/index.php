<?php
$activeQueueDate = $activeQueueDate ?? (!empty($startDate) ? $startDate : date('Y-m-d'));
$queueStats = $queueStats ?? ['total' => 0, 'pending' => 0, 'sudah' => 0, 'batal' => 0, 'retur' => 0];
$cpDefaultEksp = strtoupper(old('nama_ekspedisi', 'INSTANT'));
$cpDefaultLayanan = strtoupper(old('layanan_ekspedisi', $masterEkspedisi[$cpDefaultEksp]['default_layanan'] ?? 'INSTANT'));
$cpDefaultCutoff = old('cutoff_time', isset($masterEkspedisi[$cpDefaultEksp]['cutoff_time']) ? substr((string)$masterEkspedisi[$cpDefaultEksp]['cutoff_time'], 0, 5) : '16:00');

$returnQueryParams = http_build_query([
    'search'     => $search ?? '',
    'ekspedisi'  => $ekspedisi ?? '',
    'start_date' => $startDate ?? '',
    'end_date'   => $endDate ?? '',
]);
$currentReturnPath = '/hasil-ekspedisi' . ($returnQueryParams !== '' ? ('?' . $returnQueryParams) : '');
?>
<div class="animate__animated animate__fadeIn">
    <!-- Header & Consolidated Single-Point-of-Entry Action Toolbar -->
    <div class="he-page-header mb-3">
        <div class="he-title-block">
            <h1 class="h3 mb-1 fw-bold text-dark">LAPORAN HASIL EKSPEDISI (MANIFEST RESI)</h1>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 5px;">
                    <i data-lucide="shield-check" style="width: 13px; height: 13px;"></i> Manifest &amp; Nomor Resi Terkunci Aggregate SHA-256
                </span>
                <span style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; display: inline-flex; align-items: center; gap: 5px;">
                    <i data-lucide="zap" style="width: 13px; height: 13px;"></i> Algoritma Priority Scheduling Aktif (Non-Preemptive + Aging)
                </span>
            </div>
        </div>

        <!-- Consolidated Action Toolbar -->
        <div class="he-action-toolbar">
            <!-- Grup Kiri: Primary Actions -->
            <div class="he-toolbar-group he-toolbar-primary">
                <button type="button" id="btnOpenCreatePaketModal" class="he-action-btn he-btn-create"
                    data-bs-toggle="modal" data-bs-target="#createPaketModal">
                    <i data-lucide="plus-circle" style="width: 15px; height: 15px;"></i>
                    <span>Tambah Paket</span>
                </button>

                <button type="button" id="btnRecalculatePriority" class="he-action-btn he-btn-recalc"
                    title="Hitung ulang komponen waktu Cut-Off &amp; Dynamic Aging secara real-time">
                    <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                    <span>Hitung Ulang Skor</span>
                </button>

                <button type="button" id="btnOpenExportModal" class="he-action-btn he-btn-export"
                    data-bs-toggle="modal" data-bs-target="#modalExportEkspedisi"
                    title="Export laporan data paket &amp; manifest per ekspedisi (.xlsx)">
                    <i data-lucide="file-spreadsheet" style="width: 15px; height: 15px;"></i>
                    <span>Export Excel</span>
                </button>

                <button type="button" id="btnOpenUnifiedImportModal" class="he-action-btn he-btn-import"
                    data-bs-toggle="modal" data-bs-target="#unifiedImportModal">
                    <i data-lucide="upload-cloud" style="width: 15px; height: 15px;"></i>
                    <span>Import Excel</span>
                </button>
            </div>

        </div>
    </div>

    <!-- Compact Active Queue Synchronization Strip -->
    <div class="he-queue-strip mb-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span style="font-size: 11.5px; font-weight: 700; color: #334155; display: inline-flex; align-items: center; gap: 5px;">
                <i data-lucide="zap" style="width: 14px; height: 14px; color: #2563eb;"></i>
                Status Antrean Instan/Sameday:
            </span>
            <span class="he-qstat"><i class="he-qdot" style="background:#3b82f6;"></i> Total: <strong><?= number_format($queueStats['total']) ?></strong></span>
            <span class="he-qstat"><i class="he-qdot" style="background:#f59e0b;"></i> Pending: <strong><?= number_format($queueStats['pending']) ?></strong></span>
            <span class="he-qstat"><i class="he-qdot" style="background:#10b981;"></i> Sudah: <strong><?= number_format($queueStats['sudah']) ?></strong></span>
            <span class="he-qstat"><i class="he-qdot" style="background:#ef4444;"></i> Batal: <strong><?= number_format($queueStats['batal']) ?></strong></span>
            <span class="he-qstat"><i class="he-qdot" style="background:#f97316;"></i> Retur: <strong><?= number_format($queueStats['retur']) ?></strong></span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= url('/paket?tanggal=' . urlencode($activeQueueDate)) ?>" class="he-queue-link" title="Buka daftar antrean kurir perorangan Instan/Sameday">
                <span>Buka Antrean Instan</span>
                <i data-lucide="arrow-up-right" style="width: 13px; height: 13px;"></i>
            </a>
            <button type="button" onclick="location.reload()" class="btn btn-sm btn-light border d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; padding: 0; border-radius: 7px;" title="Muat Ulang Data">
                <i data-lucide="rotate-cw" style="width: 13px; height: 13px; color: #475569;"></i>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-2 mb-4">
        <?php
        $isFiltered = !empty($search) || !empty($ekspedisi) || !empty($startDate) || !empty($endDate);
        
        $statItems = [
            [
                'title' => $isFiltered ? 'Laporan Terfilter' : 'Upload Hari Ini', 
                'value' => $isFiltered ? $stats['total_files'] : $stats['upload_today'], 
                'icon' => $isFiltered ? 'filter' : 'calendar', 
                'color' => 'purple'
            ],
            ['title' => 'Total Paket SPX', 'value' => $stats['total_paket_spx'], 'icon' => 'package', 'color' => 'primary'],
            ['title' => 'Total Paket Instan', 'value' => $stats['total_paket_instan'], 'icon' => 'zap', 'color' => 'orange'],
            ['title' => 'Total Paket JNE', 'value' => $stats['total_paket_jne'] ?? 0, 'icon' => 'truck', 'color' => 'red'],
            ['title' => 'Total Paket JNT', 'value' => $stats['total_paket_jnt'] ?? 0, 'icon' => 'truck', 'color' => 'yellow'],
            ['title' => 'Total Paket LEX', 'value' => $stats['total_paket_lex'] ?? 0, 'icon' => 'truck', 'color' => 'cyan'],
            ['title' => 'Total Paket ID', 'value' => $stats['total_paket_id'] ?? 0, 'icon' => 'truck', 'color' => 'indigo'],
            ['title' => 'Total Paket Ninja', 'value' => $stats['total_paket_ninja'] ?? 0, 'icon' => 'truck', 'color' => 'pink'],
            ['title' => 'Total Paket SiCepat', 'value' => $stats['total_paket_sicepat'] ?? 0, 'icon' => 'truck', 'color' => 'green'],
        ];

        foreach ($statItems as $item):
            $bgColorClass = "bg-" . $item['color'] . "-soft";
            $textColorClass = "text-" . ($item['color'] === 'primary' ? 'primary' : $item['color']);
        ?>
        <div class="col-xl-4 col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 mb-0">
                <div class="card-body p-2 d-flex align-items-center">
                    <div class="<?= $bgColorClass ?> d-flex align-items-center justify-content-center rounded-3 me-3" style="width: 38px; height: 38px; flex-shrink: 0;">
                        <i data-lucide="<?= $item['icon'] ?>" class="<?= $textColorClass ?>" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div class="text-truncate">
                        <div class="text-muted fw-medium mb-0" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;"><?= $item['title'] ?></div>
                        <div class="h6 mb-0 fw-bold text-dark"><?= number_format($item['value']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Charts Section -->
    <div class="row g-3 mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i data-lucide="bar-chart-3" class="text-primary" style="width: 18px; height: 18px;"></i>
                        Volume Paket per Ekspedisi
                    </h6>
                </div>
                <div class="card-body">
                    <div style="height: 220px;">
                        <canvas id="expeditionLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i data-lucide="pie-chart" class="text-primary" style="width: 18px; height: 18px;"></i>
                        Komposisi Ekspedisi
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="width: 100%; max-width: 180px;">
                        <canvas id="expeditionDoughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 py-3">
            <form action="<?= url('/hasil-ekspedisi') ?>" method="GET" id="heFilterForm" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i data-lucide="search" style="width:14px;"></i></span>
                        <input type="text" name="search" id="heSearchInput" class="form-control border-start-0" placeholder="Cari nama file / resi / penerima..." value="<?= e($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="ekspedisi" id="heEkspedisiFilter" class="form-select form-select-sm">
                        <option value="">Semua Ekspedisi</option>
                        <option value="SPX" <?= $ekspedisi == 'SPX' ? 'selected' : '' ?>>SPX</option>
                        <option value="NON SPX" <?= $ekspedisi == 'NON SPX' ? 'selected' : '' ?>>NON SPX (Gabungan)</option>
                        <option value="INSTANT" <?= in_array($ekspedisi, ['INSTANT', 'INSTAN', 'SAMEDAY']) ? 'selected' : '' ?>>INSTAN / SAMEDAY</option>
                        <option value="JNE" <?= $ekspedisi == 'JNE' ? 'selected' : '' ?>>JNE</option>
                        <option value="JNT" <?= $ekspedisi == 'JNT' ? 'selected' : '' ?>>JNT</option>
                        <option value="LEX" <?= $ekspedisi == 'LEX' ? 'selected' : '' ?>>LEX</option>
                        <option value="ID" <?= $ekspedisi == 'ID' ? 'selected' : '' ?>>ID Express</option>
                        <option value="NINJA" <?= $ekspedisi == 'NINJA' ? 'selected' : '' ?>>Ninja Xpress</option>
                        <option value="SICEPAT" <?= $ekspedisi == 'SICEPAT' ? 'selected' : '' ?>>SiCepat</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="start_date" id="heStartDateFilter" class="form-control form-control-sm" value="<?= e($startDate) ?>" title="Tanggal Mulai">
                </div>
                <div class="col-md-2">
                    <input type="date" name="end_date" id="heEndDateFilter" class="form-control form-control-sm" value="<?= e($endDate) ?>" title="Tanggal Selesai">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="<?= url('/hasil-ekspedisi') ?>" class="btn btn-sm btn-light border w-100" title="Reset ke data Hari Ini">Hari Ini</a>
                </div>
                <div class="col-md-1">
                    <a href="<?= url('/hasil-ekspedisi?start_date=&end_date=') ?>" class="btn btn-sm btn-outline-secondary w-100" title="Tampilkan semua data histori">Semua</a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">No</th>
                            <th class="py-3">Nama File</th>
                            <th class="py-3">Ekspedisi</th>
                            <th class="py-3">Tanggal Upload</th>
                            <th class="py-3">Jumlah Data</th>
                            <th class="py-3">DiUpload Oleh</th>
                            <th class="py-3">Segel SHA-256</th>
                            <th class="py-3 text-end pe-4">Aksi &amp; Audit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="py-4">
                                    <i data-lucide="file-x" style="width: 48px; height: 48px;" class="text-muted mb-3"></i>
                                    <h6 class="text-muted fw-bold">Belum ada laporan manifest resi <?= ($startDate ? 'pada ' . date('d M Y', strtotime($startDate)) : '') ?></h6>
                                    <p class="small text-muted mb-0">Gunakan tombol <strong>Import Excel</strong> atau <strong>Tambah Paket</strong> pada toolbar di atas untuk mulai mengelola paket hari ini.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $no = ($page - 1) * 10 + 1; foreach ($items as $item):
                            $mHash = (string)($item['manifest_hash'] ?? '');
                            $mHashShort = $mHash !== '' ? (substr($mHash, 0, 10) . '...' . substr($mHash, -6)) : '';
                        ?>
                        <tr>
                            <td class="ps-4 small text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="file-icon me-2">
                                        <i data-lucide="file-spreadsheet" class="text-success" style="width:18px;"></i>
                                    </div>
                                    <span class="fw-bold small text-dark"><?= e($item['nama_file']) ?></span>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border small fw-medium"><?= e($item['ekspedisi']) ?></span></td>
                            <td class="small"><?= formatDate($item['tanggal_upload']) ?></td>
                            <td><span class="fw-bold text-primary"><?= number_format($item['jumlah_paket']) ?></span> <small class="text-muted">Paket</small></td>
                            <td class="small"><?= e($item['uploaded_by_name']) ?></td>
                            <td>
                                <?php if ($mHash !== ''): ?>
                                    <div class="d-flex flex-column align-items-start gap-1">
                                        <span class="badge bg-success-soft text-success small fw-bold d-inline-flex align-items-center gap-1" style="border: 1px solid #a7f3d0;">
                                            <i data-lucide="shield-check" style="width: 12px; height: 12px;"></i> SHA-256 TERSEGEL
                                        </span>
                                        <code style="font-size: 10px; color: #64748b;" title="<?= e($mHash) ?>"><?= e($mHashShort) ?></code>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border small fw-medium">Tersimpan (Legacy)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                    <button type="button" class="btn btn-sm btn-icon-verify btn-verify-manifest" data-id="<?= (int)$item['id'] ?>" data-file="<?= e($item['nama_file']) ?>" title="Verifikasi Integritas Manifest SHA-256">
                                        <i data-lucide="shield-check" style="width: 14px;"></i>
                                        <span class="d-none d-xl-inline ms-1" style="font-size: 11.5px; font-weight: 700;">Audit</span>
                                    </button>
                                    <button class="btn btn-sm btn-icon-view btn-show-detail" data-id="<?= $item['id'] ?>" title="Lihat Data Resi">
                                        <i data-lucide="eye" style="width: 14px;"></i>
                                    </button>
                                    <a href="<?= url("/hasil-ekspedisi/{$item['id']}/download") ?>" class="btn btn-sm btn-icon-download" title="Download Original">
                                        <i data-lucide="download" style="width: 14px;"></i>
                                    </a>
                                    <?php if (isAdmin()): ?>
                                    <form action="<?= url("/hasil-ekspedisi/{$item['id']}/delete") ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="button" class="btn btn-sm btn-icon-delete delete-confirm" title="Hapus">
                                            <i data-lucide="trash-2" style="width: 14px;"></i>
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
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm justify-content-center mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= url('/hasil-ekspedisi?page=' . ($page - 1) . '&search=' . urlencode($search) . '&ekspedisi=' . urlencode($ekspedisi) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>">&laquo;</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/hasil-ekspedisi?page=' . $i . '&search=' . urlencode($search) . '&ekspedisi=' . urlencode($ekspedisi) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= url('/hasil-ekspedisi?page=' . ($page + 1) . '&search=' . urlencode($search) . '&ekspedisi=' . urlencode($ekspedisi) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: TAMBAH PAKET BARU (TERINTEGRASI PRIORITY SCHEDULING)             -->
<!-- ========================================================================= -->
<div class="modal fade" id="createPaketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header px-4 py-3" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width: 34px; height: 34px; border-radius: 9px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="package-plus" style="width: 17px; height: 17px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 14.5px; color: #0f172a;">Tambah Paket Baru ke Antrean</h5>
                        <div style="font-size: 11px; color: #64748b;">Input paket manual beserta parameter penjadwalan Priority Scheduling</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="d-none d-sm-inline-block" style="background: #dbeafe; color: #1d4ed8; font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 999px;">
                        S = 0.40C + 0.30T + 0.15V + 0.15A
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <form action="<?= url('/paket/store') ?>" method="POST" id="createPaketForm">
                <?= csrf_field() ?>
                <input type="hidden" name="from_hasil_ekspedisi" value="1">
                <input type="hidden" name="return_url" value="<?= e($currentReturnPath) ?>">

                <div class="modal-body px-4 py-3">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="cp_no_pesanan" class="he-modal-label">No. Pesanan / Resi <span class="text-danger">*</span></label>
                            <input type="text" name="no_pesanan" id="cp_no_pesanan" class="form-control he-modal-input"
                                placeholder="Contoh: 261030SPX001" value="<?= e(old('no_pesanan')) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cp_nama_pemesan" class="he-modal-label">Nama Pemesan / Penerima <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pemesan" id="cp_nama_pemesan" class="form-control he-modal-input"
                                placeholder="Contoh: Budi Santoso" value="<?= e(old('nama_pemesan')) ?>" required>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="cp_kode" class="he-modal-label">Kode Rak / Barang</label>
                            <input type="text" name="kode" id="cp_kode" class="form-control he-modal-input"
                                placeholder="Contoh: POLO-A1" value="<?= e(old('kode')) ?>">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="cp_tanggal" class="he-modal-label">Tanggal Operasional <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="cp_tanggal" class="form-control he-modal-input"
                                value="<?= e(old('tanggal', $activeQueueDate)) ?>" required>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="cp_sesi" class="he-modal-label">Sesi Pick-Up <span class="text-danger">*</span></label>
                            <input type="text" name="sesi" id="cp_sesi" list="cp_sesi_options" class="form-control he-modal-input"
                                value="<?= e(old('sesi', 'SESI 1')) ?>" required placeholder="Pilih / ketik sesi">
                            <datalist id="cp_sesi_options">
                                <option value="SESI 1">
                                <option value="SESI 2">
                                <option value="SESI 3">
                                <option value="INSTAN / SAMEDAY">
                            </datalist>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="cp_jam" class="he-modal-label">Jam Sesi <span class="text-muted fw-normal" style="font-size: 10px;">(Opsional)</span></label>
                            <input type="time" name="jam" id="cp_jam" class="form-control he-modal-input" value="<?= e(old('jam')) ?>">
                        </div>
                    </div>

                    <!-- Box Parameter Algoritma Priority Scheduling -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 11px; padding: 0.9rem 1rem; margin-top: 1rem;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2" style="border-bottom: 1px dashed #cbd5e1;">
                            <div class="d-flex align-items-center gap-2">
                                <i data-lucide="cpu" style="width: 14px; height: 14px; color: #2563eb;"></i>
                                <span style="font-size: 11px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.35px;">
                                    Parameter Algoritma Priority Scheduling
                                </span>
                            </div>
                            <span style="font-size: 10.5px; color: #64748b;">Menentukan bobot SLA (T) &amp; urgensi Cut-Off (C)</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="cp_nama_ekspedisi" class="he-modal-label">Ekspedisi Penjemput</label>
                                <select name="nama_ekspedisi" id="cp_nama_ekspedisi" class="form-select he-modal-input">
                                    <?php foreach (($masterEkspedisi ?? []) as $kodeEksp => $prof): ?>
                                        <?php if ($kodeEksp === 'SAMEDAY') continue; ?>
                                        <option value="<?= e($kodeEksp) ?>"
                                                data-layanan="<?= e($prof['default_layanan'] ?? 'REGULER') ?>"
                                                data-cutoff="<?= e(substr((string)($prof['cutoff_time'] ?? '17:00:00'), 0, 5)) ?>"
                                                <?= ($cpDefaultEksp === $kodeEksp || ($kodeEksp === 'INSTANT' && $cpDefaultEksp === 'SAMEDAY')) ? 'selected' : '' ?>>
                                            <?= $kodeEksp === 'INSTANT' ? 'INSTAN / SAMEDAY — Kurir Instan &amp; Same-Day (GoSend / Grab / SPX Instant)' : (e($kodeEksp) . (!empty($prof['nama_lengkap']) ? ' — ' . e($prof['nama_lengkap']) : '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cp_layanan_ekspedisi" class="he-modal-label">Tipe Layanan (SLA)</label>
                                <select name="layanan_ekspedisi" id="cp_layanan_ekspedisi" class="form-select he-modal-input">
                                    <?php foreach (['INSTANT' => 'INSTAN / SAMEDAY (Bobot 100)', 'EXPRESS' => 'EXPRESS (Bobot 75)', 'REGULER' => 'REGULER (Bobot 50)', 'CARGO' => 'CARGO (Bobot 25)'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= ($cpDefaultLayanan === $val || ($val === 'INSTANT' && $cpDefaultLayanan === 'SAMEDAY')) ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cp_cutoff_time" class="he-modal-label">Batas Cut-Off Kurir</label>
                                <input type="time" name="cutoff_time" id="cp_cutoff_time" class="form-control he-modal-input" value="<?= e($cpDefaultCutoff) ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cp_jumlah_item" class="he-modal-label">Jml Item</label>
                                <input type="number" name="jumlah_item" id="cp_jumlah_item" class="form-control he-modal-input" min="1" value="<?= e(old('jumlah_item', '1')) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light-soft border-top px-4 py-3">
                    <button type="button" class="btn btn-light border rounded-3 px-3" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 600;">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 d-inline-flex align-items-center gap-2" style="font-size: 12px; font-weight: 700;">
                        <i data-lucide="save" style="width: 14px; height: 14px;"></i>
                        <span>Simpan &amp; Jadwalkan Paket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: IMPORT EXCEL TERPADU (MANIFEST SHA-256 + PRIORITY SCHEDULING)    -->
<!-- ========================================================================= -->
<div class="modal fade" id="unifiedImportModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 580px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header px-4 py-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-2">
                    <div style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="upload-cloud" style="width: 18px; height: 18px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 14.5px; color: #0f172a;">Import Excel Terpadu (Manifest &amp; Antrean)</h5>
                        <div style="font-size: 11px; color: #64748b;">Segel Aggregate SHA-256 &amp; kalkulasi Priority Scheduling dalam satu langkah</div>
                    </div>
                </div>
                <button type="button" class="btn-close" id="ui_close_btn" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="<?= url('/hasil-ekspedisi/store') ?>" method="POST" enctype="multipart/form-data" id="unifiedImportForm" class="ajax-form">
                <?= csrf_field() ?>
                <input type="hidden" name="default_tanggal" id="ui_default_tanggal" value="<?= e($activeQueueDate) ?>">
                <input type="hidden" name="import_mode" id="ui_import_mode" value="manual">
                <input type="file" name="excel_file" id="ui_file_input" accept=".xlsx,.xls,.csv" class="hidden-input">

                <div class="modal-body px-4 py-3">
                    <!-- Konfigurasi Ekspedisi, SLA & Cut-Off -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 0.95rem; margin-bottom: 0.9rem;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2" style="border-bottom: 1px dashed #cbd5e1;">
                            <div class="d-flex align-items-center gap-1">
                                <i data-lucide="cpu" style="width: 14px; height: 14px; color: #2563eb;"></i>
                                <span style="font-size: 10.5px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.35px;">
                                    1. Kategori Ekspedisi &amp; Parameter SLA
                                </span>
                            </div>
                            <span style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 9.5px; font-weight: 700; padding: 2px 7px; border-radius: 999px;">
                                SHA-256 + Priority Score
                            </span>
                        </div>

                        <div class="row g-2">
                            <div class="col-12">
                                <label for="ui_nama_ekspedisi" class="he-modal-label">Ekspedisi Penjemput / Kategori Batch <span class="text-danger">*</span></label>
                                <select name="nama_ekspedisi" id="ui_nama_ekspedisi" class="form-select he-modal-input">
                                    <option value="" disabled selected>-- Pilih Ekspedisi / Kategori File yang Diimport --</option>
                                    <optgroup label="⚡ PAKET INSTAN &amp; SAMEDAY (Masuk Antrean Prioritas &amp; Segel SHA-256)">
                                        <option value="INSTANT" data-layanan="INSTANT" data-cutoff="16:00">INSTAN / SAMEDAY — Kurir Instan &amp; Same-Day (GoSend / Grab / SPX Instant / Lalamove)</option>
                                    </optgroup>
                                    <optgroup label="📦 PAKET REGULER / MASSAL (Laporan Hasil Ekspedisi & Segel SHA-256)">
                                        <option value="SPX" data-layanan="REGULER" data-cutoff="17:00">SPX — Shopee Xpress</option>
                                        <option value="LEX" data-layanan="REGULER" data-cutoff="17:00">LEX — Lazada Express (LEX)</option>
                                        <option value="JNE" data-layanan="REGULER" data-cutoff="18:00">JNE — JNE Express</option>
                                        <option value="JNT" data-layanan="REGULER" data-cutoff="18:00">JNT — J&amp;T Express</option>
                                        <option value="ID" data-layanan="REGULER" data-cutoff="17:00">ID — ID Express</option>
                                        <option value="NINJA" data-layanan="REGULER" data-cutoff="17:00">NINJA — Ninja Xpress</option>
                                        <option value="SICEPAT" data-layanan="REGULER" data-cutoff="17:00">SICEPAT — SiCepat Ekspres</option>
                                        <option value="CARGO" data-layanan="CARGO" data-cutoff="19:00">CARGO — Layanan Kargo / Hemat / Bulky</option>
                                    </optgroup>
                                    <optgroup label="🔄 MULTI-EKSPEDISI / DETEKSI OTOMATIS">
                                        <option value="NON SPX" data-mode="auto">NON SPX — Manifest Multi-Sheet (Hanya tab Instan masuk antrean prioritas)</option>
                                        <option value="AUTO" data-mode="auto">AUTO — Deteksi Otomatis dari Kolom Excel / Format Resi</option>
                                    </optgroup>
                                </select>
                                <div style="font-size: 10.5px; color: #64748b; margin-top: 4px; line-height: 1.4;">
                                    <i data-lucide="info" style="width: 12px; height: 12px; vertical-align: middle;"></i> <strong>Info Alur:</strong> Paket reguler (SPX, JNE, LEX, dll.) adalah serah terima massal kurir sehingga langsung dicatat &amp; disegel di Laporan Hasil Ekspedisi. Antrean di menu <strong>Instan</strong> dikhususkan untuk paket <strong>Instan / Sameday</strong> (1 paket 1 kurir).
                                </div>
                            </div>

                            <div class="col-12 col-sm-7">
                                <label for="ui_layanan_ekspedisi" class="he-modal-label">Tipe Layanan (SLA)</label>
                                <select name="layanan_ekspedisi" id="ui_layanan_ekspedisi" class="form-select he-modal-input">
                                    <option value="INSTANT">INSTAN / SAMEDAY (Bobot 100)</option>
                                    <option value="EXPRESS">EXPRESS (Bobot 75)</option>
                                    <option value="REGULER" selected>REGULER (Bobot 50)</option>
                                    <option value="CARGO">CARGO (Bobot 25)</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-5">
                                <label for="ui_cutoff_time" class="he-modal-label">Batas Cut-Off Kurir</label>
                                <input type="time" name="cutoff_time" id="ui_cutoff_time" class="form-control he-modal-input" value="17:00">
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2 px-2 py-1" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 7px;">
                            <span style="font-size: 10.5px; color: #64748b; font-weight: 600;">Preview Konfigurasi:</span>
                            <span id="ui_preview_badge" style="font-size: 11px; font-weight: 700; color: #94a3b8;">Belum dipilih (pilih ekspedisi di atas)</span>
                        </div>
                    </div>
                    
                    <!-- Area Upload File Spreadsheet -->
                    <div class="mb-0">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="he-modal-label mb-0">2. Pilih File Excel / CSV <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <a href="<?= url('/paket/template') ?>" style="font-size: 10.5px; font-weight: 700; color: #2563eb; text-decoration: none;" class="no-loading d-inline-flex align-items-center gap-1">
                                    <i data-lucide="download" style="width: 11px; height: 11px;"></i> Template Antrean
                                </a>
                                <span style="color: #cbd5e1;">|</span>
                                <a href="<?= url('/hasil-ekspedisi/template') ?>" style="font-size: 10.5px; font-weight: 700; color: #059669; text-decoration: none;" class="no-loading d-inline-flex align-items-center gap-1">
                                    <i data-lucide="download" style="width: 11px; height: 11px;"></i> Template Manifest
                                </a>
                            </div>
                        </div>

                        <div class="upload-container">
                            <div class="upload-drop-zone" id="ui_drop_zone">
                                <div class="upload-content text-center py-3" id="ui_upload_placeholder">
                                    <div class="upload-icon-wrapper mb-1">
                                        <i data-lucide="file-spreadsheet" class="text-success icon-main" style="width: 32px; height: 32px;"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1 small" style="font-size: 12px;">Tarik file Excel/CSV ke sini atau klik untuk memilih</h6>
                                    <p class="text-muted" style="font-size: 10.5px; margin-bottom: 0;">Mendukung format Antrean Paket maupun Manifest Resi (.xlsx, .xls, .csv — Maks 10MB)</p>
                                    <button type="button" class="btn btn-primary btn-sm mt-2 px-3 rounded-pill" id="ui_browse_btn" style="font-size: 11px;">
                                        <i data-lucide="folder-open" class="me-1" style="width:12px;"></i> Pilih File Explorer
                                    </button>
                                </div>

                                <div class="file-preview-zone d-none text-center py-3" id="ui_file_selected">
                                    <div class="preview-card p-3 rounded-4 bg-white shadow-sm border mx-auto" style="max-width: 360px;">
                                        <div class="d-flex align-items-center">
                                            <div class="file-type-icon excel-bg me-3" style="flex-shrink: 0;">
                                                <i data-lucide="file-spreadsheet" class="text-white"></i>
                                            </div>
                                            <div class="text-start overflow-hidden flex-grow-1">
                                                <h6 class="fw-bold mb-0 text-truncate" style="font-size: 12.5px;" id="ui_selected_file_name">filename.xlsx</h6>
                                                <p class="text-muted small mb-0" style="font-size: 11px;" id="ui_selected_file_size">0 KB</p>
                                            </div>
                                            <button type="button" class="btn btn-link text-danger p-0 ms-2" id="ui_reset_file_btn" title="Ganti File">
                                                <i data-lucide="x-circle" style="width: 18px; height: 18px;"></i>
                                            </button>
                                        </div>
                                        <div class="status-badge mt-2 bg-success-soft text-success small py-1 px-3 rounded-pill d-inline-block" style="font-size: 10.5px; font-weight: 700;">
                                            <i data-lucide="shield-check" class="me-1" style="width:12px;"></i> Siap disegel SHA-256 &amp; dijadwalkan Priority Score
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div id="ui_progress_container" class="d-none mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-primary" style="font-size: 11.5px;" id="ui_status_text">Mengupload file...</span>
                            <span class="small fw-bold text-muted" style="font-size: 11.5px;" id="ui_percentage">0%</span>
                        </div>
                        <div class="progress rounded-pill" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%" id="ui_progress_bar"></div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light-soft border-top px-4 py-3">
                    <button type="button" class="btn btn-light border rounded-3 px-3" id="ui_cancel_btn" data-bs-dismiss="modal" style="font-size: 12px; font-weight: 600;">Batal</button>
                    <button type="submit" class="btn btn-success rounded-3 px-4 d-inline-flex align-items-center gap-2" id="ui_submit_btn" style="font-size: 12px; font-weight: 700; background: #10b981; border-color: #10b981;">
                        <i data-lucide="upload-cloud" style="width: 16px; height: 16px;"></i>
                        <span>Mulai Import &amp; Segel SHA-256</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: DETAIL RESI MANIFEST                                             -->
<!-- ========================================================================= -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0 ps-4 pe-4">
                <div>
                    <h5 class="modal-title fw-bold" id="detailFileName">Detail Laporan</h5>
                    <p class="text-muted small mb-0" id="detailMeta">Ekspedisi: - | Tanggal: -</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0" id="detailTable">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3 py-2">Resi</th>
                                <th class="py-2">Penerima</th>
                                <th class="py-2">Alamat</th>
                                <th class="py-2">Ekspedisi</th>
                                <th class="py-2">Status</th>
                                <th class="py-2">Segel Resi (SHA-256)</th>
                                <th class="py-2">Waktu Scan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 4: EXPORT LAPORAN EXCEL MULTI-EKSPEDISI & MANIFEST                    -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalExportEkspedisi" tabindex="-1" aria-labelledby="modalExportEkspedisiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered my-3" style="max-width: 860px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="max-height: 92vh; display: flex; flex-direction: column;">
            <!-- Modal Header (Sleek & Professional) -->
            <div class="modal-header border-0 px-4 py-3 text-white flex-shrink-0" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 38px; height: 38px; flex-shrink: 0; background: rgba(255, 255, 255, 0.2); color: #ffffff;">
                        <i data-lucide="file-spreadsheet" style="width: 20px; height: 20px; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="modalExportEkspedisiLabel" style="font-size: 16px; letter-spacing: -0.2px;">
                            Export Laporan Excel Multi-Ekspedisi
                        </h6>
                        <p class="text-white-50 mb-0" style="font-size: 12px;">
                            Pilih ekspedisi dan file manifest yang ingin diekspor ke format Excel (.xlsx) profesional
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="<?= url('/hasil-ekspedisi/export') ?>" method="GET" id="formExportEkspedisi" class="no-loading m-0 d-flex flex-column" style="overflow: hidden;">
                <!-- Modal Body: Spacing Lega & Bernapas (p-4 bg-light-soft) -->
                <div class="modal-body p-4 bg-light-soft" style="overflow-y: auto;">
                    <!-- SATU KOTAK FORM TERPADU DENGAN VERTICAL GAP PROPORSIONAL (gap: 1.35rem) -->
                    <div class="p-4 bg-white rounded-3 border shadow-xs d-flex flex-column" style="gap: 1.35rem;">
                        
                        <!-- 1. PILIH EKSPEDISI -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2.5">
                                <label class="d-flex align-items-center gap-2 mb-0" style="font-size: 13px; font-weight: 700; color: #1e293b;">
                                    <i data-lucide="truck" style="width: 16px; height: 16px; color: #2563eb;"></i>
                                    <span>1. Pilih Ekspedisi yang Ingin Diekspor</span>
                                    <span class="text-danger">*</span>
                                </label>
                                <span class="badge bg-primary-soft text-primary px-2.5 py-1" id="expCurrentBadge" style="font-size: 11px; font-weight: 700; border-radius: 6px;">SEMUA EKSPEDISI</span>
                            </div>

                            <!-- Barisan Pill Button Ekspedisi (flex flex-wrap gap-2 dengan margin-bottom mb-3 yang cukup) -->
                            <div class="d-flex flex-wrap gap-2 mb-3" id="exportEkspedisiPills">
                                <button type="button" class="btn btn-sm exp-pill-btn active" data-eksp="ALL">
                                    <i data-lucide="layers" style="width: 13px; height: 13px;"></i> SEMUA (ALL)
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="INSTAN" style="border-color: #fdba74; color: #c2410c;">
                                    <i data-lucide="zap" style="width: 13px; height: 13px; color: #ea580c;"></i> INSTAN
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="SPX">
                                    <i data-lucide="package" style="width: 13px; height: 13px; color: #2563eb;"></i> SPX
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="JNE">
                                    <i data-lucide="truck" style="width: 13px; height: 13px; color: #dc2626;"></i> JNE
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="JNT">
                                    <i data-lucide="truck" style="width: 13px; height: 13px; color: #d97706;"></i> JNT
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="LEX">
                                    <i data-lucide="truck" style="width: 13px; height: 13px; color: #0891b2;"></i> LEX
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="ID">
                                    <i data-lucide="truck" style="width: 13px; height: 13px; color: #4f46e5;"></i> ID
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="NINJA">
                                    <i data-lucide="truck" style="width: 13px; height: 13px; color: #db2777;"></i> NINJA
                                </button>
                                <button type="button" class="btn btn-sm exp-pill-btn" data-eksp="SICEPAT">
                                    <i data-lucide="truck" style="width: 13px; height: 13px; color: #16a34a;"></i> SICEPAT
                                </button>
                            </div>

                            <!-- Dropdown Select Ekspedisi -->
                            <select name="ekspedisi" id="exportEkspedisiSelect" class="form-select he-modal-input" style="height: 38px; font-size: 12.5px; border-radius: 8px;">
                                <option value="ALL" selected>-- SEMUA EKSPEDISI (Laporan Universal Gabungan) --</option>
                                <option value="INSTAN">⚡ INSTAN / SAMEDAY — Antrean Paket Prioritas (1 Paket 1 Kurir)</option>
                                <option value="SPX">📦 SPX — Shopee Xpress</option>
                                <option value="JNE">📦 JNE — JNE Express</option>
                                <option value="JNT">📦 JNT — J&amp;T Express</option>
                                <option value="LEX">📦 LEX — Lazada Express (LEX)</option>
                                <option value="ID">📦 ID — ID Express</option>
                                <option value="NINJA">📦 NINJA — Ninja Xpress</option>
                                <option value="SICEPAT">📦 SICEPAT — SiCepat Ekspres</option>
                            </select>
                        </div>

                        <!-- 2. PILIHAN FILE MANIFEST TERUPLOAD -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="d-flex align-items-center gap-2 mb-0" style="font-size: 13px; font-weight: 700; color: #1e293b;">
                                    <i data-lucide="file-check" style="width: 16px; height: 16px; color: #10b981;"></i>
                                    <span>2. Pilihan File Manifest Terupload</span>
                                </label>
                                <span class="text-muted" style="font-size: 11.5px;">Opsional (Pilih file tertentu atau seluruh batch)</span>
                            </div>
                            <select name="file_id" id="exportFileIdSelect" class="form-select he-modal-input mb-1.5" style="height: 38px; font-size: 12.5px; border-radius: 8px;">
                                <option value="0" data-eksp="ALL">-- Semua File Manifest / Seluruh Batch --</option>
                                <?php if (!empty($manifestFiles)): ?>
                                    <?php foreach ($manifestFiles as $mf): ?>
                                        <option value="<?= $mf['id'] ?>"
                                                data-eksp="<?= strtoupper(e($mf['ekspedisi'])) ?>"
                                                data-spx="<?= (int)($mf['total_spx'] ?? 0) ?>"
                                                data-instan="<?= (int)($mf['total_instan'] ?? 0) ?>"
                                                data-jne="<?= (int)($mf['total_jne'] ?? 0) ?>"
                                                data-jnt="<?= (int)($mf['total_jnt'] ?? 0) ?>"
                                                data-lex="<?= (int)($mf['total_lex'] ?? 0) ?>"
                                                data-id="<?= (int)($mf['total_id'] ?? 0) ?>"
                                                data-ninja="<?= (int)($mf['total_ninja'] ?? 0) ?>"
                                                data-sicepat="<?= (int)($mf['total_sicepat'] ?? 0) ?>">
                                            [ID: <?= $mf['id'] ?>] <?= e($mf['nama_file']) ?> (<?= number_format($mf['jumlah_paket']) ?> Pkt) [<?= e($mf['ekspedisi']) ?>]
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <div class="text-muted" style="font-size: 11.5px;">
                                <i data-lucide="info" style="width: 13px; height: 13px; vertical-align: middle;"></i> Daftar file otomatis menyesuaikan dengan ekspedisi yang Anda pilih di atas.
                            </div>
                        </div>

                        <!-- 3. RENTANG TANGGAL FILTER (Grid 2 Kolom Responsif & Quick-Filter di Header) -->
                        <div>
                            <!-- Header Baris Atas: Label & Quick Filter Buttons -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <label class="d-flex align-items-center gap-2 mb-0" style="font-size: 13px; font-weight: 700; color: #1e293b;">
                                    <i data-lucide="calendar" style="width: 16px; height: 16px; color: #6366f1;"></i>
                                    <span>3. Rentang Tanggal Filter</span>
                                </label>
                                <div class="d-flex align-items-center gap-1.5">
                                    <button type="button" class="btn btn-xs date-preset-btn" data-range="today">Hari Ini</button>
                                    <button type="button" class="btn btn-xs date-preset-btn" data-range="week">7 Hari</button>
                                    <button type="button" class="btn btn-xs date-preset-btn" data-range="month">Bulan Ini</button>
                                    <button type="button" class="btn btn-xs date-preset-btn active" data-range="all">Semua</button>
                                </div>
                            </div>

                            <!-- Baris Input Tanggal (CSS Grid / Row 2 Kolom Sejajar & Rapi) -->
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label for="exportStartDate" class="form-label text-muted mb-1" style="font-size: 11.5px; font-weight: 600;">Tanggal Mulai</label>
                                    <input type="date" name="start_date" id="exportStartDate" class="form-control he-modal-input" style="height: 38px; font-size: 12.5px; border-radius: 8px;" value="<?= e($startDate) ?>">
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="exportEndDate" class="form-label text-muted mb-1" style="font-size: 11.5px; font-weight: 600;">Tanggal Selesai</label>
                                    <input type="date" name="end_date" id="exportEndDate" class="form-control he-modal-input" style="height: 38px; font-size: 12.5px; border-radius: 8px;" value="<?= e($endDate) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- 4. SPESIFIKASI FORMAT EXCEL & SHA-256 (Box Hijau Bernafas Lega, Padding Nyaman, Radius Halus) -->
                        <div class="p-3.5 p-sm-4 rounded-3" style="background: #f0fdf4; border: 1px solid #bbf7d0; margin-top: 0.25rem;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <span style="font-size: 12.5px; font-weight: 700; color: #166534;" class="d-inline-flex align-items-center gap-1.5">
                                    <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i> Spesifikasi Format File Excel (.xlsx):
                                </span>
                                <span class="badge bg-success text-white px-2.5 py-1" style="font-size: 10.5px; font-weight: 700; border-radius: 6px;">
                                    <i data-lucide="shield-check" style="width: 12px; height: 12px; vertical-align: middle;"></i> Tersegel SHA-256
                                </span>
                            </div>
                            <div id="exportPreviewText" style="font-size: 11.5px; color: #15803d; line-height: 1.55;">
                                <strong>Format Universal Semua Ekspedisi (9 Kolom):</strong> <em>No, No Resi / Waybill, Nama Penerima, Alamat / Tujuan, Ekspedisi, Status Scan, Tanggal Scan, File Manifest,</em> dan <em>Segel Hash (SHA-256)</em>.
                            </div>
                        </div>

                    </div>
                    <!-- END SATU KOTAK UTUH -->
                </div>

                <!-- Modal Footer (Lega, Rapi, Jarak Terjaga) -->
                <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
                    <button type="button" class="btn btn-light border rounded-3 px-3.5 py-2" data-bs-dismiss="modal" style="font-size: 12.5px; font-weight: 600; color: #475569;">
                        Tutup
                    </button>
                    <button type="submit" class="btn btn-success rounded-3 px-4 py-2.5 d-inline-flex align-items-center gap-2" id="btnSubmitExportExcel" style="font-size: 13px; font-weight: 800; background: #059669; border-color: #059669; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3); letter-spacing: 0.2px; cursor: pointer;">
                        <i data-lucide="download" style="width: 17px; height: 17px;"></i>
                        <span>TARIK &amp; DOWNLOAD FILE EXCEL (.XLSX)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-soft: rgba(59, 130, 246, 0.1);
        --success-soft: rgba(16, 185, 129, 0.1);
        --light-soft: #f8fafc;
    }

    .bg-primary-soft { background-color: var(--primary-soft); }
    .bg-success-soft { background-color: var(--success-soft); }
    .bg-light-soft { background-color: var(--light-soft); }
    .rounded-4 { border-radius: 1rem !important; }

    /* Header & Consolidated Action Bar */
    .he-page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.85rem;
        flex-wrap: wrap;
    }

    .he-action-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 0.6rem;
    }

    .he-toolbar-group {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
        padding: 5px;
        border-radius: 12px;
    }

    .he-toolbar-primary {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.03);
    }

    .he-toolbar-danger {
        background: #fff1f2;
        border: 1px solid #ffe4e6;
    }

    .he-action-btn {
        padding: 6px 11px;
        border-radius: 9px;
        font-size: 11.5px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.18s ease;
        white-space: nowrap;
        text-decoration: none;
        line-height: 1.2;
    }

    .he-btn-create {
        background: #2563eb;
        color: #ffffff;
        border: 1px solid #2563eb;
    }
    .he-btn-create:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #ffffff;
        transform: translateY(-1px);
    }

    .he-btn-recalc {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        font-weight: 700;
    }
    .he-btn-recalc:hover {
        background: #dbeafe;
        border-color: #93c5fd;
    }

    .he-btn-export {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .he-btn-export:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .he-btn-import {
        background: #10b981;
        color: #ffffff;
        border: 1px solid #10b981;
        box-shadow: 0 3px 10px rgba(16, 185, 129, 0.16);
    }
    .he-btn-import:hover {
        background: #059669;
        border-color: #059669;
        color: #ffffff;
        transform: translateY(-1px);
    }

    /* Multi-Ekspedisi Export Modal Styling */
    .shadow-xs {
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .exp-pill-btn {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-weight: 600;
        font-size: 12px;
        padding: 6px 13px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .exp-pill-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }
    .exp-pill-btn.active {
        background: #1e40af !important;
        border-color: #1e40af !important;
        color: #ffffff !important;
        box-shadow: 0 3px 8px rgba(30, 64, 175, 0.25);
    }
    .exp-pill-btn.active i, .exp-pill-btn.active [data-lucide] {
        color: #ffffff !important;
    }
    .date-preset-btn {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 7px;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
    }
    .date-preset-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }
    .date-preset-btn.active {
        background: #334155 !important;
        color: #ffffff !important;
        border-color: #334155 !important;
    }

    .he-btn-danger-outline {
        background: #ffffff;
        color: #ef4444;
        border: 1px solid #fecdd3;
    }
    .he-btn-danger-outline:hover {
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .he-btn-danger-solid {
        background: #ef4444;
        color: #ffffff;
        border: 1px solid #ef4444;
        box-shadow: 0 3px 10px rgba(239, 68, 68, 0.16);
    }
    .he-btn-danger-solid:hover {
        background: #dc2626;
        border-color: #dc2626;
        transform: translateY(-1px);
    }

    /* Compact Queue Status Strip */
    .he-queue-strip {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        padding: 7px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.02);
    }

    .he-qstat {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .he-qstat strong {
        color: #0f172a;
        font-weight: 700;
    }
    .he-qdot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .he-queue-link {
        font-size: 11px;
        font-weight: 700;
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        padding: 4px 10px;
        border-radius: 7px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .he-queue-link:hover {
        background: #dbeafe;
        color: #1e40af;
    }

    /* Modal Form Controls */
    .he-modal-label {
        font-size: 11.5px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 3px;
        display: block;
    }

    .he-modal-input {
        height: 35px;
        min-height: 35px;
        border-radius: 8px;
        border-color: #cbd5e1;
        font-size: 12px;
        color: #0f172a;
    }
    .he-modal-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .he-modal-input:disabled {
        background-color: #f1f5f9 !important;
        color: #94a3b8 !important;
        cursor: not-allowed;
    }
    
    .hidden-input {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }

    .upload-container { position: relative; }

    .upload-drop-zone {
        border: 2px dashed #cbd5e1;
        background: #fdfdfd;
        border-radius: 1rem;
        transition: all 0.25s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .upload-drop-zone:hover, .upload-drop-zone.dragover {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .upload-icon-wrapper {
        position: relative;
        display: inline-block;
    }

    .file-type-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .excel-bg { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .preview-card { animation: zoomIn 0.25s ease-out; }

    @keyframes zoomIn {
        from { opacity: 0; transform: scale(0.96); }
        to { opacity: 1; transform: scale(1); }
    }

    .bg-blue-soft { background-color: rgba(59, 130, 246, 0.1); }
    .bg-green-soft { background-color: rgba(16, 185, 129, 0.1); }
    .bg-purple-soft { background-color: rgba(139, 92, 246, 0.1); }
    .bg-orange-soft { background-color: rgba(245, 158, 11, 0.1); }
    
    .text-purple { color: #8b5cf6; }
    .text-orange { color: #f59e0b; }

    .btn-icon-verify {
        color: #047857;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        padding: 5px 10px;
        border-radius: 6px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
    }
    .btn-icon-verify:hover { background: #10b981; color: white; border-color: #10b981; }
    
    .btn-icon-view { color: #3b82f6; background: rgba(59, 130, 246, 0.08); border: none; padding: 6px 10px; border-radius: 6px; transition: all 0.2s; }
    .btn-icon-view:hover { background: #3b82f6; color: white; }
    
    .btn-icon-download { color: #10b981; background: rgba(16, 185, 129, 0.08); border: none; padding: 6px 10px; border-radius: 6px; transition: all 0.2s; text-decoration: none; }
    .btn-icon-download:hover { background: #10b981; color: white; }
    
    .btn-icon-delete { color: #ef4444; background: rgba(239, 68, 68, 0.08); border: none; padding: 6px 10px; border-radius: 6px; transition: all 0.2s; }
    .btn-icon-delete:hover { background: #ef4444; color: white; }
    
    .table-hover tbody tr:hover { background-color: #f8fafc; }
    
    .pagination .page-link {
        border: none;
        color: #64748b;
        padding: 0.4rem 0.8rem;
        margin: 0 2px;
        border-radius: 6px;
    }
    .pagination .page-item.active .page-link {
        background-color: #3b82f6;
        color: white;
    }

    @media (max-width: 991.98px) {
        .he-action-toolbar {
            justify-content: flex-start;
            width: 100%;
        }
    }
</style>

<script>
    function escManifestHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function formatDisplayDate(ymd) {
        if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(ymd)) return ymd || '-';
        const parts = ymd.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ── 1. INISIALISASI GRAFIK EKSPEDISI ──
        const lineCanvas = document.getElementById('expeditionLineChart');
        const doughnutCanvas = document.getElementById('expeditionDoughnutChart');

        if (lineCanvas && doughnutCanvas && typeof Chart !== 'undefined') {
            const ctxLine = lineCanvas.getContext('2d');
            const ctxDoughnut = doughnutCanvas.getContext('2d');
            
            const labels = ['SPX', 'INSTAN', 'JNE', 'JNT', 'LEX', 'ID', 'NINJA', 'SICEPAT'];
            const dataValues = [
                <?= (int)$stats['total_paket_spx'] ?>,
                <?= (int)$stats['total_paket_instan'] ?>,
                <?= (int)$stats['total_paket_jne'] ?>,
                <?= (int)$stats['total_paket_jnt'] ?>,
                <?= (int)$stats['total_paket_lex'] ?>,
                <?= (int)$stats['total_paket_id'] ?>,
                <?= (int)$stats['total_paket_ninja'] ?>,
                <?= (int)$stats['total_paket_sicepat'] ?>
            ];
            
            const colors = ['#0d6efd', '#f97316', '#dc3545', '#eab308', '#06b2d4', '#4f46e5', '#ec4899', '#198754'];

            const gradient = ctxLine.createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(13, 110, 253, 0.2)');
            gradient.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Volume Paket',
                        data: dataValues,
                        borderColor: '#0d6efd',
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#0d6efd',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#0d6efd',
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8, displayColors: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [5, 5], color: '#e2e8f0' },
                            ticks: { font: { size: 10 }, color: '#64748b' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10, weight: '600' }, color: '#64748b' }
                        }
                    }
                }
            });

            new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: colors,
                        hoverOffset: 15,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#1e293b', padding: 12, cornerRadius: 8 }
                    }
                }
            });
        }

        // ── 2. SINKRONISASI INPUT FILTER KE FORM EXPORT & MODAL ──
        function syncToolbarFilterInputs() {
            const sVal = document.getElementById('heSearchInput')?.value || '';
            const eVal = document.getElementById('heEkspedisiFilter')?.value || '';
            const sdVal = document.getElementById('heStartDateFilter')?.value || '';
            const edVal = document.getElementById('heEndDateFilter')?.value || '';

            if (document.getElementById('heExportSearch')) document.getElementById('heExportSearch').value = sVal;
            if (document.getElementById('heExportEkspedisi')) document.getElementById('heExportEkspedisi').value = eVal;
            if (document.getElementById('heExportStartDate')) document.getElementById('heExportStartDate').value = sdVal;
            if (document.getElementById('heExportEndDate')) document.getElementById('heExportEndDate').value = edVal;

            const activeDate = sdVal || edVal || '<?= e($activeQueueDate) ?>';
            if (document.getElementById('ui_default_tanggal')) {
                document.getElementById('ui_default_tanggal').value = activeDate;
            }
        }

        ['heSearchInput', 'heEkspedisiFilter', 'heStartDateFilter', 'heEndDateFilter'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', syncToolbarFilterInputs);
                el.addEventListener('input', syncToolbarFilterInputs);
            }
        });

        // ── 3. AUTO-SYNC PARAMETER MODAL TAMBAH PAKET ──
        const cpEksp = document.getElementById('cp_nama_ekspedisi');
        const cpLayanan = document.getElementById('cp_layanan_ekspedisi');
        const cpCutoff = document.getElementById('cp_cutoff_time');
        const cpSesi = document.getElementById('cp_sesi');

        if (cpEksp) {
            cpEksp.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (!opt) return;
                const defLayanan = opt.getAttribute('data-layanan');
                const defCutoff = opt.getAttribute('data-cutoff');
                if (defLayanan && cpLayanan) cpLayanan.value = defLayanan;
                if (defCutoff && cpCutoff) cpCutoff.value = defCutoff;
                if (cpSesi) {
                    if (this.value === 'INSTANT' || this.value === 'SAMEDAY') cpSesi.value = 'INSTAN';
                    else if (cpSesi.value === 'INSTAN' || cpSesi.value === 'SAMEDAY' || cpSesi.value === 'INSTAN / SAMEDAY') cpSesi.value = 'SESI 1';
                }
            });
        }

        // ── 4. MODAL IMPORT EXCEL TERPADU (MANIFEST SHA-256 + PRIORITY SCHEDULING) ──
        const uiEksp = document.getElementById('ui_nama_ekspedisi');
        const uiLayanan = document.getElementById('ui_layanan_ekspedisi');
        const uiCutoff = document.getElementById('ui_cutoff_time');
        const uiMode = document.getElementById('ui_import_mode');
        const uiPreview = document.getElementById('ui_preview_badge');
        const uiDropZone = document.getElementById('ui_drop_zone');
        const uiFileInput = document.getElementById('ui_file_input');
        const uiBrowseBtn = document.getElementById('ui_browse_btn');
        const uiResetBtn = document.getElementById('ui_reset_file_btn');
        const uiSubmitBtn = document.getElementById('ui_submit_btn');
        const uiCancelBtn = document.getElementById('ui_cancel_btn');
        const uiCloseBtn = document.getElementById('ui_close_btn');
        const uiForm = document.getElementById('unifiedImportForm');

        let selectedUnifiedFile = null;
        let isUnifiedUploading = false;

        function updateUnifiedPreview() {
            if (!uiEksp || !uiPreview) return;
            const ekspVal = uiEksp.value;

            if (!ekspVal) {
                uiMode.value = 'manual';
                uiLayanan.disabled = false;
                uiCutoff.disabled = false;
                uiPreview.innerHTML = 'Belum dipilih (pilih ekspedisi di atas)';
                uiPreview.style.cssText = 'font-size: 11px; font-weight: 700; color: #94a3b8;';
                return;
            }

            if (ekspVal === 'AUTO' || ekspVal === 'NON SPX') {
                uiMode.value = 'auto';
                uiLayanan.disabled = true;
                uiCutoff.disabled = true;
                const desc = ekspVal === 'NON SPX'
                    ? 'NON SPX • Deteksi Multi-Sheet / Format Resi + Segel SHA-256'
                    : 'AUTO • Deteksi Kolom Excel / Format Resi + Segel SHA-256';
                uiPreview.innerHTML = desc;
                uiPreview.style.cssText = 'font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;';
                return;
            }

            uiMode.value = 'manual';
            uiLayanan.disabled = false;
            uiCutoff.disabled = false;

            const layananVal = uiLayanan.value || 'REGULER';
            const cutoffVal = uiCutoff.value || '17:00';
            const slaColors = {
                INSTANT: { bg: '#fef2f2', color: '#dc2626', border: '#fecaca', label: 'INSTAN / SAMEDAY' },
                SAMEDAY: { bg: '#fef2f2', color: '#dc2626', border: '#fecaca', label: 'INSTAN / SAMEDAY' },
                EXPRESS: { bg: '#eff6ff', color: '#2563eb', border: '#bfdbfe', label: 'EXPRESS' },
                REGULER: { bg: '#f1f5f9', color: '#475569', border: '#cbd5e1', label: 'REGULER' },
                CARGO:   { bg: '#f5f3ff', color: '#7c3aed', border: '#ddd6fe', label: 'CARGO' }
            };
            const st = slaColors[layananVal] || slaColors.REGULER;
            const slaText = st.label || layananVal;

            const isInstanMode = (ekspVal === 'INSTANT' || ekspVal === 'SAMEDAY' || ekspVal === 'INSTAN' || layananVal === 'INSTANT' || layananVal === 'SAMEDAY');
            const displayEksp = (ekspVal === 'INSTANT' || ekspVal === 'SAMEDAY') ? 'INSTAN / SAMEDAY' : ekspVal;
            const targetBadge = isInstanMode 
                ? '<span style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; font-size:9.5px; font-weight:800; padding:1px 6px; border-radius:4px; margin-left:4px;">Antrean Instan + SHA-256</span>'
                : '<span style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:9.5px; font-weight:800; padding:1px 6px; border-radius:4px; margin-left:4px;">Hasil Ekspedisi + SHA-256</span>';

            uiPreview.innerHTML = `<strong style="color:#0f172a;">${escManifestHtml(displayEksp)}</strong> &bull; <span style="background:${st.bg}; color:${st.color}; border:1px solid ${st.border}; padding:1px 6px; border-radius:5px; font-weight:800; font-size:10px;">${escManifestHtml(slaText)}</span> &bull; <span style="color:#475569;">Cut-off ${escManifestHtml(cutoffVal)}</span> ${targetBadge}`;
            uiPreview.style.cssText = 'font-size: 11px; font-weight: 700;';
        }

        if (uiEksp) {
            uiEksp.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt && this.value !== 'AUTO' && this.value !== 'NON SPX' && this.value !== '') {
                    const defLayanan = opt.getAttribute('data-layanan');
                    const defCutoff = opt.getAttribute('data-cutoff');
                    if (defLayanan && uiLayanan) uiLayanan.value = defLayanan;
                    if (defCutoff && uiCutoff) uiCutoff.value = defCutoff;
                }
                updateUnifiedPreview();
            });
        }
        if (uiLayanan) uiLayanan.addEventListener('change', updateUnifiedPreview);
        if (uiCutoff) uiCutoff.addEventListener('change', updateUnifiedPreview);

        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function resetUnifiedFile() {
            if (isUnifiedUploading) return;
            selectedUnifiedFile = null;
            if (uiFileInput) uiFileInput.value = '';
            document.getElementById('ui_upload_placeholder').classList.remove('d-none');
            document.getElementById('ui_file_selected').classList.add('d-none');
        }

        function handleUnifiedFiles(files) {
            if (!files || files.length === 0) return;
            const file = files[0];
            const ext = file.name.split('.').pop().toLowerCase();
            const maxSize = 10 * 1024 * 1024;

            if (!['xlsx', 'xls', 'csv'].includes(ext)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Format Salah',
                    text: 'Silakan pilih file dengan format .xlsx, .xls, atau .csv',
                    confirmButtonColor: '#ef4444'
                });
                resetUnifiedFile();
                return;
            }

            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Terlalu Besar',
                    text: 'Ukuran maksimal file adalah 10MB',
                    confirmButtonColor: '#ef4444'
                });
                resetUnifiedFile();
                return;
            }

            selectedUnifiedFile = file;
            document.getElementById('ui_upload_placeholder').classList.add('d-none');
            document.getElementById('ui_file_selected').classList.remove('d-none');
            document.getElementById('ui_selected_file_name').textContent = file.name;
            document.getElementById('ui_selected_file_size').textContent = formatBytes(file.size);
        }

        if (uiBrowseBtn) {
            uiBrowseBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!isUnifiedUploading) uiFileInput.click();
            });
        }

        if (uiResetBtn) {
            uiResetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                resetUnifiedFile();
            });
        }

        if (uiDropZone) {
            uiDropZone.addEventListener('click', (e) => {
                if (isUnifiedUploading) return;
                if (e.target.closest('button')) return;
                if (document.getElementById('ui_file_selected').classList.contains('d-none')) {
                    uiFileInput.click();
                }
            });

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evtName => {
                uiDropZone.addEventListener(evtName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });
            ['dragenter', 'dragover'].forEach(evtName => {
                uiDropZone.addEventListener(evtName, () => uiDropZone.classList.add('dragover'), false);
            });
            ['dragleave', 'drop'].forEach(evtName => {
                uiDropZone.addEventListener(evtName, () => uiDropZone.classList.remove('dragover'), false);
            });
            uiDropZone.addEventListener('drop', (e) => {
                if (isUnifiedUploading) return;
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    handleUnifiedFiles(dt.files);
                }
            });
        }

        if (uiFileInput) {
            uiFileInput.addEventListener('change', function() {
                handleUnifiedFiles(this.files);
            });
        }

        if (uiForm) {
            uiForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (isUnifiedUploading) return;

                if (!uiEksp.value) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilih Kategori Ekspedisi',
                        text: 'Silakan pilih Ekspedisi Penjemput / Kategori Batch terlebih dahulu (misal: INSTANT, SAMEDAY, SPX, JNE, atau NON SPX).',
                        confirmButtonColor: '#2563eb'
                    }).then(() => uiEksp.focus());
                    return;
                }

                const activeFile = selectedUnifiedFile || (uiFileInput.files && uiFileInput.files[0]);
                if (!activeFile) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'File Belum Dipilih',
                        text: 'Silakan pilih file Excel (.xlsx, .xls) atau .csv terlebih dahulu.',
                        confirmButtonColor: '#2563eb'
                    });
                    return;
                }

                syncToolbarFilterInputs();
                isUnifiedUploading = true;

                const formData = new FormData(uiForm);
                formData.set('excel_file', activeFile);

                const progressContainer = document.getElementById('ui_progress_container');
                const progressBar = document.getElementById('ui_progress_bar');
                const percentageText = document.getElementById('ui_percentage');
                const statusText = document.getElementById('ui_status_text');

                progressContainer.classList.remove('d-none');
                progressBar.style.width = '0%';
                percentageText.textContent = '0%';
                statusText.textContent = 'Mengupload file...';
                uiSubmitBtn.disabled = true;
                uiCancelBtn.disabled = true;
                uiCloseBtn.disabled = true;

                $.ajax({
                    url: uiForm.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        const xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(evt) {
                            if (evt.lengthComputable) {
                                const pct = Math.round((evt.loaded / evt.total) * 100);
                                progressBar.style.width = pct + '%';
                                percentageText.textContent = pct + '%';
                                if (pct === 100) {
                                    statusText.textContent = 'Menyegel Aggregate SHA-256 & menghitung Priority Score...';
                                }
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(res) {
                        if (res && res.success) {
                            progressBar.style.width = '100%';
                            percentageText.textContent = '100%';
                            statusText.textContent = 'Selesai! Memuat ulang laporan...';

                            setTimeout(() => {
                                if (res.redirect_date) {
                                    window.location.href = '<?= url('/hasil-ekspedisi') ?>?start_date=' + encodeURIComponent(res.redirect_date) + '&end_date=' + encodeURIComponent(res.redirect_date);
                                } else {
                                    window.location.reload();
                                }
                            }, 300);
                        } else {
                            isUnifiedUploading = false;
                            progressContainer.classList.add('d-none');
                            uiSubmitBtn.disabled = false;
                            uiCancelBtn.disabled = false;
                            uiCloseBtn.disabled = false;
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Import',
                                text: (res && res.message) ? res.message : 'Gagal memproses file.',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    },
                    error: function(xhr) {
                        isUnifiedUploading = false;
                        progressContainer.classList.add('d-none');
                        uiSubmitBtn.disabled = false;
                        uiCancelBtn.disabled = false;
                        uiCloseBtn.disabled = false;
                        let errMsg = 'Terjadi kesalahan saat menghubungi server.';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                            errMsg = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Gagal',
                            text: errMsg,
                            confirmButtonColor: '#ef4444'
                        });
                    }
                });
            });
        }

        // ── 5. TOMBOL HITUNG ULANG SKOR PRIORITAS REAL-TIME ──
        $('#btnRecalculatePriority').on('click', function() {
            const btn = $(this);
            const startDate = $('#heStartDateFilter').val() || '';
            const endDate = $('#heEndDateFilter').val() || '';

            btn.prop('disabled', true).css('opacity', '0.7');
            $.ajax({
                url: '<?= url('/paket/recalculate-priority') ?>',
                type: 'POST',
                data: {
                    _csrf_token: '<?= csrf_token() ?>',
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(res) {
                    btn.prop('disabled', false).css('opacity', '1');
                    if (res && res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Skor Prioritas Diperbarui!',
                            text: res.message,
                            timer: 2600,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    } else {
                        Swal.fire('Gagal', (res && res.message) ? res.message : 'Gagal menghitung ulang skor.', 'error');
                    }
                },
                error: function() {
                    btn.prop('disabled', false).css('opacity', '1');
                    Swal.fire('Error', 'Gagal menghubungi server saat rekalkulasi skor.', 'error');
                }
            });
        });

        // ── 6. AUDIT KEASLIAN MANIFEST SHA-256 ──
        $('.btn-verify-manifest').on('click', function () {
            const id = $(this).data('id');
            const fileName = $(this).data('file');

            Swal.fire({
                title: 'Memverifikasi Manifest SHA-256...',
                text: 'Menghitung ulang Aggregate Hash atas seluruh nomor resi di database...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '<?= url("/hasil-ekspedisi/") ?>' + id + '/verify',
                type: 'POST',
                data: { _csrf_token: '<?= csrf_token() ?>' },
                success: function (res) {
                    if (!res || !res.success) {
                        Swal.fire('Gagal', res?.message || 'Manifest tidak ditemukan.', 'error');
                        return;
                    }
                    const v = res.verification || {};
                    const st = String(v.status || 'VALID').toUpperCase();
                    const iconType = st === 'VALID' ? 'success' : (st === 'LEGACY' ? 'info' : 'error');

                    let discHtml = '';
                    if (Array.isArray(v.discrepancies) && v.discrepancies.length > 0) {
                        discHtml = `<div style="background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; border-radius:8px; padding:8px 10px; margin-bottom:10px; font-size:12px;">
                            <strong>Temuan Forensik:</strong><br>${v.discrepancies.map(escManifestHtml).join('<br>')}
                        </div>`;
                    }

                    Swal.fire({
                        icon: iconType,
                        title: st === 'VALID' ? 'Manifest Terverifikasi Utuh!' : (st === 'LEGACY' ? 'Manifest Historis (Legacy)' : 'Peringatan Manipulasi Manifest!'),
                        width: 600,
                        html: `
                            <div style="text-align:left; font-size:13px; color:#334155;">
                                <div style="margin-bottom:8px;"><strong>File:</strong> ${escManifestHtml(fileName)} • <strong>Total Resi DB:</strong> ${escManifestHtml(v.total_resi_db)} resi</div>
                                <p style="margin-bottom:10px;">${escManifestHtml(v.message || '')}</p>
                                ${discHtml}
                                <div style="background:#0f172a; color:#e2e8f0; border-radius:10px; padding:10px 12px; font-size:11px;">
                                    <div style="color:#94a3b8; font-size:10px; font-weight:700;">RECORDED MANIFEST HASH (DATABASE):</div>
                                    <code style="color:#38bdf8; word-break:break-all; display:block; margin-bottom:8px;">${escManifestHtml(v.recorded_hash || 'Belum memiliki segel (Legacy)')}</code>
                                    <div style="color:#94a3b8; font-size:10px; font-weight:700;">COMPUTED AGGREGATE HASH (SAAT INI):</div>
                                    <code style="color:${v.is_valid ? '#4ade80' : '#f87171'}; word-break:break-all; display:block;">${escManifestHtml(v.computed_hash || '-')}</code>
                                </div>
                            </div>
                        `,
                        confirmButtonColor: st === 'VALID' ? '#10b981' : '#3b82f6',
                        confirmButtonText: 'Tutup Hasil Audit'
                    });
                },
                error: function () {
                    Swal.fire('Error', 'Gagal menghubungi server saat memverifikasi manifest.', 'error');
                }
            });
        });

        // ── 8. LIHAT DETAIL RESI MANIFEST (AJAX) ──
        $('.btn-show-detail').on('click', function() {
            const id = $(this).data('id');
            const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
            
            $('#detailTable tbody').html('<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat data...</td></tr>');
            detailModal.show();
            
            $.get('<?= url("/hasil-ekspedisi/") ?>' + id + '/show', function(response) {
                if (response.success) {
                    $('#detailFileName').text(response.header.nama_file);
                    const mHashInfo = response.header.manifest_hash
                        ? ' | Manifest SHA-256: ' + response.header.manifest_hash.substring(0, 16) + '...'
                        : '';
                    $('#detailMeta').text('Ekspedisi: ' + response.header.ekspedisi + ' | Diupload: ' + response.header.tanggal_upload + mHashInfo);
                    
                    let html = '';
                    if (response.details.length === 0) {
                        html = '<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data paket ditemukan di file ini.</td></tr>';
                    } else {
                        response.details.forEach(item => {
                            const rHash = item.resi_hash
                                ? `<code style="font-size:10.5px; color:#047857; background:#ecfdf5; padding:2px 6px; border-radius:4px;" title="${escManifestHtml(item.resi_hash)}">${escManifestHtml(item.resi_hash.substring(0, 12))}...</code>`
                                : `<span class="text-muted small">-</span>`;
                            html += `
                                <tr class="small">
                                    <td class="ps-3 fw-bold">${escManifestHtml(item.resi)}</td>
                                    <td>${escManifestHtml(item.nama_penerima || '-')}</td>
                                    <td><div class="text-truncate" style="max-width: 220px;" title="${escManifestHtml(item.alamat || '-')}">${escManifestHtml(item.alamat || '-')}</div></td>
                                    <td><span class="badge bg-light text-dark border">${escManifestHtml(item.ekspedisi)}</span></td>
                                    <td><span class="text-success fw-medium">${escManifestHtml(item.status_scan || 'Berhasil')}</span></td>
                                    <td>${rHash}</td>
                                    <td>${escManifestHtml(item.tanggal || '-')}</td>
                                </tr>
                            `;
                        });
                        
                        if (response.details.length === 500) {
                            html += '<tr><td colspan="7" class="text-center py-3 bg-light text-muted small"><i data-lucide="info" style="width: 14px; margin-bottom: 2px;" class="me-1"></i> Menampilkan 500 data pertama. Silakan unduh file aslinya untuk melihat seluruh data selengkapnya.</td></tr>';
                        }
                    }
                    $('#detailTable tbody').html(html);
                    if (window.lucide) window.lucide.createIcons();
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            }).fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan saat memuat data', 'error');
            });
        });

        // ── 9. LOGIKA INTERAKTIF MODAL EXPORT EXCEL MULTI-EKSPEDISI ──
        const $expPills = $('.exp-pill-btn');
        const $expSelect = $('#exportEkspedisiSelect');
        const $fileSelect = $('#exportFileIdSelect');
        const $currentBadge = $('#expCurrentBadge');
        const $previewText = $('#exportPreviewText');

        function updateExportModalUI(selectedEksp) {
            selectedEksp = (selectedEksp || 'ALL').toUpperCase();

            // Update status tombol pill
            $expPills.removeClass('active');
            $expPills.filter(`[data-eksp="${selectedEksp}"]`).addClass('active');

            // Update dropdown ekspedisi
            if ($expSelect.val() !== selectedEksp) {
                $expSelect.val(selectedEksp);
            }

            // Update badge tampilan
            $currentBadge.text(selectedEksp === 'ALL' ? 'SEMUA EKSPEDISI' : selectedEksp);

            // Filter opsi file manifest berdasarkan ekspedisi yang dipilih
            $fileSelect.find('option').each(function() {
                const $opt = $(this);
                const optVal = $opt.val();
                if (optVal === '0') {
                    $opt.show().prop('disabled', false);
                    return;
                }

                const fileEksp = ($opt.data('eksp') || '').toString().toUpperCase();
                const totalSpx = parseInt($opt.data('spx') || 0);
                const totalInstan = parseInt($opt.data('instan') || 0);
                const totalJne = parseInt($opt.data('jne') || 0);
                const totalJnt = parseInt($opt.data('jnt') || 0);
                const totalLex = parseInt($opt.data('lex') || 0);
                const totalId = parseInt($opt.data('id') || 0);
                const totalNinja = parseInt($opt.data('ninja') || 0);
                const totalSicepat = parseInt($opt.data('sicepat') || 0);

                let isMatch = false;
                if (selectedEksp === 'ALL') {
                    isMatch = true;
                } else if (selectedEksp === 'INSTAN') {
                    isMatch = (fileEksp.includes('INSTAN') || fileEksp.includes('SAMEDAY') || totalInstan > 0);
                } else if (selectedEksp === 'SPX') {
                    isMatch = (fileEksp === 'SPX' || fileEksp.includes('SHOPEE') || totalSpx > 0);
                } else if (selectedEksp === 'JNE') {
                    isMatch = (fileEksp.includes('JNE') || totalJne > 0);
                } else if (selectedEksp === 'JNT') {
                    isMatch = (fileEksp.includes('JNT') || fileEksp.includes('J&T') || totalJnt > 0);
                } else if (selectedEksp === 'LEX') {
                    isMatch = (fileEksp.includes('LEX') || fileEksp.includes('LAZADA') || totalLex > 0);
                } else if (selectedEksp === 'ID') {
                    isMatch = (fileEksp === 'ID' || fileEksp.includes('ID') || totalId > 0);
                } else if (selectedEksp === 'NINJA') {
                    isMatch = (fileEksp.includes('NINJA') || totalNinja > 0);
                } else if (selectedEksp === 'SICEPAT') {
                    isMatch = (fileEksp.includes('SICEPAT') || totalSicepat > 0);
                }

                if (isMatch) {
                    $opt.show().prop('disabled', false);
                } else {
                    $opt.hide().prop('disabled', true);
                }
            });

            // Jika opsi manifest yang sedang terpilih sekarang disembunyikan/disabled, reset ke 0 (Semua File)
            const $curOpt = $fileSelect.find('option:selected');
            if ($curOpt.prop('disabled') || $curOpt.is(':hidden')) {
                $fileSelect.val('0');
            }

            // Update teks preview spesifikasi kolom Excel
            if (selectedEksp === 'INSTAN') {
                $previewText.html('<strong>Format Antrean Prioritas (12 Kolom):</strong> <em>No, No Paket, No Pesanan, Nama Pemesan, Ekspedisi, Layanan SLA, Cut-Off, Skor Prioritas (Formula Si), Tanggal Masuk, Sesi, Status,</em> dan <em>Petugas Input</em>.');
            } else if (selectedEksp === 'ALL') {
                $previewText.html('<strong>Format Universal Semua Ekspedisi (9 Kolom):</strong> <em>No, No Resi / Waybill, Nama Penerima, Alamat / Tujuan, Ekspedisi, Status Scan, Tanggal Scan, File Manifest,</em> dan <em>Segel Hash (SHA-256)</em>.');
            } else {
                $previewText.html(`<strong>Format Manifest Khusus ${selectedEksp} (9 Kolom):</strong> <em>No, No Resi / Waybill, Nama Penerima, Alamat / Tujuan, Ekspedisi (${selectedEksp}), Status Scan, Tanggal Scan, File Manifest,</em> dan <em>Segel Kriptografi SHA-256</em>.`);
            }
        }

        $expPills.on('click', function() {
            const chosen = $(this).data('eksp');
            updateExportModalUI(chosen);
        });

        $expSelect.on('change', function() {
            updateExportModalUI($(this).val());
        });

        // Quick Date Preset Buttons
        $('.date-preset-btn').on('click', function() {
            $('.date-preset-btn').removeClass('active');
            $(this).addClass('active');

            const range = $(this).data('range');
            const now = new Date();
            const formatYmd = d => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            if (range === 'today') {
                const todayStr = formatYmd(now);
                $('#exportStartDate').val(todayStr);
                $('#exportEndDate').val(todayStr);
            } else if (range === 'week') {
                const past7 = new Date();
                past7.setDate(past7.getDate() - 7);
                $('#exportStartDate').val(formatYmd(past7));
                $('#exportEndDate').val(formatYmd(now));
            } else if (range === 'month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                $('#exportStartDate').val(formatYmd(firstDay));
                $('#exportEndDate').val(formatYmd(now));
            } else if (range === 'all') {
                $('#exportStartDate').val('');
                $('#exportEndDate').val('');
            }
        });

        $('#modalExportEkspedisi').on('shown.bs.modal', function () {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });

        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>

<?php if (isset($_SESSION['swal'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?= e($_SESSION['swal']['icon']) ?>',
            title: '<?= e($_SESSION['swal']['title']) ?>',
            html: `<?= $_SESSION['swal']['text'] ?>`,
            confirmButtonText: 'OK, Saya Mengerti',
            confirmButtonColor: '#10b981'
        });
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>
<?php clearOldInput(); ?>

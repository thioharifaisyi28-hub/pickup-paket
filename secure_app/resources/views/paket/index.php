<?php
$activeSort = ($sort ?? 'priority') === 'fifo' ? 'fifo' : 'priority';
?>
<div class="page-header"
    style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
    <div>
        <h1 class="page-title" style="margin-bottom: 4px;">DAFTAR ANTREAN PAKET</h1>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span id="queueModeBadge" style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; <?= $activeSort === 'priority' ? 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;' : 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;' ?>">
                <i data-lucide="<?= $activeSort === 'priority' ? 'zap' : 'clock' ?>" style="width: 13px; height: 13px;"></i>
                <span id="queueModeText"><?= $activeSort === 'priority' ? 'Algoritma Priority Scheduling Aktif (Non-Preemptive + Aging)' : 'Mode Komparasi: Antrean Standar FIFO (Waktu Masuk)' ?></span>
            </span>
            <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                <i data-lucide="info" style="width: 13px; height: 13px;"></i> Khusus Antrean Instan / Sameday (1 Paket 1 Kurir)
            </span>
        </div>
    </div>

    <div class="page-actions"
        style="display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 0.75rem;">
        <div
            style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; background: white; padding: 6px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <a href="<?= url('/hasil-ekspedisi?start_date=' . urlencode($tanggal) . '&end_date=' . urlencode($tanggal)) ?>"
                id="btnGoHasilEkspedisi"
                title="Pusat Terpadu Import Excel, Tambah Paket, Rekalkulasi Skor &amp; Kelola Paket"
                style="padding: 6px 12px; display: inline-flex; align-items: center; gap: 7px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 10px; font-size: 11.5px; font-weight: 700; text-decoration: none; transition: all 0.2s; cursor: pointer;">
                <i data-lucide="file-check-2" style="width: 15px; height: 15px;"></i> Pusat Import &amp; Kelola Paket (Hasil Ekspedisi)
            </a>

            <a href="<?= url('/hasil-ekspedisi/export?ekspedisi=INSTAN&start_date=' . urlencode($tanggal) . '&end_date=' . urlencode($tanggal)) ?>"
                id="btnExportAntreanInstan"
                class="no-loading"
                title="Download laporan antrean paket prioritas instan/sameday (.xlsx)"
                style="padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px; background: white; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 11.5px; font-weight: 600; text-decoration: none; transition: all 0.2s; cursor: pointer;"
                onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#94a3b8';"
                onmouseout="this.style.background='white'; this.style.borderColor='#cbd5e1';">
                <i data-lucide="file-spreadsheet" style="width: 15px; height: 15px; color: #10b981;"></i>
                <span>Export Excel</span>
            </a>

            <?php if (!isAdmin()): ?>
            <div style="width: 1px; height: 24px; background: #cbd5e1; margin: 0 2px;"></div>
            
            <button type="button" id="btnHeaderAmbilFotoMasal"
                style="padding: 6px 11px; display: inline-flex; align-items: center; gap: 7px; background: #3b82f6; color: white; border: none; border-radius: 10px; font-size: 11.5px; font-weight: 600; transition: all 0.2s; cursor: pointer; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);"
                onmouseover="this.style.background='#2563eb'; this.style.transform='translateY(-1px)';"
                onmouseout="this.style.background='#3b82f6'; this.style.transform='none';">
                <i data-lucide="camera" style="width: 16px; height: 16px;"></i> Ambil Foto Masal Semua
            </button>
            <?php endif; ?>
        </div>

        <?php if (isAdmin()): ?>
        <!-- Admin Danger Zone: Hapus Paket Sudah & Hapus Semua Paket -->
        <div
            style="display: flex; align-items: center; gap: 0.5rem; background: #fff1f2; padding: 6px; border-radius: 12px; border: 1px solid #ffe4e6; box-shadow: 0 2px 4px rgba(225, 29, 72, 0.04);">
            <form action="<?= url('/paket/delete-sudah') ?>" method="POST" id="deleteSudahForm" style="margin:0;">
                <?= csrf_field() ?>
                <input type="hidden" name="tanggal" id="deleteSudahTanggal" value="<?= e($tanggal) ?>">
                <input type="hidden" name="return_url" id="deleteSudahReturnUrl" value="">
                <button type="button" id="btnDeleteSudah"
                    style="padding: 6px 11px; display: inline-flex; align-items: center; gap: 6px; background: white; color: #e11d48; border: 1px solid #fecdd3; border-radius: 10px; font-size: 11.5px; font-weight: 600; transition: all 0.2s; cursor: pointer;"
                    onmouseover="this.style.background='#ffe4e6';"
                    onmouseout="this.style.background='white';">
                    <i data-lucide="trash-2" style="width: 15px; height: 15px;"></i> Hapus Paket Sudah
                </button>
            </form>

            <form action="<?= url('/paket/delete-all') ?>" method="POST" id="deleteAllForm" style="margin:0;">
                <?= csrf_field() ?>
                <input type="hidden" name="tanggal" id="deleteAllTanggal" value="<?= e($tanggal) ?>">
                <input type="hidden" name="return_url" id="deleteAllReturnUrl" value="">
                <button type="button" id="btnDeleteAll"
                    style="padding: 6px 11px; display: inline-flex; align-items: center; gap: 6px; background: #e11d48; color: white; border: none; border-radius: 10px; font-size: 11.5px; font-weight: 600; transition: all 0.2s; cursor: pointer; box-shadow: 0 4px 12px rgba(225, 29, 72, 0.2);"
                    onmouseover="this.style.background='#be123c'; this.style.transform='translateY(-1px)';"
                    onmouseout="this.style.background='#e11d48'; this.style.transform='none';">
                    <i data-lucide="trash" style="width: 15px; height: 15px;"></i> Hapus Semua Paket
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card"
    style="box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 12px; border: 1px solid #eef2f6; overflow: hidden;">
    <!-- Quick Stats Bar -->
    <div class="quick-stats-bar" style="justify-content: space-between;">
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <div style="width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;"></div>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Total: <strong id="statTotal"
                        style="color: #1e293b;"><?= number_format($stats['total']) ?></strong></span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <div style="width: 8px; height: 8px; background: #f59e0b; border-radius: 50%;"></div>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Pending: <strong id="statPending"
                        style="color: #1e293b;"><?= number_format($stats['pending']) ?></strong></span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <div style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></div>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Sudah: <strong id="statSudah"
                        style="color: #1e293b;"><?= number_format($stats['sudah']) ?></strong></span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <div style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%;"></div>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Batal: <strong id="statBatal"
                        style="color: #1e293b;"><?= number_format($stats['batal']) ?></strong></span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0;">
                <div style="width: 8px; height: 8px; background: #f97316; border-radius: 50%;"></div>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Retur: <strong id="statRetur"
                        style="color: #1e293b;"><?= number_format($stats['retur']) ?></strong></span>
            </div>
        </div>
        <div style="font-size: 11.5px; color: #64748b; font-weight: 600; flex-shrink: 0;">
            Klik badge <strong>Skor Prioritas</strong> pada baris paket untuk melihat rincian rumus
        </div>
    </div>
    
    <div class="card-header paket-filter-header border-bottom-0 p-3 p-md-4">
        <!-- Filter Form -->
        <form method="GET" action="<?= url('/paket') ?>" id="filterForm" class="paket-filter-form d-flex flex-column flex-md-row gap-2 gap-md-3 w-100">
            <!-- Search Bar -->
            <div class="filter-search-wrapper flex-grow-1">
                <div class="input-group search-input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius: 10px 0 0 10px; border-color: #cbd5e1; padding-left: 14px; padding-right: 8px;">
                        <i data-lucide="search" style="width: 18px; height: 18px; color: #94a3b8;"></i>
                    </span>
                    <input type="text" name="search" id="searchInput" class="form-control border-start-0 ps-1"
                        placeholder="Cari No. Paket / No. Pesanan / Nama pemesan..." value="<?= e($search) ?>"
                        style="background: #ffffff; border-radius: 0 10px 10px 0; border-color: #cbd5e1; height: 35px; font-size: 12px;"
                        autocomplete="off">
                </div>
            </div>

            <!-- Date, Status & Algorithm Sort Filter Group -->
            <div class="filter-secondary-group d-flex flex-wrap flex-md-nowrap gap-2">
                <div class="filter-date-wrapper flex-fill" style="min-width: 140px;">
                    <input type="date" name="tanggal" id="tanggalFilter" class="form-control"
                        value="<?= e($tanggal) ?>"
                        style="background: #ffffff; border-radius: 10px; border-color: #cbd5e1; height: 35px; font-size: 12px;">
                </div>
                <div class="filter-status-wrapper flex-fill" style="min-width: 140px;">
                    <select name="status" id="statusFilter" class="form-select"
                        style="background: #ffffff; border-radius: 10px; border-color: #cbd5e1; height: 35px; font-size: 12px;">
                        <option value="">Semua Status</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Sudah" <?= $status == 'Sudah' ? 'selected' : '' ?>>Sudah</option>
                        <option value="Batal" <?= $status == 'Batal' ? 'selected' : '' ?>>Batal</option>
                        <option value="Retur" <?= $status == 'Retur' ? 'selected' : '' ?>>Retur</option>
                    </select>
                </div>
                <div class="filter-sort-wrapper flex-fill" style="min-width: 215px;">
                    <select name="sort" id="sortFilter" class="form-select fw-bold"
                        title="Pilih metode penjadwalan antrean paket"
                        style="background: #eff6ff; color: #1e40af; border-radius: 10px; border-color: #93c5fd; height: 35px; font-size: 12px;">
                        <option value="priority" <?= $activeSort === 'priority' ? 'selected' : '' ?>>⚡ Priority Scheduling (Aktif)</option>
                        <option value="fifo" <?= $activeSort === 'fifo' ? 'selected' : '' ?>>🕒 Antrean FIFO (Komparasi)</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body" style="padding: 0;">
        <?php if (!isAdmin()): ?>
            <div id="bulkSelectionBanner" style="display: none; background: #eff6ff; border-top: 1px solid #dbeafe; border-bottom: 1px solid #dbeafe; color: #1e40af; padding: 10px 1.5rem; font-size: 11.5px; font-weight: 600; text-align: center;">
                <i data-lucide="info" style="width: 14px; height: 14px; margin-right: 8px; vertical-align: middle;"></i>
                Semua <span id="currentPageCount" style="color: #1d4ed8; font-weight: 800;">0</span> paket di halaman ini terpilih. 
                <button type="button" id="btnSelectAllAcross" style="background: #3b82f6; border: none; color: white; font-weight: 700; cursor: pointer; padding: 2px 10px; border-radius: 6px; margin-left: 8px; font-size: 12px; transition: all 0.2s;">Pilih semua <?= number_format($total) ?> paket</button>
            </div>
        <?php endif; ?>

        <div id="tableContainer">
            <?php $this->partial('paket.table', $data); ?>
        </div>
    </div>
</div>

<!-- Modal Rincian Skor Priority Scheduling -->
<div class="modal-overlay" id="priorityBreakdownModal">
    <div class="modal-dialog" style="width: 640px; max-width: 95%; border-radius: 20px; overflow: hidden; background: white; box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.3);">
        <div class="modal-header" style="padding: 1.15rem 1.5rem; background: #0f172a; color: white; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 9px; background: rgba(59, 130, 246, 0.25); color: #60a5fa; display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="cpu" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #fff;">Rincian Skor Priority Scheduling</h4>
                    <div style="font-size: 11.5px; color: #94a3b8;">Formula: S<sub>i</sub> = (0.40 × C<sub>i</sub>) + (0.30 × T<sub>i</sub>) + (0.15 × V<sub>i</sub>) + (0.15 × A<sub>i</sub>)</div>
                </div>
            </div>
            <button type="button" class="modal-close" onclick="closeModal('priorityBreakdownModal')" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 1.5rem; background: #f8fafc;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; background: white; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1rem;">
                <div>
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Paket & Ekspedisi</div>
                    <div style="font-size: 15px; font-weight: 800; color: #1e293b;" id="pbNoPesanan">-</div>
                    <div style="font-size: 12.5px; color: #475569; margin-top: 2px;" id="pbSubInfo">-</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">Skor Akhir (S<sub>i</sub>)</div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; margin-top: 3px;">
                        <span id="pbFinalScore" style="font-size: 24px; font-weight: 900; color: #1e293b;">0.00</span>
                        <span id="pbLevelBadge" style="font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8;">SEDANG</span>
                    </div>
                </div>
            </div>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden;">
                <table class="table mb-0" style="font-size: 13px;">
                    <thead style="background: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase;">
                        <tr>
                            <th style="padding: 10px 14px;">Kriteria Parameter</th>
                            <th style="padding: 10px 14px; text-align: center;">Nilai Mentah</th>
                            <th style="padding: 10px 14px; text-align: center;">Bobot (w)</th>
                            <th style="padding: 10px 14px; text-align: right;">Kontribusi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 10px 14px;">
                                <div style="font-weight: 700; color: #1e293b;">1. Urgensi Cut-Off Kurir (C<sub>i</sub>)</div>
                                <div style="font-size: 11.5px; color: #64748b;" id="pbDetailC">-</div>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700;" id="pbRawC">0</td>
                            <td style="padding: 10px 14px; text-align: center; color: #64748b;">0.40 (40%)</td>
                            <td style="padding: 10px 14px; text-align: right; font-weight: 800; color: #2563eb;" id="pbWeightedC">0.00</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 14px;">
                                <div style="font-weight: 700; color: #1e293b;">2. Tingkat Layanan SLA (T<sub>i</sub>)</div>
                                <div style="font-size: 11.5px; color: #64748b;" id="pbDetailT">-</div>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700;" id="pbRawT">0</td>
                            <td style="padding: 10px 14px; text-align: center; color: #64748b;">0.30 (30%)</td>
                            <td style="padding: 10px 14px; text-align: right; font-weight: 800; color: #2563eb;" id="pbWeightedT">0.00</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 14px;">
                                <div style="font-weight: 700; color: #1e293b;">3. Skala Volume Batch Harian (V<sub>i</sub>)</div>
                                <div style="font-size: 11.5px; color: #64748b;" id="pbDetailV">-</div>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700;" id="pbRawV">0</td>
                            <td style="padding: 10px 14px; text-align: center; color: #64748b;">0.15 (15%)</td>
                            <td style="padding: 10px 14px; text-align: right; font-weight: 800; color: #2563eb;" id="pbWeightedV">0.00</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 14px;">
                                <div style="font-weight: 700; color: #1e293b;">4. Dynamic Aging Anti-Starvation (A<sub>i</sub>)</div>
                                <div style="font-size: 11.5px; color: #64748b;" id="pbDetailA">-</div>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 700;" id="pbRawA">0</td>
                            <td style="padding: 10px 14px; text-align: center; color: #64748b;">0.15 (15%)</td>
                            <td style="padding: 10px 14px; text-align: right; font-weight: 800; color: #2563eb;" id="pbWeightedA">0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lihat Foto (Galeri Inline) -->
<div class="modal-overlay" id="lihatFotoModal">
    <div class="modal-dialog" style="width: 800px; max-width: 95%;">
        <div class="modal-header" style="padding: 1rem 1.5rem;">
            <h3 class="modal-title" style="display: flex; align-items: center; gap: 10px;">
                <i data-lucide="image" style="color: #3b82f6;"></i> Galeri Foto Paket
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('lihatFotoModal')">
                <i data-lucide="x"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 1.5rem; background: #f8fafc; min-height: 300px;">
            <!-- Header Info -->
            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: white;">
                <div class="card-body p-3">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 1rem;">
                        <div>
                            <div class="text-muted"
                                style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                NO. PESANAN</div>
                            <div id="fotoNoPesanan" style="font-size: 14px; font-weight: 700; color: #1e293b;">-</div>
                        </div>
                        <div>
                            <div class="text-muted"
                                style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                PENERIMA</div>
                            <div id="fotoPenerima" style="font-size: 14px; font-weight: 700; color: #1e293b;">-</div>
                        </div>
                        <div>
                            <div class="text-muted"
                                style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                TANGGAL</div>
                            <div id="fotoTanggal" style="font-size: 14px; font-weight: 700; color: #1e293b;">-</div>
                        </div>
                        <div>
                            <div class="text-muted"
                                style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                SESI</div>
                            <div id="fotoSesi" style="font-size: 14px; font-weight: 700; color: #1e293b;">-</div>
                        </div>
                        <div>
                            <div class="text-muted"
                                style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                PETUGAS</div>
                            <div id="fotoPetugas" style="font-size: 14px; font-weight: 700; color: #1e293b;">-</div>
                        </div>
                        <div>
                            <div class="text-muted"
                                style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                                STATUS</div>
                            <div id="fotoStatus" style="font-size: 14px; font-weight: 700; color: #1e293b;">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingFoto"
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 0;">
                <i data-lucide="loader-2"
                    style="width: 32px; height: 32px; color: #3b82f6; animation: spin 1s linear infinite;"></i>
                <p style="margin-top: 1rem; color: #64748b; font-size: 14px;">Memuat data foto...</p>
            </div>

            <!-- Gallery State -->
            <div id="fotoGallery" style="display: none; max-height: 50vh; overflow-y: auto; padding-right: 5px;">
                <!-- Dinamis dari JS -->
            </div>
        </div>
    </div>
</div>

<style>
    .modal-overlay .modal-dialog,
    .modal-overlay .modal-content {
        pointer-events: auto !important;
    }

    .swal2-container {
        z-index: 9999 !important;
    }

    .import-modal-control {
        height: 35px;
        border-radius: 8px;
        border-color: #cbd5e1;
        font-size: 12px;
        color: #0f172a;
    }

    .import-modal-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .import-modal-control:disabled {
        background-color: #f1f5f9 !important;
        color: #94a3b8 !important;
        cursor: not-allowed;
    }

    .upload-drop-zone:hover,
    .upload-drop-zone.dragover {
        border-color: #10b981 !important;
        background: #f0fdf4 !important;
    }

    .btn-priority-detail:hover {
        transform: translateY(-1px) scale(1.02);
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
    }

    @keyframes spin {
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<script>
    function openModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('show');
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('show');
    }

    window.addEventListener('click', function (e) {
        const modals = ['lihatFotoModal', 'priorityBreakdownModal'];
        modals.forEach(id => {
            const modal = document.getElementById(id);
            if (e.target === modal) {
                closeModal(id);
            }
        });
    });

    $(document).ready(function () {
        // Delegated handler untuk Lihat Foto agar tetap aktif setelah filter / paginasi AJAX
        $(document).on('click', '.btn-lihat-foto', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            $('#fotoNoPesanan').text($(this).data('nopesanan'));
            $('#fotoPenerima').text($(this).data('nama'));
            $('#fotoSesi').text($(this).data('sesi'));
            $('#fotoTanggal').text($(this).data('tanggal'));
            $('#fotoPetugas').text($(this).data('petugas'));
            $('#fotoStatus').text($(this).data('status'));

            openModal('lihatFotoModal');
            $('#loadingFoto').show();
            $('#fotoGallery').hide().empty();

            $.ajax({
                url: '<?= url('/foto-instan/') ?>' + id,
                type: 'GET',
                success: function (res) {
                    if (res.success) {
                        let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">';
                        res.photos.forEach(p => {
                            html += `<div><img src="<?= url('/') ?>${p.path_foto}" style="width:100%; border-radius:8px;"></div>`;
                        });
                        html += '</div>';
                        $('#fotoGallery').html(html || '<p>Belum ada foto.</p>');
                        $('#loadingFoto').hide();
                        $('#fotoGallery').show();
                    }
                }
            });
        });

        // Delegated handler klik badge Skor Prioritas -> tampilkan rincian matematis
        $(document).on('click', '.btn-priority-detail', function (e) {
            e.preventDefault();
            const btn = $(this);
            const noPaket = btn.data('nopaket');
            const noPesanan = btn.data('nopesanan');
            const nama = btn.data('nama');
            const ekspedisi = btn.data('ekspedisi');
            const layanan = btn.data('layanan');
            const cutoff = btn.data('cutoff');
            const score = parseFloat(btn.data('score') || 0);
            const level = btn.data('level') || 'SEDANG';

            let bd = btn.attr('data-breakdown');
            let parsed = {};
            try {
                parsed = bd ? JSON.parse(bd) : {};
            } catch (err) {
                parsed = {};
            }

            const C = parseFloat(parsed.C ?? 0);
            const T = parseFloat(parsed.T ?? 0);
            const V = parseFloat(parsed.V ?? 0);
            const A = parseFloat(parsed.A ?? 0);
            const rem = parsed.rem !== undefined ? parseInt(parsed.rem, 10) : null;
            const vol = parsed.vol !== undefined ? parseInt(parsed.vol, 10) : 1;
            const wt = parsed.wt !== undefined ? parseInt(parsed.wt, 10) : 0;

            $('#pbNoPesanan').text('#' + noPaket + ' • ' + noPesanan + ' (' + nama + ')');
            $('#pbSubInfo').text('Ekspedisi ' + ekspedisi + ' • Layanan ' + layanan + ' • Cut-off ' + cutoff + ' WIB');
            $('#pbFinalScore').text(score.toFixed(2));
            $('#pbLevelBadge').text(level);

            const levelStyles = {
                KRITIS: { bg: '#fef2f2', color: '#dc2626' },
                TINGGI: { bg: '#fffbeb', color: '#d97706' },
                SEDANG: { bg: '#eff6ff', color: '#2563eb' },
                RENDAH: { bg: '#f1f5f9', color: '#475569' }
            };
            const st = levelStyles[level] || levelStyles.SEDANG;
            $('#pbLevelBadge').css({ background: st.bg, color: st.color });

            $('#pbRawC').text(C);
            $('#pbWeightedC').text((0.40 * C).toFixed(2));
            $('#pbDetailC').text(
                rem !== null
                    ? (rem <= 0 ? 'Melewati / tepat jam cut-off (' + rem + ' mnt) — Zona KRITIS' : 'Sisa waktu ke cut-off ' + cutoff + ': ' + rem + ' menit')
                    : 'Batas penjemputan kurir pukul ' + cutoff + ' WIB'
            );

            $('#pbRawT').text(T);
            $('#pbWeightedT').text((0.30 * T).toFixed(2));
            $('#pbDetailT').text('Klasifikasi SLA layanan ' + layanan + ' (' + ekspedisi + ')');

            $('#pbRawV').text(V);
            $('#pbWeightedV').text((0.15 * V).toFixed(2));
            $('#pbDetailV').text('Akumulasi volume harian ekspedisi ' + ekspedisi + ': ' + vol + ' paket');

            $('#pbRawA').text(A.toFixed(2));
            $('#pbWeightedA').text((0.15 * A).toFixed(2));
            $('#pbDetailA').text('Waktu tunggu di antrean: ' + wt + ' menit (+10 poin/jam)');

            openModal('priorityBreakdownModal');
        });

        // Handler untuk tombol simpan dengan konfirmasi centang
        $('.save-confirm').on('click', function (e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const title = $(this).data('title') || 'Konfirmasi';
            const text = $(this).data('text') || 'Simpan data ini?';

            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Tampilkan SweetAlert Box untuk sukses tambah paket (Override global toast)
        <?php
        $flash = flash();
        if ($flash):
            if ($flash['type'] === 'success' && strpos($flash['message'], 'ditambahkan') !== false):
                ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?= e($flash['message']) ?>',
                    confirmButtonColor: '#3b82f6',
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php
            else:
                $_SESSION['flash'] = $flash;
            endif;
        endif;
        ?>

        // Global state for selection
        let selectedIds = [];
        let isAllAcrossPages = false;

        function updateBulkBar() {
            const selectedCount = selectedIds.length;
            const bar = $('#bulkActionBar');
            const banner = $('#bulkSelectionBanner');
            
            $('.paket-checkbox').each(function() {
                const id = parseInt($(this).val());
                $(this).prop('checked', selectedIds.includes(id));
                
                if (selectedIds.includes(id)) {
                    $(this).closest('.paket-row').css('background', '#eff6ff');
                } else {
                    $(this).closest('.paket-row').css('background', '');
                }
            });

            const allCheckedOnPage = $('.paket-checkbox').length > 0 && $('.paket-checkbox:checked').length === $('.paket-checkbox').length;
            $('#selectAllPaket').prop('checked', allCheckedOnPage);

            if (selectedCount > 0) {
                $('#selectedCount').text(selectedCount);
                bar.css('bottom', '30px');
                
                $('#btnBulkFoto').html('<i data-lucide="camera" style="width: 16px; height: 16px;"></i> Ambil Foto Masal (' + selectedCount + ')');
                if (window.lucide) lucide.createIcons();

                if (allCheckedOnPage && !isAllAcrossPages) {
                    $('#currentPageCount').text($('.paket-checkbox').length);
                    banner.show();
                } else if (!isAllAcrossPages) {
                    banner.hide();
                }

                if (isAllAcrossPages) {
                    banner.html('<i data-lucide="check-circle" style="width: 14px; height: 14px; margin-right: 8px; vertical-align: middle; color: #10b981;"></i> Semua <strong>' + selectedCount + '</strong> paket yang sesuai filter telah terpilih. <button type="button" id="btnClearSelection" style="background: #fee2e2; border: none; color: #ef4444; font-weight: 800; cursor: pointer; padding: 2px 10px; border-radius: 6px; margin-left: 8px; font-size: 12px;">Batalkan Pilihan</button>').show();
                    if (window.lucide) lucide.createIcons();
                }
            } else {
                bar.css('bottom', '-100px');
                banner.hide();
                isAllAcrossPages = false;
            }
        }

        $(document).on('change', '.paket-checkbox', function() {
            const id = parseInt($(this).val());
            if ($(this).is(':checked')) {
                if (!selectedIds.includes(id)) selectedIds.push(id);
            } else {
                selectedIds = selectedIds.filter(i => i !== id);
                isAllAcrossPages = false;
            }
            updateBulkBar();
        });

        $(document).on('change', '#selectAllPaket', function() {
            const isChecked = $(this).is(':checked');
            if (isChecked) {
                $('.paket-checkbox').each(function() {
                    const id = parseInt($(this).val());
                    $(this).prop('checked', true);
                    if (!selectedIds.includes(id)) {
                        selectedIds.push(id);
                    }
                });

                const search = '';
                const status = '';
                
                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang menyeleksi SELURUH paket Anda yang belum diambil foto',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: '<?= url('/paket/selectable-ids') ?>',
                    data: { search: search, status: status, tanggal: $('#tanggalFilter').val() },
                    success: function(res) {
                        if (res.success) {
                            selectedIds = res.ids;
                            isAllAcrossPages = true;
                            updateBulkBar();
                            Swal.close();
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Terjadi kesalahan saat mengambil data paket.',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                });
            } else {
                selectedIds = [];
                isAllAcrossPages = false;
                updateBulkBar();
            }
        });

        $(document).on('click', '#btnSelectAllAcross', function() {
            const search = '';
            const status = '';
            
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang mengambil SELURUH ID paket Anda',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '<?= url('/paket/selectable-ids') ?>',
                data: { search: search, status: status, tanggal: $('#tanggalFilter').val() },
                success: function(res) {
                    if (res.success) {
                        selectedIds = res.ids;
                        isAllAcrossPages = true;
                        updateBulkBar();
                        Swal.close();
                    }
                }
            });
        });

        $(document).on('click', '#btnClearSelection', function() {
            selectedIds = [];
            isAllAcrossPages = false;
            updateBulkBar();
        });

        $('#btnCancelBulk').on('click', function() {
            selectedIds = [];
            isAllAcrossPages = false;
            updateBulkBar();
        });

        $('#btnBulkFoto').on('click', function() {
            if (selectedIds.length === 0) return;
            
            if (selectedIds.length > 300) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pilihan Terlalu Banyak',
                    text: 'Anda memilih ' + selectedIds.length + ' paket. Disarankan untuk memproses maksimal 300 paket sekaligus agar sistem tetap stabil. Lanjutkan?',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        proceedToCapture();
                    }
                });
            } else {
                proceedToCapture();
            }
        });

        function proceedToCapture() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "<?= url('/foto-instan/capture') ?>";
            
            const inputIds = document.createElement('input');
            inputIds.type = 'hidden';
            inputIds.name = 'ids';
            inputIds.value = selectedIds.join(',');
            
            const inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden';
            inputCsrf.name = '_csrf_token';
            inputCsrf.value = "<?= csrf_token() ?>";
            
            form.appendChild(inputIds);
            form.appendChild(inputCsrf);
            document.body.appendChild(form);
            form.submit();
        }

        // Logic for Header "Ambil Foto Masal Semua" Button
        $('#btnHeaderAmbilFotoMasal').on('click', function() {
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang menyiapkan seluruh paket Anda yang belum diambil foto...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '<?= url('/paket/selectable-ids') ?>',
                data: { search: '', status: '', tanggal: $('#tanggalFilter').val() },
                success: function(res) {
                    if (res.success && res.ids.length > 0) {
                        Swal.close();
                        const totalIds = res.ids;
                        
                        Swal.fire({
                            icon: 'question',
                            title: 'Ambil Foto Masal',
                            text: 'Anda akan mengambil foto masal untuk ' + totalIds.length + ' paket secara keseluruhan. Lanjutkan?',
                            showCancelButton: true,
                            confirmButtonColor: '#3b82f6',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = "<?= url('/foto-instan/capture') ?>";
                                
                                const inputIds = document.createElement('input');
                                inputIds.type = 'hidden';
                                inputIds.name = 'ids';
                                inputIds.value = totalIds.join(',');
                                
                                const inputCsrf = document.createElement('input');
                                inputCsrf.type = 'hidden';
                                inputCsrf.name = '_csrf_token';
                                inputCsrf.value = "<?= csrf_token() ?>";
                                
                                form.appendChild(inputIds);
                                form.appendChild(inputCsrf);
                                document.body.appendChild(form);
                                form.submit();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Tidak Ada Paket',
                            text: 'Tidak ada paket tertunda yang perlu diambil foto.',
                            confirmButtonColor: '#3b82f6'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Gagal mengambil data paket.',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
        });

        let filterTimer;

        function syncHeaderMeta() {
            const sortVal = $('#sortFilter').val();
            const tglVal = $('#tanggalFilter').val();
            $('#btnGoHasilEkspedisi').attr(
                'href',
                "<?= url('/hasil-ekspedisi') ?>?start_date=" + encodeURIComponent(tglVal) + "&end_date=" + encodeURIComponent(tglVal)
            );

            const badge = $('#queueModeBadge');
            if (sortVal === 'fifo') {
                badge.css({ background: '#f1f5f9', color: '#475569', borderColor: '#cbd5e1' });
                badge.html('<i data-lucide="clock" style="width: 13px; height: 13px;"></i> <span>Mode Komparasi: Antrean Standar FIFO (Waktu Masuk)</span>');
            } else {
                badge.css({ background: '#eff6ff', color: '#1d4ed8', borderColor: '#bfdbfe' });
                badge.html('<i data-lucide="zap" style="width: 13px; height: 13px;"></i> <span>Algoritma Priority Scheduling Aktif (Non-Preemptive + Aging)</span>');
            }
            if (window.lucide) window.lucide.createIcons();
        }

        function updateTable() {
            syncHeaderMeta();
            const form = $('#filterForm');
            const url = form.attr('action');
            const data = form.serialize();

            const newUrl = url + '?' + data;
            window.history.pushState({ path: newUrl }, '', newUrl);

            $.ajax({
                url: url,
                data: data,
                type: 'GET',
                success: function (html) {
                    $('#tableContainer').html(html);
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                    updateBulkBar();
                }
            });
        }

        $('#searchInput').on('input', function () {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(function () {
                updateTable();
            }, 500);
        });

        $('#statusFilter, #sortFilter').on('change', function () {
            updateTable();
        });

        $('#tanggalFilter').on('change', function () {
            updateTable();
            $('input[name="tanggal"]').val($(this).val());
        });

        $(document).on('click', '.page-link[data-ajax="true"]', function (e) {
            e.preventDefault();
            const url = $(this).attr('href');

            if (!url || $(this).parent().hasClass('disabled')) return;

            window.history.pushState({ path: url }, '', url);

            $.ajax({
                url: url,
                type: 'GET',
                success: function (html) {
                    $('#tableContainer').html(html);
                    if (window.lucide) {
                        window.lucide.createIcons();
                    }
                    updateBulkBar();
                    $('html, body').animate({
                        scrollTop: $("#tableContainer").offset().top - 100
                    }, 200);
                }
            });
        });

        const searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('search') && $('#searchInput').val().length > 0) {
            const inputElement = document.getElementById('searchInput');
            if (inputElement) {
                inputElement.focus();
                const val = inputElement.value;
                inputElement.value = '';
                inputElement.value = val;
            }
        }

        <?php if (isAdmin()): ?>
        // Handler khusus Hapus Paket Sudah dengan Password
        $('#btnDeleteSudah').on('click', function(e) {
            const form = $('#deleteSudahForm');
            const tanggal = $('#tanggalFilter').val() || '<?= e($tanggal) ?>';
            $('#deleteSudahTanggal').val(tanggal);
            $('#deleteSudahReturnUrl').val(window.location.href);

            const tglParts = tanggal.split('-');
            const tglDisplay = tglParts.length === 3 ? tglParts[2] + '/' + tglParts[1] + '/' + tglParts[0] : tanggal;

            Swal.fire({
                title: 'Konfirmasi Keamanan',
                html: `<p style="margin-bottom:8px;">Anda akan menghapus semua paket berstatus <strong>SUDAH</strong> pada tanggal:</p><p style="font-size:18px;font-weight:700;color:#ef4444;">${tglDisplay}</p><p style="margin-top:8px;font-size:13px;color:#64748b;">Masukkan password untuk melanjutkan:</p>`,
                input: 'password',
                inputPlaceholder: 'Masukkan password...',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Verifikasi & Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value === 'polosport') {
                        form.find('input[name="confirm_password"]').remove();
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'confirm_password',
                            value: result.value
                        }).appendTo(form);
                        form.submit();
                    } else {
                        Swal.fire('Gagal!', 'Password salah. Aksi dibatalkan.', 'error');
                    }
                }
            });
        });

        // Handler khusus Hapus Semua Paket dengan Password
        $('#btnDeleteAll').on('click', function(e) {
            const form = $('#deleteAllForm');
            const tanggal = $('#tanggalFilter').val() || '<?= e($tanggal) ?>';
            $('#deleteAllTanggal').val(tanggal);
            $('#deleteAllReturnUrl').val(window.location.href);

            const tglParts = tanggal.split('-');
            const tglDisplay = tglParts.length === 3 ? tglParts[2] + '/' + tglParts[1] + '/' + tglParts[0] : tanggal;

            Swal.fire({
                title: 'TINDAKAN SANGAT BERBAHAYA!',
                html: `<p style="margin-bottom:8px;">Seluruh paket aktif pada tanggal:</p><p style="font-size:18px;font-weight:700;color:#ef4444;">${tglDisplay}</p><p style="margin-top:8px;font-size:13px;color:#64748b;">akan di-reset/dihapus permanen dari antrean. Masukkan password konfirmasi:</p>`,
                input: 'password',
                inputPlaceholder: 'Masukkan password...',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'HAPUS SEMUA DATA!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    if (result.value === 'polosport') {
                        form.find('input[name="confirm_password"]').remove();
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'confirm_password',
                            value: result.value
                        }).appendTo(form);
                        form.submit();
                    } else {
                        Swal.fire('Gagal!', 'Password salah. Aksi dibatalkan.', 'error');
                    }
                }
            });
        });
        <?php endif; ?>
    });
</script>
<?php if (!isAdmin()): ?>
<div id="bulkActionBar" style="position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background: #1e293b; color: white; padding: 12px 24px; border-radius: 16px; display: flex; align-items: center; gap: 20px; z-index: 1000; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);">
    <div style="display: flex; align-items: center; gap: 12px; border-right: 1px solid rgba(255,255,255,0.2); padding-right: 20px;">
        <div style="background: #3b82f6; color: white; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;" id="selectedCount">0</div>
        <span style="font-size: 11.5px; font-weight: 600; letter-spacing: 0.5px;">Paket Terpilih</span>
    </div>
    
    <div style="display: flex; gap: 10px;">
        <button type="button" id="btnBulkFoto" style="background: #10b981; color: white; border: none; padding: 8px 18px; border-radius: 10px; font-size: 11.5px; font-weight: 700; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s;">
            <i data-lucide="camera" style="width: 16px; height: 16px;"></i> Ambil Foto Masal
        </button>
        <button type="button" id="btnCancelBulk" style="background: transparent; color: #94a3b8; border: 1px solid #475569; padding: 8px 16px; border-radius: 10px; font-size: 11.5px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
            Batal
        </button>
    </div>
</div>
<?php endif; ?>
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

<style>
    .paket-filter-form {
        width: 100%;
    }
    
    .search-input-group {
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    
    .search-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
    }
    
    .search-input-group:focus-within .input-group-text,
    .search-input-group:focus-within input {
        border-color: #3b82f6 !important;
    }

    .quick-stats-bar {
        background: #f8fafc;
        border-bottom: 1px solid #eef2f6;
        padding: 0.85rem 1.25rem;
        display: flex;
        gap: 1.5rem;
        align-items: center;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        white-space: nowrap;
    }
    .quick-stats-bar::-webkit-scrollbar {
        display: none;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.25rem !important;
        }

        .page-title {
            font-size: 1.35rem !important;
        }

        .page-actions {
            width: 100%;
            justify-content: flex-start !important;
            gap: 0.5rem !important;
        }

        .paket-filter-header {
            padding: 1rem 0.75rem !important;
        }

        .filter-search-wrapper {
            width: 100% !important;
        }

        .filter-secondary-group {
            width: 100% !important;
        }

        .filter-date-wrapper,
        .filter-status-wrapper {
            flex: 1 1 45% !important;
            min-width: 0 !important;
        }

        .filter-sort-wrapper {
            flex: 1 1 100% !important;
        }

        .table-responsive {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .paket-table {
            min-width: 860px;
        }

        .paket-table th, 
        .paket-table td {
            white-space: nowrap !important;
        }
    }
</style>

<?php clearOldInput(); ?>

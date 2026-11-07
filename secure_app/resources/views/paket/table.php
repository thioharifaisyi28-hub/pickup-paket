<?php
$activeSort = ($sort ?? 'priority') === 'fifo' ? 'fifo' : 'priority';
?>
    <div class="table-responsive">
        <table class="table table-custom paket-table">
            <thead>
                <tr>
                    <?php if (!isAdmin()): ?>
                    <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="selectAllPaket" style="cursor: pointer; width: 18px; height: 18px; border-radius: 4px; border: 2px solid #cbd5e1;">
                    </th>
                    <?php endif; ?>
                    <th class="col-no">NO PAKET</th>
                    <th class="col-nopesanan">NO PESANAN</th>
                    <th class="col-nama">NAMA</th>
                    <th class="col-ekspedisi" style="text-align: center;">EKSPEDISI & SLA</th>
                    <th class="col-prioritas" style="text-align: center;">SKOR PRIORITAS</th>
                    <th class="col-tanggal">TANGGAL</th>
                    <th class="col-kode">KODE</th>
                    <th class="col-sesi">SESI</th>
                    <th class="col-status">STATUS</th>
                    <?php if (isAdmin()): ?>
                    <th class="col-petugas">PETUGAS</th>
                    <?php endif; ?>
                    <th class="col-aksi">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pakets)): ?>
                <tr>
                    <td colspan="<?= isAdmin() ? 11 : 10 ?>" style="text-align:center; padding:2rem; color:var(--text-muted);">
                        Data tidak ditemukan.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($pakets as $row):
                        $ekspCode   = strtoupper((string)($row['nama_ekspedisi'] ?? 'SPX'));
                        $layanan    = strtoupper((string)($row['layanan_ekspedisi'] ?? 'REGULER'));
                        $cutoffDisp = !empty($row['cutoff_time']) ? substr((string)$row['cutoff_time'], 0, 5) : '17:00';
                        $pScore     = (float)($row['priority_score'] ?? 0);

                        if ($pScore >= 75.0) {
                            $pLevel = 'KRITIS';
                            $pBg    = '#fef2f2';
                            $pColor = '#dc2626';
                            $pBdr   = '#fecaca';
                            $pIcon  = 'flame';
                        } elseif ($pScore >= 60.0) {
                            $pLevel = 'TINGGI';
                            $pBg    = '#fffbeb';
                            $pColor = '#d97706';
                            $pBdr   = '#fde68a';
                            $pIcon  = 'zap';
                        } elseif ($pScore >= 45.0) {
                            $pLevel = 'SEDANG';
                            $pBg    = '#eff6ff';
                            $pColor = '#2563eb';
                            $pBdr   = '#bfdbfe';
                            $pIcon  = 'trending-up';
                        } else {
                            $pLevel = 'RENDAH';
                            $pBg    = '#f8fafc';
                            $pColor = '#475569';
                            $pBdr   = '#cbd5e1';
                            $pIcon  = 'clock';
                        }

                        $slaBadgeStyle = match ($layanan) {
                            'INSTANT' => 'background:#ffedd5;color:#c2410c;border:1px solid #fed7aa;',
                            'SAMEDAY' => 'background:#f3e8ff;color:#7e22ce;border:1px solid #e9d5ff;',
                            'EXPRESS' => 'background:#cffafe;color:#0e7490;border:1px solid #a5f3fc;',
                            'CARGO'   => 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;',
                            default   => 'background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;',
                        };
                    ?>
                    <tr class="paket-row" data-id="<?= $row['id'] ?>">
                        <?php if (!isAdmin()): ?>
                        <td style="text-align: center; vertical-align: middle;">
                            <?php 
                                $hasPhoto = !empty($row['latest_photo']);
                                $isMyPackage = $row['user_id'] == (auth()['id'] ?? 0);
                                $canSelect = $row['status'] !== 'Pending' && !$hasPhoto && $isMyPackage;
                                if ($canSelect):
                            ?>
                            <input type="checkbox" class="paket-checkbox" value="<?= $row['id'] ?>" style="cursor: pointer; width: 18px; height: 18px; border-radius: 4px; border: 2px solid #cbd5e1;">
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="col-no"><strong><?= e($row['no_paket']) ?></strong></td>
                        <td class="col-nopesanan"><?= e($row['no_pesanan']) ?></td>
                        <td class="col-nama"><?= e($row['nama_pemesan']) ?></td>
                        <td class="col-ekspedisi" style="text-align: center; vertical-align: middle;">
                            <div style="display: inline-flex; flex-direction: column; align-items: center; gap: 3px;">
                                <div style="display: inline-flex; align-items: center; gap: 5px;">
                                    <span style="font-weight: 800; font-size: 11.5px; color: #1e293b;"><?= e($ekspCode) ?></span>
                                    <span style="font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 999px; <?= $slaBadgeStyle ?>"><?= e($layanan) ?></span>
                                </div>
                                <span style="font-size: 10.5px; color: #64748b; font-weight: 600;">
                                    Cut-off <?= e($cutoffDisp) ?> • <?= max(1, (int)($row['jumlah_item'] ?? 1)) ?> item
                                </span>
                            </div>
                        </td>
                        <td class="col-prioritas" style="text-align: center; vertical-align: middle;">
                            <button type="button"
                                    class="btn-priority-detail"
                                    data-nopesanan="<?= e($row['no_pesanan']) ?>"
                                    data-nopaket="<?= e($row['no_paket']) ?>"
                                    data-nama="<?= e($row['nama_pemesan']) ?>"
                                    data-ekspedisi="<?= e($ekspCode) ?>"
                                    data-layanan="<?= e($layanan) ?>"
                                    data-cutoff="<?= e($cutoffDisp) ?>"
                                    data-masuk="<?= e($row['tanggal_masuk'] ?? '') ?>"
                                    data-score="<?= number_format($pScore, 2, '.', '') ?>"
                                    data-level="<?= e($pLevel) ?>"
                                    data-breakdown="<?= e($row['score_breakdown'] ?? '{}') ?>"
                                    title="Klik untuk melihat rincian rumus Priority Scheduling"
                                    style="background: <?= $pBg ?>; color: <?= $pColor ?>; border: 1px solid <?= $pBdr ?>; border-radius: 10px; padding: 4px 10px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 800; font-size: 12px; transition: transform 0.15s ease;">
                                <i data-lucide="<?= $pIcon ?>" style="width: 13px; height: 13px;"></i>
                                <span><?= number_format($pScore, 2) ?></span>
                                <span style="font-size: 9.5px; font-weight: 700; opacity: 0.85; border-left: 1px solid <?= $pBdr ?>; padding-left: 5px;"><?= $pLevel ?></span>
                            </button>
                        </td>
                        <td class="col-tanggal"><?= formatDateStacked($row['tanggal'], false) ?></td>
                        <td class="col-kode"><?= e($row['kode']) ?></td>
                        <td class="col-sesi">
                            <div style="display: inline-flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 4px 10px; gap: 8px; white-space: nowrap; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
                                <span style="font-weight: 700; color: #475569; font-size: 11.5px;"><?= e($row['sesi']) ?></span>
                                <?php if(!empty($row['jam'])): ?>
                                    <span style="width: 4px; height: 4px; border-radius: 50%; background: #cbd5e1;"></span>
                                    <span style="font-size: 11.5px; color: #0ea5e9; font-weight: 700;"><?= e($row['jam']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="col-status"><?= statusBadge($row['status']) ?></td>
                        <?php if (isAdmin()): ?>
                        <td class="col-petugas">
                            <?php 
                                $username = $row['input_by'] ?? 'System';
                                $firstLetter = strtoupper(substr($username, 0, 1));
                                $restOfName = substr($username, 1);
                            ?>
                            <div class="d-flex align-items-center justify-content-center" style="gap: 8px;">
                                <div class="avatar-sm" style="width:32px; height:32px; background:#3b82f6; color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700;">
                                    <?= $firstLetter ?>
                                </div>
                                <span style="color: #444; font-weight: 500;"><?= e($restOfName) ?></span>
                            </div>
                        </td>
                        <?php endif; ?>
                        <td class="col-aksi">
                                <form action="<?= url('/paket/' . $row['id'] . '/status') ?>" method="POST" style="margin:0; display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                    <?php if (!isAdmin()): ?>
                                        <?php $disableStaff = $row['status'] !== 'Pending' ? 'disabled' : ''; ?>
                                        <button type="submit" name="action" value="ambil" class="btn-action-sm btn-action-ambil delete-confirm" 
                                                <?= $disableStaff ?>
                                                data-title="Konfirmasi Ambil" 
                                                data-text="Apakah Anda yakin data yang di ambil tidak dapat di kembalikan?">Ambil</button>
                                        
                                        <button type="submit" name="action" value="batal" class="btn-action-sm btn-action-batal delete-confirm"
                                                <?= $disableStaff ?>
                                                data-title="Konfirmasi Batal" 
                                                data-text="Apakah Anda yakin ingin membatalkan pesanan ini?">Batal</button>
                                        
                                        <button type="submit" name="action" value="retur" class="btn-action-sm btn-action-retur delete-confirm"
                                                <?= $disableStaff ?>
                                                data-title="Konfirmasi Retur" 
                                                data-text="Apakah Anda yakin ingin meretur pesanan ini?">Retur</button>
                                    <?php endif; ?>

                                    <?php 
                                        $hasPhoto = !empty($row['latest_photo']);
                                        $isStaff = !isAdmin();
                                        $isPending = $row['status'] === 'Pending';
                                        
                                        // Cek kondisi tombol foto untuk staff
                                        $fotoSelesai = ($isStaff && $hasPhoto);
                                        $fotoDisabledPending = ($isStaff && $isPending);
                                        
                                        $fotoUrl = isAdmin() ? url('/foto-instan/' . $row['id']) : url('/foto-instan/capture/' . $row['id']);
                                    ?>
                                    
                                    <?php if ($fotoSelesai): ?>
                                        <button type="button" class="btn-action-sm" style="background: #94a3b8; color: white; margin-right: 4px; border: 1px solid #cbd5e1; cursor: not-allowed; opacity: 0.7; display: inline-flex; align-items: center; gap: 4px;" disabled title="Foto sudah diunggah">
                                            <i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Selesai
                                        </button>
                                    <?php else: ?>
                                        <?php if (isAdmin()): ?>
                                            <button type="button" class="btn-action-sm btn-lihat-foto" 
                                                    data-id="<?= $row['id'] ?>"
                                                    data-nopesanan="<?= e($row['no_pesanan']) ?>"
                                                    data-nama="<?= e($row['nama_pemesan']) ?>"
                                                    data-sesi="<?= e($row['sesi']) ?><?= !empty($row['jam']) ? ' (' . e($row['jam']) . ')' : '' ?>"
                                                    data-status="<?= e($row['status']) ?>"
                                                    data-petugas="<?= e($row['input_by'] ?? 'System') ?>"
                                                    data-tanggal="<?= date('d M Y', strtotime($row['tanggal'])) ?>"
                                                    style="background: #0ea5e9; color: white; margin-right: 4px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Lihat Foto">
                                                <i data-lucide="camera" style="width: 14px; height: 14px;"></i> Lihat Foto
                                            </button>
                                        <?php else: ?>
                                            <?php if ($fotoDisabledPending): ?>
                                                <button type="button" class="btn-action-sm" onclick="Swal.fire('Perhatian', 'Silakan tentukan status paket (Ambil/Batal/Retur) terlebih dahulu sebelum mengambil foto.', 'warning')" style="background: #94a3b8; color: white; margin-right: 4px; border: 1px solid #cbd5e1; cursor: not-allowed; opacity: 0.7; display: inline-flex; align-items: center; gap: 4px;" title="Pilih aksi Ambil/Batal/Retur terlebih dahulu">
                                                    <i data-lucide="camera-off" style="width: 14px; height: 14px;"></i> Ambil Foto
                                                </button>
                                            <?php elseif ($row['user_id'] != (auth()['id'] ?? 0)): ?>
                                                <button type="button" class="btn-action-sm" onclick="Swal.fire('Perhatian', 'Paket ini dieksekusi oleh petugas lain (<?= e($row['input_by'] ?? 'System') ?>). Anda tidak dapat mengambil fotonya.', 'warning')" style="background: #94a3b8; color: white; margin-right: 4px; border: 1px solid #cbd5e1; cursor: not-allowed; opacity: 0.7; display: inline-flex; align-items: center; gap: 4px;" title="Diambil oleh petugas lain">
                                                    <i data-lucide="camera-off" style="width: 14px; height: 14px;"></i> Ambil Foto
                                                </button>
                                            <?php else: ?>
                                                <a href="<?= $fotoUrl ?>" class="btn-action-sm" style="background: #0ea5e9; color: white; margin-right: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Ambil Foto">
                                                    <i data-lucide="camera" style="width: 14px; height: 14px;"></i> Ambil Foto
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (isAdmin()): ?>
                                        <a href="<?= url('/paket/' . $row['id'] . '/edit') ?>" class="btn-action-sm" style="background: #f59e0b; color: white; margin-right: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 3px;" title="Edit Parameter SLA & Paket">
                                            <i data-lucide="edit-2" style="width: 12px; height: 12px;"></i> Edit
                                        </a>
                                        <?php $disableReset = $row['status'] === 'Pending' ? 'disabled' : ''; ?>
                                        <button type="submit" name="action" value="reset" class="btn-action-sm btn-action-reset delete-confirm" 
                                                <?= $disableReset ?>
                                                style="background: #3b82f6; color: white;"
                                                data-title="Konfirmasi Reset" 
                                                data-text="Apakah Anda yakin ingin meriset status paket ini kembali ke Pending?">Reset</button>
                                    <?php endif; ?>
                            </form>
                            <?php if (isAdmin()): ?>
                                <form action="<?= url('/paket/' . $row['id'] . '/delete') ?>" method="POST" style="margin:0; display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="return_url" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                                    <button type="submit" class="btn-action-sm btn-action-delete delete-confirm" data-title="Konfirmasi Hapus" data-text="Data akan disembunyikan dari daftar, tetapi tetap ada di database. Lanjutkan?" style="background: #ef4444; color: white; margin-left: 4px;">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrapper" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-top: 1px solid #f1f5f9;">
        <div class="pagination-info" style="font-size: 13px; color: #64748b;">
            Menampilkan halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong>
        </div>
        <ul class="pagination" style="margin: 0;">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= url('/paket') ?>?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&tanggal=<?= urlencode($tanggal) ?>&sort=<?= urlencode($activeSort) ?>" aria-label="Previous" data-ajax="true">
                    <i data-lucide="chevron-left" style="width: 16px; height: 16px;"></i>
                </a>
            </li>
            
            <?php
            $sidePages = 1;
            $start = max(1, $page - $sidePages);
            $end = min($totalPages, $page + $sidePages);
            
            if ($start > 1) {
                echo '<li class="page-item"><a class="page-link" href="'.url('/paket').'?page=1&search='.urlencode($search).'&status='.urlencode($status).'&tanggal='.urlencode($tanggal).'&sort='.urlencode($activeSort).'" data-ajax="true">1</a></li>';
                if ($start > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('/paket') ?>?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&tanggal=<?= urlencode($tanggal) ?>&sort=<?= urlencode($activeSort) ?>" data-ajax="true"><?= $i ?></a>
                </li>
            <?php endfor;
            
            if ($end < $totalPages) {
                if ($end < $totalPages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="'.url('/paket').'?page='.$totalPages.'&search='.urlencode($search).'&status='.urlencode($status).'&tanggal='.urlencode($tanggal).'&sort='.urlencode($activeSort).'" data-ajax="true">'.$totalPages.'</a></li>';
            }
            ?>

            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= url('/paket') ?>?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&tanggal=<?= urlencode($tanggal) ?>&sort=<?= urlencode($activeSort) ?>" aria-label="Next" data-ajax="true">
                    <i data-lucide="chevron-right" style="width: 16px; height: 16px;"></i>
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
    
    <script>
        // Init Lucide icons for AJAX loaded content
        if (window.lucide) {
            window.lucide.createIcons();
        }

        // Update Quick Stats Bar dynamically when this partial is loaded via AJAX
        if ($('#statTotal').length) {
            $('#statTotal').text('<?= number_format($stats['total'] ?? 0) ?>');
            $('#statPending').text('<?= number_format($stats['pending'] ?? 0) ?>');
            $('#statSudah').text('<?= number_format($stats['sudah'] ?? 0) ?>');
            $('#statBatal').text('<?= number_format($stats['batal'] ?? 0) ?>');
            $('#statRetur').text('<?= number_format($stats['retur'] ?? 0) ?>');
        }
    </script>

<?php
$currentEksp = strtoupper((string)($paket['nama_ekspedisi'] ?? 'SPX'));
$currentLayanan = strtoupper((string)($paket['layanan_ekspedisi'] ?? 'REGULER'));
$currentCutoff = !empty($paket['cutoff_time']) ? substr((string)$paket['cutoff_time'], 0, 5) : '17:00';
$currentScore = (float)($paket['priority_score'] ?? 0);
?>
<div class="row">
    <div class="col-12 col-lg-11 col-xl-10" style="max-width: 980px;">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h1 class="page-title" style="margin-bottom: 2px;">EDIT PAKET #<?= e($paket['no_paket']) ?></h1>
                <p class="text-muted" style="margin: 0; font-size: 12px;">Perbarui informasi pesanan, parameter SLA kurir, dan kalkulasi ulang skor prioritas</p>
            </div>
            <a href="<?= url('/paket?tanggal=' . urlencode($paket['tanggal'] ?? date('Y-m-d'))) ?>" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; height: 34px; padding: 0 13px; font-weight: 600; font-size: 12px; text-decoration: none; border: 1px solid #cbd5e1; color: #475569; background: #fff;">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Antrean
            </a>
        </div>

        <div class="card" style="padding: 0; border-radius: 13px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05); overflow: hidden;">
            <div class="card-header" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); padding: 0.95rem 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 13.5px; color: #0f172a;">No. Pesanan: <?= e($paket['no_pesanan']) ?></div>
                        <div style="font-size: 11px; color: #64748b;">Waktu Masuk: <?= e($paket['tanggal_masuk'] ?? '-') ?></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 7px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 600;">Skor Saat Ini:</span>
                    <span style="background: #0f172a; color: #fff; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 20px;">
                        <?= number_format($currentScore, 2) ?>
                    </span>
                </div>
            </div>

            <div class="card-body" style="padding: 1.35rem 1.5rem;">
                <form action="<?= url('/paket/' . $paket['id'] . '/update') ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-12 col-sm-4 col-md-3">
                            <label class="form-label fw-semibold text-dark">No. Paket</label>
                            <input type="text" name="no_paket" class="form-control" value="<?= e($paket['no_paket']) ?>" readonly style="background:#f1f5f9; border-radius: 8px; height: 36px; font-weight: 700;">
                        </div>

                        <div class="col-12 col-sm-8 col-md-5">
                            <label for="no_pesanan" class="form-label fw-semibold text-dark">No. Pesanan / Resi <span class="text-danger">*</span></label>
                            <input type="text" name="no_pesanan" id="no_pesanan" class="form-control" value="<?= e($paket['no_pesanan']) ?>" required style="border-radius: 8px; height: 36px;">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="status" class="form-label fw-semibold text-dark">Status Paket</label>
                            <select name="status" id="status" class="form-select" style="border-radius: 8px; height: 36px;">
                                <?php foreach (['Pending', 'Sudah', 'Batal', 'Retur'] as $st): ?>
                                    <option value="<?= $st ?>" <?= ($paket['status'] ?? 'Pending') === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-5">
                            <label for="nama_pemesan" class="form-label fw-semibold text-dark">Nama Pemesan / Penerima <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pemesan" id="nama_pemesan" class="form-control" value="<?= e($paket['nama_pemesan']) ?>" required style="border-radius: 8px; height: 36px;">
                        </div>

                        <div class="col-12 col-sm-4 col-md-3">
                            <label for="kode" class="form-label fw-semibold text-dark">Kode Rak / Barang</label>
                            <input type="text" name="kode" id="kode" class="form-control" value="<?= e($paket['kode'] ?? '') ?>" style="border-radius: 8px; height: 36px;">
                        </div>

                        <div class="col-12 col-sm-4 col-md-2">
                            <label for="tanggal" class="form-label fw-semibold text-dark">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= e($paket['tanggal']) ?>" required style="border-radius: 8px; height: 36px;">
                        </div>

                        <div class="col-12 col-sm-4 col-md-2">
                            <label for="sesi" class="form-label fw-semibold text-dark">Sesi <span class="text-danger">*</span></label>
                            <input type="text" name="sesi" id="sesi" class="form-control" value="<?= e($paket['sesi']) ?>" required style="border-radius: 8px; height: 36px;">
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 11px; padding: 1.1rem 1.25rem; margin-top: 1.35rem;">
                        <div style="display: flex; align-items: center; gap: 7px; margin-bottom: 0.9rem; padding-bottom: 0.6rem; border-bottom: 1px dashed #cbd5e1;">
                            <i data-lucide="cpu" style="width: 15px; height: 15px; color: #2563eb;"></i>
                            <span style="font-size: 11.5px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px;">Parameter Algoritma Priority Scheduling</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="nama_ekspedisi" class="form-label fw-semibold text-dark">Ekspedisi Penjemput</label>
                                <select name="nama_ekspedisi" id="nama_ekspedisi" class="form-select" style="border-radius: 8px; height: 36px;">
                                    <?php foreach (($masterEkspedisi ?? []) as $kodeEksp => $prof): ?>
                                        <?php if ($kodeEksp === 'SAMEDAY' && $currentEksp !== 'SAMEDAY') continue; ?>
                                        <option value="<?= e($kodeEksp) ?>"
                                                data-layanan="<?= e($prof['default_layanan'] ?? 'REGULER') ?>"
                                                data-cutoff="<?= e(substr((string)($prof['cutoff_time'] ?? '17:00:00'), 0, 5)) ?>"
                                                <?= ($currentEksp === $kodeEksp || ($kodeEksp === 'INSTANT' && $currentEksp === 'SAMEDAY')) ? 'selected' : '' ?>>
                                            <?= $kodeEksp === 'INSTANT' ? 'INSTAN / SAMEDAY — Kurir Instan &amp; Same-Day (GoSend / Grab / SPX Instant)' : (e($kodeEksp) . (!empty($prof['nama_lengkap']) ? ' — ' . e($prof['nama_lengkap']) : '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="layanan_ekspedisi" class="form-label fw-semibold text-dark">Tipe Layanan (SLA)</label>
                                <select name="layanan_ekspedisi" id="layanan_ekspedisi" class="form-select" style="border-radius: 8px; height: 36px;">
                                    <?php foreach (['INSTANT' => 'INSTAN / SAMEDAY (Bobot 100)', 'EXPRESS' => 'EXPRESS (Bobot 75)', 'REGULER' => 'REGULER (Bobot 50)', 'CARGO' => 'CARGO (Bobot 25)'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= ($currentLayanan === $val || ($val === 'INSTANT' && $currentLayanan === 'SAMEDAY')) ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cutoff_time" class="form-label fw-semibold text-dark">Batas Cut-Off Kurir</label>
                                <input type="time" name="cutoff_time" id="cutoff_time" class="form-control" value="<?= e($currentCutoff) ?>" style="border-radius: 8px; height: 36px;">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="jumlah_item" class="form-label fw-semibold text-dark">Jml Item</label>
                                <input type="number" name="jumlah_item" id="jumlah_item" class="form-control" min="1" value="<?= (int)($paket['jumlah_item'] ?? 1) ?>" style="border-radius: 8px; height: 36px;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1.35rem; padding-top: 1.05rem; border-top: 1px solid #e2e8f0;" class="d-flex gap-2 justify-content-end">
                        <a href="<?= url('/paket?tanggal=' . urlencode($paket['tanggal'] ?? date('Y-m-d'))) ?>" class="btn btn-light border px-3" style="border-radius: 8px; height: 36px; display: inline-flex; align-items: center; font-weight: 600;">Batal</a>
                        <button type="submit" class="btn btn-primary px-3 d-inline-flex align-items-center gap-2" style="border-radius: 8px; height: 36px; font-weight: 700;">
                            <i data-lucide="save" style="width: 14px; height: 14px;"></i> Simpan &amp; Hitung Ulang Skor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ekspSelect = document.getElementById('nama_ekspedisi');
    const layananSelect = document.getElementById('layanan_ekspedisi');
    const cutoffInput = document.getElementById('cutoff_time');

    if (ekspSelect) {
        ekspSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt) return;
            const defLayanan = opt.getAttribute('data-layanan');
            const defCutoff = opt.getAttribute('data-cutoff');
            if (defLayanan && layananSelect) {
                layananSelect.value = defLayanan;
            }
            if (defCutoff && cutoffInput) {
                cutoffInput.value = defCutoff;
            }
        });
    }
});
</script>

<?php
$selectedDate = old('tanggal', $_GET['tanggal'] ?? date('Y-m-d'));
$selectedEksp = strtoupper(old('nama_ekspedisi', 'SPX'));
$selectedLayanan = strtoupper(old('layanan_ekspedisi', 'REGULER'));
$defaultCutoff = old('cutoff_time', isset($masterEkspedisi[$selectedEksp]['cutoff_time']) ? substr((string)$masterEkspedisi[$selectedEksp]['cutoff_time'], 0, 5) : '17:00');
?>

<style>
    .paket-create-wrapper {
        max-width: 980px;
    }

    .paket-create-card {
        padding: 0 !important;
        border-radius: 13px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        background: #ffffff;
    }

    .paket-create-card .card-header {
        margin-bottom: 0 !important;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 0.95rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }

    .paket-create-card .card-body {
        padding: 1.35rem 1.5rem;
    }

    .paket-create-card .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .paket-create-card .form-control,
    .paket-create-card .form-select {
        height: 36px;
        min-height: 36px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 12.5px;
        color: #0f172a;
        background-color: #ffffff;
        box-shadow: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .paket-create-card .form-control {
        padding: 0.4rem 0.8rem;
    }

    .paket-create-card .form-select {
        padding: 0.4rem 2.1rem 0.4rem 0.8rem;
    }

    .paket-create-card .form-control::placeholder {
        color: #94a3b8;
    }

    .paket-create-card .form-control:focus,
    .paket-create-card .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .paket-create-card .priority-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 11px;
        padding: 1.1rem 1.25rem;
        margin-top: 1.35rem;
    }

    .paket-create-card .field-hint {
        display: block;
        margin-top: 0.3rem;
        font-size: 10.5px;
        color: #64748b;
        line-height: 1.35;
    }

    .paket-create-card .form-actions {
        margin-top: 1.35rem;
        padding-top: 1.05rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .paket-create-card .btn-action {
        height: 36px;
        padding: 0 1.15rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        font-size: 12.5px;
    }

    @media (max-width: 767.98px) {
        .paket-create-card .card-header {
            padding: 0.85rem 1rem;
        }
        .paket-create-card .card-body {
            padding: 1rem;
        }
        .paket-create-card .priority-box {
            padding: 0.95rem;
        }
    }
</style>

<div class="row">
    <div class="col-12 col-lg-11 col-xl-10 paket-create-wrapper">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <h1 class="page-title" style="margin-bottom: 2px;">TAMBAH PAKET BARU</h1>
                <p class="text-muted" style="margin: 0; font-size: 12px;">Input paket manual beserta parameter penjadwalan <strong>Priority Scheduling</strong></p>
            </div>
            <a href="<?= url('/paket?tanggal=' . urlencode($selectedDate)) ?>" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; height: 34px; padding: 0 13px; font-weight: 600; font-size: 12px; text-decoration: none; border: 1px solid #cbd5e1; color: #475569; background: #fff;">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Kembali ke Antrean
            </a>
        </div>

        <div class="card paket-create-card">
            <div class="card-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="package-plus" style="width: 16px; height: 16px;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 13.5px; color: #0f172a;">Formulir Data Pesanan &amp; SLA Kurir</div>
                        <div style="font-size: 11px; color: #64748b;">Skor prioritas dihitung otomatis saat paket disimpan</div>
                    </div>
                </div>
                <span style="background: #dbeafe; color: #1d4ed8; font-size: 10.5px; font-weight: 700; padding: 4px 10px; border-radius: 999px; letter-spacing: 0.2px;">
                    S = 0.40C + 0.30T + 0.15V + 0.15A
                </span>
            </div>

            <div class="card-body">
                <form action="<?= url('/paket/store') ?>" method="POST">
                    <?= csrf_field() ?>

                    <!-- Bagian Atas: Grid Input Utama -->
                    <div class="row g-3">
                        <!-- Baris 1: 2 Kolom Proporsional 50:50 -->
                        <div class="col-12 col-md-6">
                            <label for="no_pesanan" class="form-label">
                                No. Pesanan / Resi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="no_pesanan" id="no_pesanan" class="form-control" placeholder="Contoh: 261030SPX001" value="<?= e(old('no_pesanan')) ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="nama_pemesan" class="form-label">
                                Nama Pemesan / Penerima <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_pemesan" id="nama_pemesan" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= e(old('nama_pemesan')) ?>" required>
                        </div>

                        <!-- Baris 2: 4 Kolom Sejajar (Jam Sesi dipisah dengan label tersendiri) -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="kode" class="form-label">Kode Rak / Barang</label>
                            <input type="text" name="kode" id="kode" class="form-control" placeholder="Contoh: POLO-A1" value="<?= e(old('kode')) ?>">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="tanggal" class="form-label">
                                Tanggal Operasional <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= e($selectedDate) ?>" required>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="sesi" class="form-label">
                                Sesi Pick-Up <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="sesi" id="sesi" list="sesiOptions" class="form-control" value="<?= e(old('sesi', 'SESI 1')) ?>" required placeholder="Pilih / ketik sesi">
                            <datalist id="sesiOptions">
                                <option value="SESI 1">
                                <option value="SESI 2">
                                <option value="SESI 3">
                                <option value="INSTAN / SAMEDAY">
                            </datalist>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="jam" class="form-label">
                                Jam Sesi <span class="text-muted fw-normal" style="font-size: 10.5px;">(Opsional)</span>
                            </label>
                            <input type="time" name="jam" id="jam" class="form-control" value="<?= e(old('jam')) ?>" title="Jam Sesi (Opsional)">
                        </div>
                    </div>

                    <!-- Box Parameter Algoritma Priority Scheduling (2x2 Grid) -->
                    <div class="priority-box">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-bottom: 0.9rem; padding-bottom: 0.6rem; border-bottom: 1px dashed #cbd5e1;">
                            <div style="display: flex; align-items: center; gap: 7px;">
                                <i data-lucide="cpu" style="width: 15px; height: 15px; color: #2563eb;"></i>
                                <span style="font-size: 11.5px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px;">Parameter Algoritma Priority Scheduling</span>
                            </div>
                            <span style="font-size: 11px; color: #64748b;">Menentukan bobot SLA (T) &amp; urgensi Cut-Off (C)</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="nama_ekspedisi" class="form-label">Ekspedisi Penjemput</label>
                                <select name="nama_ekspedisi" id="nama_ekspedisi" class="form-select">
                                    <?php foreach (($masterEkspedisi ?? []) as $kodeEksp => $prof): ?>
                                        <?php if ($kodeEksp === 'SAMEDAY') continue; ?>
                                        <option value="<?= e($kodeEksp) ?>"
                                                data-layanan="<?= e($prof['default_layanan'] ?? 'REGULER') ?>"
                                                data-cutoff="<?= e(substr((string)($prof['cutoff_time'] ?? '17:00:00'), 0, 5)) ?>"
                                                <?= ($selectedEksp === $kodeEksp || ($kodeEksp === 'INSTANT' && $selectedEksp === 'SAMEDAY')) ? 'selected' : '' ?>>
                                            <?= $kodeEksp === 'INSTANT' ? 'INSTAN / SAMEDAY — Kurir Instan &amp; Same-Day (GoSend / Grab / SPX Instant)' : (e($kodeEksp) . (!empty($prof['nama_lengkap']) ? ' — ' . e($prof['nama_lengkap']) : '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-hint">Memilih ekspedisi akan menyesuaikan tipe layanan &amp; jam cut-off default secara otomatis.</span>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="layanan_ekspedisi" class="form-label">Tipe Layanan (SLA)</label>
                                <select name="layanan_ekspedisi" id="layanan_ekspedisi" class="form-select">
                                    <?php foreach (['INSTANT' => 'INSTAN / SAMEDAY (Bobot 100)', 'EXPRESS' => 'EXPRESS (Bobot 75)', 'REGULER' => 'REGULER (Bobot 50)', 'CARGO' => 'CARGO (Bobot 25)'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>" <?= ($selectedLayanan === $val || ($val === 'INSTANT' && $selectedLayanan === 'SAMEDAY')) ? 'selected' : '' ?>><?= $lbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="field-hint">Bobot tingkat kepentingan layanan pengiriman (komponen T pada rumus prioritas).</span>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cutoff_time" class="form-label">Batas Cut-Off Kurir</label>
                                <input type="time" name="cutoff_time" id="cutoff_time" class="form-control" value="<?= e($defaultCutoff) ?>">
                                <span class="field-hint">Batas maksimal jam penjemputan kurir ekspedisi hari ini (komponen C).</span>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="jumlah_item" class="form-label">Jml Item</label>
                                <input type="number" name="jumlah_item" id="jumlah_item" class="form-control" min="1" value="<?= e(old('jumlah_item', '1')) ?>">
                                <span class="field-hint">Total kuantitas fisik barang/koli di dalam nomor pesanan ini.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi di Kanan Bawah -->
                    <div class="form-actions">
                        <a href="<?= url('/paket?tanggal=' . urlencode($selectedDate)) ?>" class="btn btn-light border btn-action" style="font-weight: 600; color: #475569; text-decoration: none;">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary btn-action" style="font-weight: 700;">
                            <i data-lucide="save" style="width: 14px; height: 14px;"></i> Simpan &amp; Jadwalkan Paket
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
<?php clearOldInput(); ?>

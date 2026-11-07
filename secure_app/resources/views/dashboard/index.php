<div class="animate__animated animate__fadeIn">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold" style="color: var(--accent-color);">
                <?= (isset($_SESSION['user']['role']) && strtolower(trim($_SESSION['user']['role'])) === 'admin') ? 'ADMIN PT. POLO SPORT INDONESIA' : 'PT. POLO SPORT INDONESIA' ?>
            </h1>
            <p class="text-muted small mb-0">Ringkasan status paket dan operasional harian.</p>
        </div>
        <div class="mt-3 mt-md-0">
            <div class="d-inline-flex align-items-center bg-white px-3 py-2 rounded-3 shadow-sm border">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="calendar" class="text-primary" style="width:16px;"></i>
                    <span id="current-date-saas" class="small fw-medium text-secondary">-- --- ----</span>
                </div>
                <div class="mx-3 border-end" style="height: 16px;"></div>
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="clock" class="text-primary" style="width:16px;"></i>
                    <span id="current-time-saas" class="small fw-bold text-dark" style="font-family: monospace;">00.00.00 WIB</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex align-items-center">
                    <div class="bg-blue-soft p-3 rounded-4 me-3 d-none d-sm-block">
                        <i data-lucide="package" class="text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Total Paket</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($stats['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex align-items-center">
                    <div class="p-3 rounded-4 me-3 d-none d-sm-block" style="background-color: rgba(245, 158, 11, 0.1);">
                        <i data-lucide="clock" style="color: #f59e0b;"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Paket Pending</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($stats['pending'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex align-items-center">
                    <div class="p-3 rounded-4 me-3 d-none d-sm-block" style="background-color: rgba(16, 185, 129, 0.1);">
                        <i data-lucide="check-circle" style="color: #10b981;"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Sudah Pickup</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($stats['sudah'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex align-items-center">
                    <div class="p-3 rounded-4 me-3 d-none d-sm-block" style="background-color: rgba(239, 68, 68, 0.1);">
                        <i data-lucide="x-circle" style="color: #ef4444;"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Paket Batal</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($stats['batal'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4 d-flex align-items-center">
                    <div class="p-3 rounded-4 me-3 d-none d-sm-block" style="background-color: rgba(59, 130, 246, 0.1);">
                        <i data-lucide="refresh-ccw" style="color: #3b82f6;"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium mb-1" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Paket Retur</div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($stats['retur'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Packages Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">PAKET TERBARU</h6>
            <a href="<?= url('/paket') ?>" class="btn btn-sm btn-primary px-3 rounded-3">Daftar Paket</a>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3 border-0">No. Paket</th>
                            <th class="py-3 border-0">No. Pesanan</th>
                            <th class="py-3 border-0">Penerima</th>
                            <th class="py-3 border-0">Status</th>
                            <th class="py-3 pe-4 border-0">Tanggal Masuk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_paket)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="py-4">
                                    <i data-lucide="inbox" style="width: 48px; height: 48px;" class="text-muted mb-3 opacity-25"></i>
                                    <h6 class="text-muted fw-normal">Belum ada data paket terbaru.</h6>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            $i = 1;
                            foreach ($recent_paket as $row): 
                            ?>
                            <tr>
                                <td class="ps-4 py-3 fw-bold text-primary">#<?= $i++ ?></td>
                                <td class="py-3 fw-medium text-dark"><?= e($row['no_pesanan']) ?></td>
                                <td class="py-3 text-secondary"><?= e($row['nama_pemesan']) ?></td>
                                <td class="py-3"><?= statusBadge($row['status']) ?></td>
                                <td class="pe-4 py-3 small text-secondary"><?= formatDate($row['tanggal_masuk']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Clock Implementation
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const timeStr = `${hours}.${minutes}.${seconds} WIB`;
        const dateStr = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        
        $('#current-time-saas').text(timeStr);
        $('#current-date-saas').text(dateStr);
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Welcome Alert
    <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true): ?>
    Swal.fire({
        title: 'Selamat Datang!',
        html: `
            <div style="text-align: center;">
                <img src="<?= asset('images/polologin.png') ?>" alt="Polo Logo" style="width: 100px; max-height: 50px; object-fit: contain; height: auto; margin-bottom: 15px; opacity: 0.9;">
                <p style="font-size: 16px; color: #334155; margin-bottom: 5px;"><strong>Selamat datang kembali, <?= e(auth()['username']) ?>!</strong></p>
                <p style="font-size: 14px; color: #64748B;">POLO SPORT INDONESIA</p>
            </div>
        `,
        icon: 'success',
        confirmButtonText: 'Lanjutkan',
        confirmButtonColor: '#3B82F6',
        allowOutsideClick: false,
        allowEscapeKey: false,
        timer: 4000,
        timerProgressBar: true,
        width: '420px',
        customClass: {
            container: 'swal-welcome-container',
            popup: 'swal-welcome-popup',
            confirmButton: 'swal-welcome-button'
        }
    });
    <?php unset($_SESSION['login_success']); ?>
    <?php endif; ?>
});
</script>

<style>
.swal-welcome-popup {
    border-radius: 20px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.8) !important;
}

.swal-welcome-button {
    border-radius: 8px !important;
    padding: 10px 30px !important;
    font-weight: 600 !important;
    font-size: 14px !important;
}
</style>

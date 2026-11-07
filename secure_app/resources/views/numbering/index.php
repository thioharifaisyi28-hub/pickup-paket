<?php
/**
 * resources/views/numbering/index.php
 */
?>

<div class="page-header animate__animated animate__fadeIn" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap;">
    <div class="page-title" style="flex: 1; min-width: 300px;">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 10px;">
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px -4px rgba(37, 99, 235, 0.3);">
                <i data-lucide="list-ordered" style="color: white; width: 18px; height: 18px;"></i>
            </div>
            <div>
                <h1 style="font-size: 1.35rem; font-weight: 800; color: #0f172a; letter-spacing: -0.04em; margin: 0; line-height: 1;">Numbering <span style="background: linear-gradient(to right, #3b82f6, #6366f1); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Paket</span></h1>
            </div>
        </div>
        
    </div>
    
    <div class="page-actions" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 1rem;">
        <!-- Primary Actions Group -->
        <div style="display: flex; align-items: center; gap: 0.5rem; background: white; padding: 6px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <form action="<?= url('/numbering/export') ?>" method="GET" style="margin:0;" id="exportForm">
                <input type="hidden" name="search" id="exportSearch">
                <input type="hidden" name="filter" id="exportFilter">
                <input type="hidden" name="tanggal" id="exportTanggal" value="<?= e($tanggal ?? date('Y-m-d')) ?>">
                <button type="submit" class="btn-premium btn-export" style="padding: 8px 16px;">
                    <i data-lucide="file-spreadsheet"></i> Export Excel
                </button>
            </form>
        </div>

        <?php if (isAdmin()): ?>
        <!-- Admin Danger Zone Group -->
        <div style="display: flex; align-items: center; gap: 0.5rem; background: #fff1f2; padding: 6px; border-radius: 12px; border: 1px solid #ffe4e6;">
            <button type="button" class="btn-danger-outline" id="btnResetAll" style="padding: 8px 16px; background: white;">
                <i data-lucide="rotate-ccw"></i> Reset Semua
            </button>
            <button type="button" class="btn-danger-solid" id="btnDeleteSelected" style="padding: 8px 16px;">
                <i data-lucide="trash-2"></i> Hapus Terpilih
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>



<style>
/* Premium Buttons */
.btn-premium {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}

.btn-premium i {
    width: 16px;
    height: 16px;
}

.btn-export {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-export:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
    transform: translateY(-1px);
}

.btn-import {
    background: #10b981;
    color: white;
}

.btn-import:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
}


.btn-add {
    background: #3b82f6;
    color: white;
}

.btn-add:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* Danger Zone Buttons */
.btn-danger-outline {
    background: white;
    color: #ef4444;
    border: 1px solid #fee2e2;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-danger-outline:hover {
    background: #fef2f2;
    border-color: #fca5a5;
    transform: translateY(-1px);
}

.btn-danger-solid {
    background: #ef4444;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
}

.btn-danger-solid:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.25);
}
</style>

<div class="card animate__animated animate__fadeIn" style="margin-top: 0.75rem; padding: 0;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1.15rem; gap: 0.75rem; margin-bottom: 0;">
        <div style="display: flex; gap: 1rem; flex: 1;">
            <div class="search-box" style="position: relative; width: 100%; max-width: 400px;">
                <i data-lucide="search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; color: #64748b;"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Cari Nama / No Pesanan / No Paket / Kode" style="padding-left: 40px; border-radius: 10px;">
            </div>
            <div class="filter-box" style="width: 160px;">
                <input type="date" id="tanggalFilter" class="form-control" value="<?= e($tanggal ?? date('Y-m-d')) ?>" style="border-radius: 10px; cursor: pointer;">
            </div>
            <div class="filter-box" style="width: 180px;">
                <select id="statusFilter" class="form-control" style="border-radius: 10px; cursor: pointer;">
                    <option value="all">Semua Paket</option>
                    <option value="not_selected">Belum Terpilih</option>
                    <option value="selected">Terpilih</option>
                </select>
            </div>
        </div>
        <div id="selectionStats" style="display: flex; align-items: center; gap: 8px; background: #eff6ff; color: #1d4ed8; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 13px; border: 1px solid #dbeafe; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <i data-lucide="check-circle-2" style="width: 16px; height: 16px;"></i>
            <span id="selectedCount">0</span> Paket Terpilih
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-premium" id="numberingTable">
            <thead>
                <tr>
                    <th style="width: 100px;">Aksi</th>
                    <th style="width: 120px;">No Paket</th>
                    <th style="width: 180px;">No Pesanan</th>
                    <th>Nama Pemesan</th>
                    <th style="width: 150px;">Tanggal</th>
                    <th style="width: 100px;">Kode</th>
                    <th style="width: 150px;">Sesi</th>
                    <th style="width: 150px;">Petugas</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <!-- Data loaded via AJAX -->
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Memuat data paket...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div class="card-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fafafa; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
        <div class="pagination-info" style="font-size: 13px; color: #64748b;">
            Menampilkan <span id="pageRange">0-0</span> dari <span id="totalRecords">0</span> paket
        </div>
        <div id="paginationButtons" class="pagination-container" style="display: flex; gap: 5px;">
            <!-- Buttons dynamically generated -->
        </div>
    </div>
</div>

<style>
.table-premium thead th {
    background: #f8fafc;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 14px 1.5rem;
    border-bottom: 2px solid #f1f5f9;
    border-top: none;
    white-space: nowrap;
}

.table-premium tbody td {
    padding: 16px 1.5rem;
    vertical-align: middle;
    color: #334155;
    font-size: 13.5px;
    border-bottom: 1px solid #f1f5f9;
}

.table-premium tbody tr {
    transition: all 0.2s;
}

.table-premium tbody tr:hover {
    background-color: #f8fafc;
}

.table-premium .row-selected {
    background-color: #f0f9ff !important;
}

.table-premium .row-selected td {
    border-bottom-color: #e0f2fe;
}

/* Avatar Style */
.avatar-xs-premium {
    width: 32px;
    height: 32px;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    border: 2px solid white;
    box-shadow: 0 0 0 1px #e2e8f0;
}

/* Badge Styles */
.badge-no-paket {
    background: #f1f5f9;
    color: #475569;
    padding: 4px 10px;
    border-radius: 8px;
    font-family: 'JetBrains Mono', 'Monaco', monospace;
    font-weight: 600;
    font-size: 12px;
    border: 1px solid #e2e8f0;
}

.badge-kode-premium {
    background: #fff;
    color: #3b82f6;
    padding: 2px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid #dbeafe;
    box-shadow: 0 1px 2px rgba(59, 130, 246, 0.05);
}

.btn-select-premium {
    padding: 6px 16px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-select-premium:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background: #f0f9ff;
}

.btn-select-premium.active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
}

.num-urut-premium {
    background: #3b82f6;
    color: white;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 800;
    margin-right: 8px;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
}

/* Modern Pagination Styles */
.pagination-container {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pagination-btn {
    min-width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 12px;
    border-radius: 10px;
    background: white;
    border: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.pagination-btn:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #1e293b;
    transform: translateY(-1px);
}

.pagination-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f1f5f9;
}

.pagination-ellipsis {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    color: #94a3b8;
    font-weight: 700;
    font-size: 14px;
}
</style>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('show');
    if (window.lucide) {
        window.lucide.createIcons();
    }
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

window.addEventListener('click', function(e) {
    const modals = [];
    modals.forEach(id => {
        const modal = document.getElementById(id);
        if (e.target === modal) {
            closeModal(id);
        }
    });
});

$(document).ready(function() {
    let debounceTimer;
    let currentPage = 1;

    // Handler untuk tombol simpan dengan konfirmasi centang
    $('.save-confirm').on('click', function(e) {
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
    
    // Export Form Sync
    $('#exportForm').on('submit', function() {
        $('#exportSearch').val($('#searchInput').val());
        $('#exportFilter').val($('#statusFilter').val());
        $('#exportTanggal').val($('#tanggalFilter').val());
    });

    // Initial Load
    loadData();

    // Search Input Event
    $('#searchInput').on('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentPage = 1;
            loadData();
        }, 300);
    });

    // Status Filter Event
    $('#statusFilter').on('change', function() {
        currentPage = 1;
        loadData();
    });

    // Tanggal Filter Event
    $('#tanggalFilter').on('change', function() {
        currentPage = 1;
        loadData();
    });

    // Toggle Select Click
    $(document).on('click', '.btn-select-premium', function() {
        const id = $(this).data('id');
        toggleSelect(id, $(this));
    });

    // Pagination Click
    $(document).on('click', '.pagination-btn', function() {
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadData();
        }
    });

    // Reset All (Admin)
    $('#btnResetAll').on('click', function() {
        Swal.fire({
            title: 'Reset Semua Pilihan?',
            text: "Status terpilih pada semua paket akan dihapus.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url('/numbering/reset') ?>', {
                    _csrf_token: '<?= csrf_token() ?>',
                    tanggal: $('#tanggalFilter').val()
                }, function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil', res.message, 'success');
                        loadData();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                });
            }
        });
    });

    // Delete Selected (Admin)
    $('#btnDeleteSelected').on('click', function() {
        const count = parseInt($('#selectedCount').text());
        if (count === 0) {
            Swal.fire('Info', 'Tidak ada paket yang dipilih.', 'info');
            return;
        }

        Swal.fire({
            title: 'Hapus Paket Terpilih?',
            text: `Anda akan menghapus ${count} paket yang sedang dipilih. Tindakan ini tidak bisa dibatalkan!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('<?= url('/numbering/delete-selected') ?>', {
                    _csrf_token: '<?= csrf_token() ?>',
                    tanggal: $('#tanggalFilter').val()
                }, function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil', res.message, 'success');
                        currentPage = 1;
                        loadData();
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                });
            }
        });
    });

    function loadData() {
        const search = $('#searchInput').val();
        const filter = $('#statusFilter').val();
        const tanggal = $('#tanggalFilter').val();
        
        // Update URL to reflect current date filter
        const newUrl = window.location.pathname + '?tanggal=' + tanggal;
        window.history.replaceState({ path: newUrl }, '', newUrl);

        $.get('<?= url('/numbering/data') ?>', { 
            search: search,
            filter: filter,
            tanggal: tanggal,
            page: currentPage
        }, function(res) {
            if (res.success) {
                renderTable(res.data);
                updateStats(res.total_selected);
                renderPagination(res.pagination);
            }
        });
    }

    function renderPagination(pg) {
        const container = $('#paginationButtons');
        container.empty();

        if (pg.total_records === 0) {
            $('#pageRange').text('0-0');
            $('#totalRecords').text(0);
            return;
        }

        const start = (pg.current_page - 1) * pg.limit + 1;
        const end = Math.min(pg.current_page * pg.limit, pg.total_records);
        $('#pageRange').text(`${start}-${end}`);
        $('#totalRecords').text(pg.total_records);

        if (pg.total_pages <= 1) return;

        // Prev Button
        container.append(`
            <button class="pagination-btn" ${pg.current_page === 1 ? 'disabled' : ''} data-page="${pg.current_page - 1}" title="Previous">
                <i data-lucide="chevron-left" style="width:18px; height:18px;"></i>
            </button>
        `);

        // Page Buttons
        const delta = 1; // Number of pages to show around current page
        const range = [];
        for (let i = 1; i <= pg.total_pages; i++) {
            if (i === 1 || i === pg.total_pages || (i >= pg.current_page - delta && i <= pg.current_page + delta)) {
                range.push(i);
            }
        }

        let l;
        for (let i of range) {
            if (l) {
                if (i - l === 2) {
                    container.append(`<button class="pagination-btn" data-page="${l + 1}">${l + 1}</button>`);
                } else if (i - l !== 1) {
                    container.append(`<span class="pagination-ellipsis">...</span>`);
                }
            }
            container.append(`
                <button class="pagination-btn ${i === pg.current_page ? 'active' : ''}" data-page="${i}">
                    ${i}
                </button>
            `);
            l = i;
        }

        // Next Button
        container.append(`
            <button class="pagination-btn" ${pg.current_page === pg.total_pages ? 'disabled' : ''} data-page="${pg.current_page + 1}" title="Next">
                <i data-lucide="chevron-right" style="width:18px; height:18px;"></i>
            </button>
        `);

        if (window.lucide) lucide.createIcons();
    }

    function renderTable(data) {
        const tbody = $('#tableBody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append('<tr><td colspan="8" style="text-align: center; padding: 4rem; color: #94a3b8;"><i data-lucide="inbox" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.2;"></i><br>Tidak ada data ditemukan.</td></tr>');
            if (window.lucide) lucide.createIcons();
            return;
        }

        data.forEach(item => {
            const isSelected = item.is_selected == 1;
            const rowClass = isSelected ? 'row-selected' : '';
            const btnClass = isSelected ? 'active' : '';
            const btnText = isSelected ? 'Selected' : 'Select';
            const btnIcon = isSelected ? 'check-circle' : 'circle';
            
            const badge = isSelected ? `<div style="margin-top: 4px;"><span class="badge-selected"><i data-lucide="check" style="width:10px;height:10px;"></i> Terpilih</span></div>` : '';
            const numUrut = (isSelected && item.nomor_urut) ? `<span class="num-urut-premium">#${item.nomor_urut}</span>` : '';

            const row = `
                <tr class="${rowClass}" id="row-${item.id}">
                    <td>
                        <button class="btn-select-premium ${btnClass}" data-id="${item.id}">
                            <i data-lucide="${btnIcon}" style="width: 14px; height: 14px;"></i>
                            ${btnText}
                        </button>
                    </td>
                    <td>
                        <span class="badge-no-paket">${item.no_paket}</span>
                    </td>
                    <td>
                        <div style="font-family: 'Inter', sans-serif; font-weight: 700; color: #1e293b; letter-spacing: -0.01em;">${item.no_pesanan}</div>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 600; color: #0f172a; font-size: 14px;">${item.nama_pemesan}</span>
                            ${badge}
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 13px;">
                            <i data-lucide="calendar" style="width: 14px; height: 14px; opacity: 0.6;"></i>
                            ${formatDate(item.tanggal)}
                        </div>
                    </td>
                    <td>
                        <span class="badge-kode-premium">${item.kode || '-'}</span>
                    </td>
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <span style="font-weight: 700; color: #334155; font-size: 12.5px;">${item.sesi}</span>
                            <span style="font-size: 11px; color: #3b82f6; font-weight: 600;">${item.jam || '-'}</span>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="avatar-xs-premium">${item.petugas ? item.petugas.charAt(0).toUpperCase() : '?'}</div>
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 13px; font-weight: 600; color: #475569;">${item.petugas || '-'}</span>
                                <span style="font-size: 10px; color: #94a3b8; text-transform: uppercase;">Petugas</span>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        if (window.lucide) lucide.createIcons();
    }

    function toggleSelect(id, btn) {
        const row = $(`#row-${id}`);
        
        // Add loading state to button
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<div class="spinner-border spinner-border-sm" role="status"></div>');
        
        $.post(`<?= url('/numbering/') ?>${id}/toggle`, {
            _csrf_token: '<?= csrf_token() ?>'
        }, function(res) {
            btn.prop('disabled', false);
            
            if (res.success) {
                // UI Update
                if (res.is_selected) {
                    btn.addClass('active').html('<i data-lucide="check-circle" style="width: 14px; height: 14px;"></i> Selected');
                    row.addClass('row-selected');
                    
                    // Toast success
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: res.message
                    });
                } else {
                    btn.removeClass('active').html('<i data-lucide="circle" style="width: 14px; height: 14px;"></i> Select');
                    row.removeClass('row-selected');
                }
                
                if (window.lucide) lucide.createIcons();
                
                // Reload data to refresh numbering order & stats
                loadData();
            } else {
                btn.html(originalHtml);
                Swal.fire('Gagal', res.message, 'error');
            }
        }).fail(function() {
            btn.prop('disabled', false).html(originalHtml);
            Swal.fire('Error', 'Gagal menghubungi server.', 'error');
        });
    }

    function updateStats(total) {
        $('#selectedCount').text(total);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('id-ID', options);
    }
});
</script>

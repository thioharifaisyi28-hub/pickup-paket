<div class="page-header">
    <div>
        <h1 class="page-title">Manajemen User</h1>
        <p class="text-muted">Kelola akses admin dan staff</p>
    </div>
    
    <button type="button" class="btn btn-primary" id="btnTambahUser">
        <i data-lucide="plus"></i> Tambah User
    </button>
</div>

<!-- Fitur Pencarian User -->
<div class="card mb-4" style="border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 1.25rem;">
    <form action="<?= url('/user') ?>" method="GET" class="d-flex" style="gap: 10px; margin: 0; align-items: center;">
        <div style="position: relative; flex: 1;">
            <i data-lucide="search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: #94a3b8;"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan username..." value="<?= e($search ?? '') ?>" style="padding-left: 40px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 12.5px; width: 100%; height: 35px; margin: 0;">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 35px; padding: 0 16px; border-radius: 10px;">
            Cari
        </button>
        <?php if (!empty($search)): ?>
        <a href="<?= url('/user') ?>" class="btn btn-outline" style="height: 35px; padding: 0 16px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;">
            Reset
        </a>
        <?php endif; ?>
    </form>
</div>


<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="70" style="text-align: center;">No</th>
                    <th style="text-align: center;">Username</th>
                    <th style="text-align: center;">Role</th>
                    <th style="text-align: center;">Terdaftar Sejak</th>
                    <th width="140" style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:3rem; color:var(--text-muted);">
                        <i data-lucide="users-2" style="width: 48px; height: 48px; opacity: 0.2; margin-bottom: 1rem;"></i>
                        <p>Belum ada data user terdaftar.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $rowNum = 1;
                    foreach ($users as $row): 
                        $username = $row['username'];
                        $firstLetter = strtoupper(substr($username, 0, 1));
                        $isMe = ($row['id'] === auth()['id']);
                    ?>
                    <tr>
                        <td align="center">
                            <span style="font-family: monospace; font-size: 13px; font-weight: 600; color: var(--text-muted);"><?= $rowNum++ ?></span>
                        </td>
                        <td align="center">
                            <div class="d-flex align-items-center justify-content-center">
                                <div class="avatar-xs" style="width:34px; height:34px; background:var(--accent-color); color:white; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; position: relative; z-index: 2; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                    <?= $firstLetter ?>
                                </div>
                                <div style="margin-left: -8px; padding: 6px 16px 6px 18px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0 12px 12px 0; position: relative; z-index: 1;">
                                    <span style="font-weight: 700; font-size: 13px; color: var(--primary-color);">
                                        <?= e(ucfirst($username)) ?>
                                    </span>
                                    <?php if ($isMe): ?>
                                        <div style="font-size: 9px; color: var(--success-color); font-weight: 700; text-transform: uppercase; margin-top: -2px; letter-spacing: 0.5px;">Current User</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td align="center">
                            <span class="badge" style="background: <?= $row['role'] == 'admin' ? '#f0f9ff' : '#f8fafc' ?>; color: <?= $row['role'] == 'admin' ? '#0369a1' : '#64748b' ?>; border: 1px solid <?= $row['role'] == 'admin' ? '#bae6fd' : '#e2e8f0' ?>; border-radius: 8px; padding: 0.5rem 0.8rem; font-weight: 700; font-size: 10px; display: inline-flex; align-items: center; justify-content: center; min-width: 90px; letter-spacing: 0.5px;">
                                <i data-lucide="<?= $row['role'] == 'admin' ? 'shield-check' : 'user' ?>" style="width: 12px; height: 12px; margin-right: 6px;"></i>
                                <?= strtoupper(e($row['role'])) ?>
                            </span>
                        </td>
                        <td align="center">
                            <div style="font-size: 12px; font-family: monospace; font-weight: 500;">
                                <?= formatDateStacked($row['created_at'] ?? 'now', false) ?>
                            </div>
                        </td>
                        <td align="center">
                            <div class="d-flex justify-content-center" style="gap: 0.6rem;">
                                <a href="javascript:void(0)"
                                   class="btn-action-sm btn-edit-user"
                                   data-url="<?= url('/user/' . $row['id'] . '/edit') ?>"
                                   title="Edit User"
                                   style="background: #3b82f6; color: white; width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none;">
                                    <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i>
                                </a>
                                <?php if (!$isMe): ?>
                                <form action="<?= url('/user/' . $row['id'] . '/delete') ?>" method="POST" class="form-delete-user" style="margin:0;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="confirm_password" value="">
                                    <button type="button" class="btn-action-sm btn-hapus-user"
                                        data-username="<?= e($row['username']) ?>"
                                        title="Hapus User"
                                        style="background: #ef4444; color: white; width: 34px; height: 34px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer;">
                                        <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
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

<!-- Modal Tambah User -->
<div class="modal-overlay" id="tambahUserModal">
    <div class="modal-dialog" style="max-width: 450px; overflow: hidden; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); pointer-events: auto;">
        <form id="formTambahUser" action="<?= url('/user/store') ?>" method="POST" style="margin: 0; display: flex; flex-direction: column;">
            <div class="modal-header" style="background: white; border-bottom: 1px solid #f1f5f9; padding: 1.5rem;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                    <div style="background: #e0f2fe; color: #0ea5e9; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="user-plus" style="width: 20px; height: 20px;"></i>
                    </div>
                    Tambah User Baru
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('tambahUserModal')" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; transition: all 0.2s;">
                    <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
            
            <?= csrf_field() ?>
            <input type="hidden" name="confirm_password" id="tambahUserConfirmPassword" value="">
            
            <div class="modal-body" style="padding: 1.5rem; background: #fafafa;">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-weight: 600; color: #475569; margin-bottom: 0.5rem; display: block; font-size: 13.5px;">Username <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="username" class="form-control" required placeholder="cth: staff_polo" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; background: white; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s;">
                </div>
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" style="font-weight: 600; color: #475569; margin-bottom: 0.5rem; display: block; font-size: 13.5px;">Password <span style="color: #ef4444;">*</span></label>
                    <input type="password" name="password" class="form-control" required placeholder="Minimal 6 karakter" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; background: white; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s;">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-weight: 600; color: #475569; margin-bottom: 0.5rem; display: block; font-size: 13.5px;">Role / Hak Akses <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <select name="role" class="form-select" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; background: white; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s; appearance: none; cursor: pointer;">
                            <option value="staff">Staff (Akses terbatas ke paket)</option>
                            <option value="admin">Admin (Akses penuh ke semua data)</option>
                        </select>
                        <i data-lucide="chevron-down" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #64748b; pointer-events: none;"></i>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer" style="padding: 1.25rem 1.5rem; border-top: 1px solid #f1f5f9; background: white; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('tambahUserModal')" style="padding: 0.6rem 1.25rem; font-weight: 600; border-radius: 10px; background: white; border: 1px solid #cbd5e1; color: #475569; font-size: 14px; cursor: pointer; transition: all 0.2s;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.25rem; font-weight: 600; border-radius: 10px; background: #3b82f6; border: none; color: white; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25); transition: all 0.2s;">
                    <i data-lucide="save" style="width: 16px; height: 16px;"></i> Simpan User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// =============================================
// HELPER: Password Gate (konfirmasi polosport)
// =============================================
function requirePassword(onSuccess) {
    Swal.fire({
        title: '<i class="swal-icon-lock">🔒</i> Konfirmasi Keamanan',
        html: '<p style="margin-bottom:8px;color:#475569;font-size:14px;">Fitur ini memerlukan verifikasi password admin.<br>Masukkan password untuk melanjutkan:</p>',
        input: 'password',
        inputPlaceholder: 'Masukkan password admin...',
        inputAttributes: { autocomplete: 'current-password' },
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i data-lucide="unlock"></i> Verifikasi',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'swal-security-popup'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value === 'polosport') {
                onSuccess(result.value);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Akses Ditolak!',
                    text: 'Password salah. Anda tidak memiliki izin untuk melakukan tindakan ini.',
                    confirmButtonColor: '#ef4444'
                });
            }
        }
    });
}

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
    const modal = document.getElementById('tambahUserModal');
    if (e.target === modal) {
        closeModal('tambahUserModal');
    }
});

$(document).ready(function() {

    // =============================================
    // TOMBOL TAMBAH USER — butuh password dulu
    // =============================================
    $('#btnTambahUser').on('click', function() {
        requirePassword(function(password) {
            // Isi hidden input agar controller bisa verifikasi ulang
            $('#tambahUserConfirmPassword').val(password);
            openModal('tambahUserModal');
        });
    });

    // =============================================
    // TOMBOL EDIT USER — butuh password dulu
    // =============================================
    $(document).on('click', '.btn-edit-user', function() {
        const targetUrl = $(this).data('url');
        requirePassword(function(password) {
            // Redirect ke halaman edit dengan password sebagai query param sementara
            // (edit form akan divalidasi di controller)
            window.location.href = targetUrl;
        });
    });

    // =============================================
    // TOMBOL HAPUS USER — butuh password dulu
    // =============================================
    $(document).on('click', '.btn-hapus-user', function() {
        const form = $(this).closest('form');
        const username = $(this).data('username');
        requirePassword(function(password) {
            Swal.fire({
                icon: 'warning',
                title: 'Konfirmasi Hapus User',
                html: `<p>Anda akan menghapus user:</p><p style="font-size:17px;font-weight:700;color:#ef4444;margin:8px 0;">"${username}"</p><p style="font-size:13px;color:#64748b;">Tindakan ini tidak dapat dibatalkan!</p>`,
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Isi confirm_password sebelum submit
                    form.find('input[name="confirm_password"]').val(password);
                    form.submit();
                }
            });
        });
    });

    // =============================================
    // SUBMIT AJAX FORM TAMBAH USER
    // =============================================
    $('#formTambahUser').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Disable button to prevent double submit
        submitBtn.prop('disabled', true).html('<i data-lucide="loader-2" style="animation: spin 1s linear infinite; width: 16px; height: 16px;"></i> Menyimpan...');
        if (window.lucide) window.lucide.createIcons();

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(res) {
                if (res.success) {
                    closeModal('tambahUserModal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message || 'User berhasil ditambahkan.',
                        confirmButtonColor: '#3b82f6',
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    submitBtn.prop('disabled', false).html(originalText);
                    if (window.lucide) window.lucide.createIcons();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: res.message || 'Terjadi kesalahan.',
                        confirmButtonColor: '#ef4444'
                    });
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html(originalText);
                if (window.lucide) window.lucide.createIcons();
                
                let errorMsg = 'Terjadi kesalahan sistem.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: errorMsg,
                    confirmButtonColor: '#ef4444'
                });
            }
        });
    });
});
</script>

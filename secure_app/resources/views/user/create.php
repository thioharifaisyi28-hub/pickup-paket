<div class="page-header">
    <div>
        <h1 class="page-title">Tambah User</h1>
        <p class="text-muted">Buat akun untuk staff atau admin baru</p>
    </div>
    <a href="<?= url('/user') ?>" class="btn btn-outline">
        <i data-lucide="arrow-left"></i> Kembali
    </a>
</div>

<div class="card" style="max-width: 500px;">
    <form action="<?= url('/user/store') ?>" method="POST">
        <?= csrf_field() ?>
        
        <div class="form-group">
            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" id="username" class="form-control" value="<?= old('username') ?>" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="role" class="form-label">Role</label>
            <select name="role" id="role" class="form-select">
                <option value="staff" <?= old('role') == 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= old('role') == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <small class="text-muted mt-1 d-block">Admin memiliki akses hapus dan manajemen user.</small>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> Simpan User
            </button>
            <a href="<?= url('/user') ?>" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>
<?php clearOldInput(); ?>

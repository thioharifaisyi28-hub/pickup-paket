<div class="page-header">
    <div>
        <h1 class="page-title">Edit User</h1>
        <p class="text-muted">Edit credential dan access role</p>
    </div>
    <a href="<?= url('/user') ?>" class="btn btn-outline">
        <i data-lucide="arrow-left"></i> Kembali
    </a>
</div>

<div class="card" style="max-width: 500px;">
    <form action="<?= url('/user/' . $user['id'] . '/update') ?>" method="POST">
        <?= csrf_field() ?>
        
        <div class="form-group">
            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" id="username" class="form-control" value="<?= e($user['username']) ?>" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password Baru (Opsional)</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="Kosongkan jika tidak ingin merubah password">
        </div>

        <?php if ($user['id'] !== auth()['id']): ?>
        <div class="form-group">
            <label for="role" class="form-label">Role</label>
            <select name="role" id="role" class="form-select">
                <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="role" value="<?= e($user['role']) ?>">
            <div class="form-group">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" disabled style="background:#f1f5f9; cursor:not-allowed;">
                <small class="text-muted mt-1 d-block">Anda tidak dapat merubah role akun Anda sendiri.</small>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> Update User
            </button>
            <a href="<?= url('/user') ?>" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

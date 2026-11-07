<?php
/**
 * UserController.php
 */

require_once BASE_PATH . '/app/Services/UserService.php';

class UserController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    /**
     * Halaman daftar user + fitur pencarian
     */
    public function index(): void
    {
        // Ambil keyword pencarian dari URL
        $search = trim($_GET['search'] ?? '');

        // Ambil data user berdasarkan pencarian
        $users = $this->userService->getAllUsers($search);

        // Kirim data ke view
        $this->view('user.index', [
            'title'  => 'Manajemen User',
            'users'  => $users,
            'search' => $search
        ]);
    }

    /**
     * Form tambah user
     */
    public function create(): void
    {
        $this->view('user.create', [
            'title' => 'Tambah User'
        ]);
    }

    /**
     * Simpan user baru
     */
    public function store(): void
    {
        // Validasi CSRF
        if (!$this->validateCsrf()) {
            return;
        }

        // Validasi password konfirmasi admin
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($confirmPassword !== 'polosport') {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Akses ditolak: Password konfirmasi admin tidak valid.'], 403);
                return;
            }
            $this->flash('error', 'Akses ditolak: Password konfirmasi admin tidak valid.');
            $this->redirect('/user');
            return;
        }

        // Simpan user
        $result = $this->userService->createUser($_POST);

        // Jika berhasil
        if ($result['success']) {

            // Jika request AJAX
            if ($this->isAjax()) {

                $this->json([
                    'success' => true,
                    'message' => 'User berhasil ditambahkan.'
                ]);

                return;
            }

            // Flash message biasa
            $this->flash('success', 'User berhasil ditambahkan.');

            $this->redirect('/user');

            return;
        }

        // Jika gagal dan AJAX
        if ($this->isAjax()) {

            $this->json([
                'success' => false,
                'message' => $result['message']
            ], 400);

            return;
        }

        // Jika gagal biasa
        $this->flash('error', $result['message']);

        setOldInput($_POST);

        $this->redirect('/user');
    }

    /**
     * Form edit user
     */
    public function edit(string $id): void
    {
        $user = $this->userService->getUserById((int)$id);

        // Jika user tidak ditemukan
        if (!$user) {
            App::abort(404);
        }

        $this->view('user.edit', [
            'title' => 'Edit User',
            'user'  => $user
        ]);
    }

    /**
     * Update user
     */
    public function update(string $id): void
    {
        // Validasi CSRF
        if (!$this->validateCsrf()) {
            return;
        }

        // Update data
        $result = $this->userService->updateUser((int)$id, $_POST);

        // Jika berhasil
        if ($result['success']) {

            $this->flash('success', 'User berhasil diupdate.');

            $this->redirect('/user');

            return;
        }

        // Jika gagal
        $this->flash('error', $result['message']);

        $this->redirect("/user/{$id}/edit");
    }

    /**
     * Hapus user
     */
    public function delete(string $id): void
    {
        // Validasi CSRF
        if (!$this->validateCsrf()) {
            return;
        }

        // Validasi password konfirmasi admin
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($confirmPassword !== 'polosport') {
            $this->flash('error', 'Akses ditolak: Password konfirmasi admin tidak valid.');
            $this->redirect('/user');
            return;
        }

        // Cegah hapus akun sendiri
        if ((int)$id === auth()['id']) {

            $this->flash('error', 'Tidak bisa menghapus akun sendiri.');

            $this->redirect('/user');

            return;
        }

        // Hapus user
        if ($this->userService->deleteUser((int)$id)) {

            $this->flash('success', 'User berhasil dihapus.');

        } else {

            $this->flash('error', 'Gagal menghapus user.');
        }

        $this->redirect('/user');
    }
}
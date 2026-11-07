<?php
/**
 * AuthController.php
 */

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Tampilkan form login
     */
    public function index(): void
    {
        if (isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.login', ['title' => 'Login - ' . env('APP_NAME')], 'auth');
    }

    /**
     * Proses login
     */
    public function login(): void
    {
        if (!$this->validateCsrf()) {
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->flash('error', 'Username dan password wajib diisi.');
            setOldInput(['username' => $username]);
            $this->redirect('/login');
        }

        $user = $this->userModel->findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            // Login sukses
            $_SESSION['user'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role']
            ];
            $_SESSION['last_activity'] = time();
            $_SESSION['login_success'] = true;
            
            $this->flash('success', 'Selamat datang kembali, ' . e($user['username']) . '!');
            $this->redirect('/dashboard');
        } else {
            // Login gagal
            $this->flash('error', 'Username atau password salah.');
            setOldInput(['username' => $username]);
            $this->redirect('/login');
        }
    }

    /**
     * Proses logout
     */
    public function logout(): void
    {
        session_unset();
        session_destroy();
        session_start();
        
        $this->flash('success', 'Anda telah berhasil logout.');
        $this->redirect('/login');
    }
}

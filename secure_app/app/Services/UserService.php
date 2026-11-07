<?php
/**
 * UserService.php - Service Layer for User logic
 */

class UserService
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Ambil semua user + support search
     */
    public function getAllUsers(string $search = '')
    {
        // Ambil semua user dari model
        $users = $this->userModel->all();

        // Jika tidak ada pencarian
        if (empty($search)) {
            return $users;
        }

        // Filter username
        $filtered = array_filter($users, function ($user) use ($search) {

            return stripos(
                $user['username'],
                $search
            ) !== false;
        });

        // Re-index array
        return array_values($filtered);
    }

    /**
     * Buat user baru
     */
    public function createUser($data)
    {
        // Validasi username
        if ($this->userModel->isUsernameExists($data['username'])) {

            return [
                'success' => false,
                'message' => 'Username sudah digunakan.'
            ];
        }

        // Simpan user
        $id = $this->userModel->create([
            'username' => trim($data['username']),
            'password' => password_hash(
                $data['password'],
                PASSWORD_BCRYPT
            ),
            'role' => $data['role']
        ]);

        // Return hasil
        if ($id) {

            return [
                'success' => true,
                'id' => $id
            ];
        }

        return [
            'success' => false,
            'message' => 'Gagal membuat user.'
        ];
    }

    /**
     * Update user
     */
    public function updateUser($id, $data)
    {
        // Validasi username duplicate
        if ($this->userModel->isUsernameExists(
            $data['username'],
            $id
        )) {

            return [
                'success' => false,
                'message' => 'Username sudah digunakan oleh user lain.'
            ];
        }

        // Data update
        $updateData = [
            'username' => trim($data['username']),
            'role'     => $data['role']
        ];

        // Update password jika diisi
        if (!empty($data['password'])) {

            $updateData['password'] = password_hash(
                $data['password'],
                PASSWORD_BCRYPT
            );
        }

        // Jalankan update
        $success = $this->userModel->update(
            $id,
            $updateData
        );

        // Return hasil
        if ($success) {

            return [
                'success' => true
            ];
        }

        return [
            'success' => false,
            'message' => 'Gagal update user.'
        ];
    }

    /**
     * Hapus user
     */
    public function deleteUser($id)
    {
        return $this->userModel->delete($id);
    }

    /**
     * Ambil user berdasarkan ID
     */
    public function getUserById($id)
    {
        return $this->userModel->find($id);
    }
}
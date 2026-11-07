<?php
/**
 * DashboardController.php
 */

require_once BASE_PATH . '/app/Services/PaketService.php';

class DashboardController extends Controller
{
    private PaketService $paketService;

    public function __construct()
    {
        $this->paketService = new PaketService();
    }

    public function index(): void
    {
        $user = auth();
        $is_admin = isAdmin();
        
        // Let staff see all data too
        $userId = 0;
        $tanggal = date('Y-m-d');

        $data = [
            'title' => 'Dashboard',
            'stats' => $this->paketService->getStats($userId, $tanggal),
            'recent_paket' => $this->paketService->getRecentPackages($userId, 5, $tanggal),
            'chart_data' => $this->paketService->getChartData($userId)
        ];

        $this->view('dashboard.index', $data);
    }
}

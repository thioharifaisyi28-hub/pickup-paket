<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Aplikasi Pickup Paket') ?></title>
    
    <!-- Custom CSS with cache busting -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('css/expedition.css') ?>?v=<?= time() ?>">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- AJAX polyfill or simple script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Animate.css for smooth transitions -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Global Compact UI/UX Scale (80% Proportional Density) -->
    <link rel="stylesheet" href="<?= asset('css/compact-scale.css') ?>?v=<?= time() ?>">

    <!-- Anti-Flicker Sidebar State Script (Must be in head) -->
    <script>
        (function() {
            const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            if (isCollapsed && window.innerWidth > 768) {
                document.documentElement.classList.add('sidebar-is-collapsed');
                // We add a class to html/body so we can use it in CSS immediately
            }
        })();
    </script>
</head>
<body data-url="<?= url() ?>" class="preload">
    
    <?php
    $baseUrl = url();
    $currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    // Helper untuk menu active
    $isActive = function($path) use ($baseUrl, $currentUri) {
        $fullPath = rtrim(parse_url($baseUrl . $path, PHP_URL_PATH), '/');
        $currentUriClean = rtrim($currentUri, '/');
        if ($fullPath === '' || $fullPath === '/pickup_paket/public') {
             // Handle root dashboard specifically
             return ($currentUriClean === $fullPath || $currentUriClean === $fullPath . '/') ? 'active' : '';
        }
        return str_contains($currentUriClean, $fullPath) ? 'active' : '';
    };
    ?>

    <?php
    $sidebarCollapsed = ($_COOKIE['sidebar_collapsed'] ?? 'false') === 'true';
    ?>

    <div class="app-container">
        <!-- Sidebar Backdrop Overlay (Mobile Off-Canvas) -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Sidebar -->
        <aside class="sidebar <?= $sidebarCollapsed ? 'collapsed' : '' ?>" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="<?= asset('images/polologin.png') ?>" alt="Polo Logo" class="sidebar-logo">
                </div>
                <button id="mobileCloseSidebar" class="mobile-only" type="button" aria-label="Tutup Menu">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <nav class="sidebar-menu">
                <a href="<?= url('/profil-mitra') ?>" class="menu-item <?= $isActive('/profil-mitra') ?>">
                    <i data-lucide="building-2"></i> <span>PROFIL MITRA</span>
                </a>

                <div class="menu-label">Main Menu</div>
                <a href="<?= url('/dashboard') ?>" class="menu-item <?= $isActive('/dashboard') ?>">
                    <i data-lucide="layout-dashboard"></i> <span>DASHBOARD</span>
                </a>
                <a href="<?= url('/paket') ?>" class="menu-item <?= $isActive('/paket') ?>">
                    <i data-lucide="box"></i> <span>INSTAN</span>
                </a>
                <a href="<?= url('/numbering') ?>" class="menu-item <?= $isActive('/numbering') ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>
                    <span>NUMBERING PAKET</span>
                </a>

                <div class="menu-label section-label">LOGISTIK</div>
                <a href="<?= url('/ekspedisi/spx') ?>" class="menu-item <?= str_contains($currentUri, '/ekspedisi') ? 'active' : '' ?>">
                    <i data-lucide="truck"></i> <span>EKSPEDISI</span>
                </a>
                <a href="<?= url('/hasil-ekspedisi') ?>" class="menu-item <?= $isActive('/hasil-ekspedisi') ?>">
                    <i data-lucide="file-spreadsheet"></i> <span>HASIL EKSPEDISI</span>
                </a>
                

                <?php if (isAdmin()): ?>
                <div class="menu-label section-label">Administrator</div>
                <a href="<?= url('/user') ?>" class="menu-item <?= $isActive('/user') ?>">
                    <i data-lucide="users"></i> <span>Manajemen User</span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-copyright">
                &copy; 2026 TIM KP PRODI TI | UNPAM.<br>All Rights Reserved.
            </div>
        </aside>

        <!-- Main Wrapper -->
        <main class="main-wrapper <?= $sidebarCollapsed ? 'collapsed' : '' ?>" id="mainWrapper">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i data-lucide="menu"></i>
                    </button>
                </div>

                <div class="topbar-right">
                    <div class="topbar-actions">
                        <button class="action-btn" title="Notifikasi">
                            <i data-lucide="bell"></i>
                            <span class="badge-dot"></span>
                        </button>
                    </div>
                    
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-trigger">
                            <span class="welcome-text">Hi, <?= e(auth()['username']) ?></span>
                            <div class="avatar-small">
                                <?= strtoupper(substr(auth()['username'], 0, 1)) ?>
                            </div>
                            <i data-lucide="chevron-down"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="<?= url('/logout') ?>" class="dropdown-item logout-item delete-confirm" data-title="Logout" data-text="Apakah Anda yakin ingin keluar?"><i data-lucide="log-out"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="content">
                <?php
                if (isset($content)) {
                    echo $content;
                }
                ?>
            </div>
        </main>
    </div>


    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="<?= asset('js/app.js') ?>"></script>
    
    <script>
        // Init Lucide Icons
        lucide.createIcons();

        // User Dropdown toggle
        $(document).on('click', '#userDropdown', function(e) {
            $(this).toggleClass('active');
            e.stopPropagation();
        });

        $(document).on('click', function() {
            $('#userDropdown').removeClass('active');
        });

        // Flash Messages handler via SweetAlert with Branding
        <?php if ($flash = flash()): ?>
            <?php if ($flash['type'] === 'success'): ?>
                Swal.fire({
                    html: `
                        <div style="display: flex; align-items: center; gap: 15px; padding: 5px;">
                            <img src="<?= asset('images/polologin.png') ?>" style="height: 35px; width: auto; object-fit: contain;">
                            <div style="text-align: left;">
                                <div style="font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 2px;">Berhasil!</div>
                                <div style="font-size: 13px; color: #64748b;"><?= addslashes($flash['message']) ?></div>
                            </div>
                        </div>
                    `,
                    timer: 4000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                    background: '#ffffff',
                    showClass: {
                        popup: 'animate__animated animate__fadeInRight'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutRight'
                    },
                    customClass: {
                        popup: 'branded-toast'
                    }
                });
            <?php else: ?>
                Swal.fire({
                    icon: '<?= $flash['type'] === 'error' ? 'error' : 'warning' ?>',
                    title: '<?= $flash['type'] === 'error' ? 'Gagal' : 'Peringatan' ?>',
                    text: '<?= addslashes($flash['message']) ?>',
                    timer: 4000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            <?php endif; ?>
        <?php endif; ?>

        // =============================================
        // GLOBAL LOADING SYSTEM - Semua Tindakan
        // =============================================

        // Keyword URL yang menghasilkan download file (halaman tidak berpindah)
        const downloadUrlKeywords = ['export', 'download', 'template'];

        function isDownloadUrl(href) {
            if (!href) return false;
            const lower = href.toLowerCase();
            return downloadUrlKeywords.some(k => lower.includes(k));
        }

        /**
         * Tampilkan loading global.
         * @param {boolean} autoClose - Jika true, loading otomatis ditutup setelah 5 detik
         *                              (dipakai untuk download/export agar tidak stuck)
         */
        function showGlobalLoading(autoClose = false) {
            Swal.fire({
                title: '',
                html: `
                    <div style="padding: 20px 10px;">
                        <div class="global-bouncing-dots" style="margin-bottom: 30px;">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                        <div style="margin-top: 15px;">
                            <div style="font-weight: 700; font-size: 18px; color: #1e293b; margin-bottom: 8px; animation: pulse-text 1.5s ease-in-out infinite;">Memproses...</div>
                            <div style="font-size: 13.5px; color: #64748b;">Sistem sedang bekerja, mohon tunggu sebentar.</div>
                        </div>
                    </div>
                `,
                allowOutsideClick: false,
                showConfirmButton: false,
                customClass: {
                    popup: 'loading-swal-popup'
                }
            });

            // Khusus untuk download/export: halaman tidak berpindah,
            // jadi tutup otomatis setelah 5 detik.
            // Navigasi biasa: halaman reload sendiri → Swal hilang natural.
            if (autoClose) {
                setTimeout(() => {
                    if (Swal.isVisible()) {
                        Swal.close();
                    }
                }, 5000);
            }
        }

        // Loading saat submit form (hanya POST — bukan GET/search)
        $(document).on('submit', 'form', function(e) {
            const $form = $(this);
            if ($form.hasClass('no-loading')) return;
            if ($form.hasClass('ajax-form')) return;

            const action = $form.attr('action') || '';
            const method = ($form.attr('method') || 'get').toLowerCase();

            // Form GET (pencarian/filter) tidak perlu loading overlay
            // agar pengalaman pencarian tetap responsif dan tidak terganggu
            if (method === 'get' && !isDownloadUrl(action)) return;

            // Form POST (import, aksi, simpan) atau URL download → tampilkan loading
            showGlobalLoading(isDownloadUrl(action));
        });

        // Loading saat klik link navigasi
        $(document).on('click', 'a[href]', function(e) {
            const $el = $(this);
            const href = $el.attr('href');

            if (!href || href === '#' || href.startsWith('javascript') || href.startsWith('mailto')) return;
            if ($el.hasClass('no-loading') || $el.hasClass('delete-confirm') || $el.hasClass('delete-confirm-ajax') || $el.attr('data-ajax') === 'true') return;
            if ($el.attr('target') === '_blank') return;
            if ($el.attr('onclick') && ($el.attr('onclick').includes('openModal') || $el.attr('onclick').includes('closeModal'))) return;

            // Cek link external
            try {
                const urlObj = new URL(href, window.location.href);
                if (urlObj.hostname !== window.location.hostname) return;
            } catch (err) { return; }

            // Tampilkan loading — auto-close jika URL adalah download
            showGlobalLoading(isDownloadUrl(href));
        });


        // Setup Alert Konfirmasi Global (Delete / Logout)
        $(document).on('click', '.delete-confirm', function(e) {
            e.preventDefault();
            const clickedElement = $(this);
            const href = clickedElement.attr('href') || clickedElement.closest('form').attr('action');
            const title = clickedElement.data('title') || 'Apakah Anda Yakin?';
            const text = clickedElement.data('text') || 'Data yang dihapus tidak dapat dikembalikan!';
            const isForm = clickedElement.closest('form').length > 0;
            const form = clickedElement.closest('form');
            const isLogout = clickedElement.hasClass('logout-item');

            const confirmColor = clickedElement.data('confirm-color') || (clickedElement.hasClass('btn-action-ambil') || clickedElement.hasClass('btn-success') ? '#10B981' : (clickedElement.hasClass('btn-action-reset') ? '#3B82F6' : '#EF4444'));
            
            let alertConfig = {
                title: title,
                text: text,
                icon: (clickedElement.hasClass('btn-action-ambil') || clickedElement.hasClass('btn-success')) ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#64748B',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            };

            // Jika logout, ganti dengan logo dan HTML custom
            if (isLogout) {
                alertConfig = {
                    title: '',
                    html: `
                        <div style="text-align: center; margin-bottom: 25px; margin-top: 10px;">
                            <img src="<?= asset('images/polologin.png') ?>" alt="Polo Logo" style="width: 120px; max-height: 60px; object-fit: contain; height: auto; opacity: 0.9; display: block; margin: 0 auto;">
                        </div>
                        <p style="color: #334155; margin-bottom: 8px; font-size: 16px;"><strong>Apakah Anda yakin ingin keluar?</strong></p>
                        <p style="font-size: 13px; color: #64748B; margin: 0;">Anda akan logout dari sistem PT. POLO SPORT INDONESIA</p>
                    `,
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#64748B',
                    confirmButtonText: 'Ya, Logout',
                    cancelButtonText: 'Batal',
                    width: '420px',
                    customClass: {
                        popup: 'swal-logout-popup',
                        confirmButton: 'swal-logout-button'
                    }
                };
            }

            Swal.fire(alertConfig).then((result) => {
                if (result.isConfirmed) {
                    if (isForm) {
                        // Jika yang diklik adalah button submit, tambahkan field hidden untuk value-nya
                        if (clickedElement.is('button') && clickedElement.attr('name')) {
                            $('<input>').attr({
                                type: 'hidden',
                                name: clickedElement.attr('name'),
                                value: clickedElement.attr('value')
                            }).appendTo(form);
                        }
                        form.submit();
                    } else if(href) {
                        window.location.href = href;
                    }
                }
            });
        });

        // Handle AJAX delete confirmation
        $(document).on('click', '.delete-confirm-ajax', function(e) {
            e.preventDefault();
            
            const clickedElement = $(this);
            const form = clickedElement.closest('form');
            const title = clickedElement.data('title') || 'Konfirmasi Hapus';
            const text = clickedElement.data('text') || 'Data akan dihapus dan tidak dapat dikembalikan!';
            const formAction = form.attr('action');

            let alertConfig = {
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC2626',
                cancelButtonColor: '#64748B',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            };

            Swal.fire(alertConfig).then((result) => {
                if (result.isConfirmed) {
                    // Get CSRF token
                    const csrfToken = form.find('input[name="_csrf_token"]').val();
                    console.log('Delete Action - Form Action:', formAction);
                    console.log('Delete Action - CSRF Token:', csrfToken);
                    
                    // Send AJAX delete request
                    $.ajax({
                        url: formAction,
                        type: 'POST',
                        data: {
                            _csrf_token: csrfToken
                        },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Delete Success Response:', response);
                            if (response.success) {
                                // Show success message
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message || 'Data berhasil dihapus',
                                    timer: 2000,
                                    timerProgressBar: true,
                                    confirmButtonColor: '#10B981'
                                }).then(() => {
                                    // Remove row from table
                                    clickedElement.closest('tr').fadeOut(350, function() {
                                        $(this).remove();
                                        
                                        // Check if table is empty
                                        const tableBody = $('table tbody');
                                        if (tableBody.find('tr').length === 0) {
                                            location.reload();
                                        }
                                    });
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal!',
                                    text: response.message || 'Gagal menghapus data',
                                    confirmButtonColor: '#EF4444'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Delete Error Status:', xhr.status);
                            console.log('Delete Error Response:', xhr.responseText);
                            console.log('Delete Error Text:', error);
                            
                            let errorMessage = 'Terjadi kesalahan saat menghapus data';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: errorMessage,
                                confirmButtonColor: '#EF4444'
                            });
                        }
                    });
                }
            });
        });
    </script>

    <style>
        .swal-logout-popup {
            border-radius: 20px !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.8) !important;
        }

        .swal-logout-button {
            border-radius: 8px !important;
            padding: 10px 30px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
        }

        /* Branded Success Toast */
        .branded-toast {
            border-radius: 12px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
            border-left: 4px solid #10b981 !important;
            padding: 10px !important;
        }

        /* Professional Loading Swal */
        .loading-swal-popup {
            border-radius: 24px !important;
            padding: 2.5rem !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15) !important;
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
        }

        /* Animasi Global Bouncing Dots Keren & Smooth */
        .global-bouncing-dots {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }
        .global-bouncing-dots div {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #3b82f6, #0ea5e9);
            border-radius: 50%;
            animation: bounce-dot-global 0.6s alternate infinite cubic-bezier(0.6, 0.05, 0.15, 0.95);
            box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4);
        }
        .global-bouncing-dots div:nth-child(1) { animation-delay: 0s; }
        .global-bouncing-dots div:nth-child(2) { animation-delay: 0.15s; background: linear-gradient(135deg, #0ea5e9, #38bdf8); }
        .global-bouncing-dots div:nth-child(3) { animation-delay: 0.3s; background: linear-gradient(135deg, #38bdf8, #7dd3fc); }

        @keyframes bounce-dot-global {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(-20px) scale(1.15); }
        }
        @keyframes pulse-text {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(0.98); }
        }
        /* Sidebar Profil Mitra Card */
        .sidebar-profil-mitra:hover {
            background: linear-gradient(145deg, rgba(59,130,246,0.12) 0%, rgba(59,130,246,0.05) 100%) !important;
            border-color: rgba(59,130,246,0.25) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
        }

        .sidebar-profil-mitra.active {
            background: linear-gradient(145deg, rgba(59,130,246,0.15) 0%, rgba(59,130,246,0.08) 100%) !important;
            border-color: rgba(59,130,246,0.3) !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }

        /* Sidebar Copyright */
        .sidebar-copyright {
            padding: 0.65rem 0.65rem;
            text-align: center;
            font-size: 9.5px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            line-height: 1.6;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .sidebar.collapsed .sidebar-copyright {
            display: none;
        }

        /* ===================================================
           OFF-CANVAS MOBILE SIDEBAR & BACKDROP
           =================================================== */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1040;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (max-width: 768px) {
            .sidebar-backdrop {
                display: block;
            }

            .sidebar-backdrop.show {
                opacity: 1;
                pointer-events: auto;
            }

            body.sidebar-open {
                overflow: hidden !important;
            }

            /* Off-Canvas Mobile Sidebar */
            .sidebar {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                bottom: 0 !important;
                height: 100vh !important;
                height: 100dvh !important;
                width: 280px !important;
                max-width: 82vw !important;
                z-index: 1050 !important;
                transform: translateX(-100%) !important;
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.35) !important;
                display: flex !important;
                flex-direction: column !important;
            }

            .sidebar.show {
                transform: translateX(0) !important;
            }

            /* Reset collapsed state on mobile so items are always full-width */
            .sidebar.collapsed {
                width: 280px !important;
                max-width: 82vw !important;
            }

            .sidebar.collapsed .sidebar-header {
                padding: 0 1.25rem !important;
                justify-content: space-between !important;
            }

            .sidebar.collapsed .sidebar-logo {
                max-width: 130px !important;
                max-height: 40px !important;
                object-fit: contain !important;
            }

            .sidebar.collapsed .menu-label {
                opacity: 1 !important;
                height: auto !important;
                margin: 1.25rem 0 0.5rem 0 !important;
                padding: 0 1.25rem !important;
                pointer-events: auto !important;
            }

            .sidebar.collapsed .menu-item {
                justify-content: flex-start !important;
                padding: 0.75rem 1rem !important;
                margin: 4px 12px !important;
                gap: 0.75rem !important;
            }

            .sidebar.collapsed .menu-item span {
                display: inline !important;
            }

            .sidebar.collapsed .sidebar-copyright {
                display: block !important;
            }

            /* Sidebar Header Mobile */
            .sidebar-header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 0 1.25rem !important;
                height: 70px !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            }

            .sidebar-header .logo {
                justify-content: flex-start !important;
                width: auto !important;
            }

            /* Mobile Close Sidebar Button */
            #mobileCloseSidebar {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.08);
                border: 1px solid rgba(255, 255, 255, 0.15);
                color: #ffffff;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s ease;
                padding: 0;
            }

            #mobileCloseSidebar:hover,
            #mobileCloseSidebar:active {
                background: rgba(255, 255, 255, 0.2);
                color: #ffffff;
            }

            /* Content & Topbar Mobile Spacing */
            .main-wrapper,
            .main-wrapper.collapsed {
                margin-left: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .content {
                padding: 1rem 0.75rem !important;
            }

            .topbar {
                padding: 0 1rem !important;
            }

            .topbar-left, 
            .topbar-right {
                gap: 0.75rem !important;
            }
        }

        @media (max-width: 480px) {
            .welcome-text {
                display: none;
            }
        }
    </style>
</body>
</html>

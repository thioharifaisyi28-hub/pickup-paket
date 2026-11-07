/**
 * app.js - Client side logic for Pickup Paket
 */

document.addEventListener('DOMContentLoaded', function() {
    // Remove preload class after a short delay to allow the browser to finish rendering
    // the initial state without transitions
    setTimeout(() => {
        document.body.classList.remove('preload');
    }, 100);

    // Detail Modal Functionality
    const detailButtons = document.querySelectorAll('.view-detail');
    detailButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const id = this.getAttribute('data-id');
            const baseUrl = document.body.getAttribute('data-url');
            
            fetch(`${baseUrl}/paket/${id}`)
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        showPacketDetail(res.data);
                    }
                })
                .catch(err => console.error('Error fetching detail:', err));
        });
    });

    function showPacketDetail(data) {
        let statusClass = '';
        if (data.status === 'pending') statusClass = 'badge-warning';
        else if (data.status === 'diproses') statusClass = 'badge-info';
        else if (data.status === 'selesai') statusClass = 'badge-success';

        Swal.fire({
            title: `Detail Paket #${data.no_paket}`,
            html: `
                <div style="text-align: left; font-size: 14px;">
                    <p><strong>Penerima:</strong> ${data.nama_penerima}</p>
                    <p><strong>No. HP:</strong> ${data.no_hp}</p>
                    <p><strong>Alamat:</strong> ${data.alamat}</p>
                    <p><strong>Status:</strong> <span class="badge ${statusClass}">${data.status.toUpperCase()}</span></p>
                    <hr style="margin: 10px 0; border: 0; border-top: 1px solid #eee;">
                    <p><strong>Tgl Masuk:</strong> ${data.tanggal_masuk}</p>
                    <p><strong>Terakhir Update:</strong> ${data.tanggal_update}</p>
                </div>
            `,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#3B82F6'
        });
    }

    /**
     * Sidebar Toggle Logic with Cookie Persistence & Mobile Off-Canvas Backdrop
     */
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');
    const mobileCloseBtn = document.getElementById('mobileCloseSidebar');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    function openMobileSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
        document.body.classList.add('sidebar-open');
    }

    function closeMobileSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }

    function setCookie(name, value, days = 30) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "; expires=" + date.toUTCString();
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth <= 768) {
                // Mobile: toggle off-canvas visibility with backdrop
                if (sidebar && sidebar.classList.contains('show')) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            } else {
                // Desktop: toggle collapse
                if (sidebar) sidebar.classList.toggle('collapsed');
                if (mainWrapper) mainWrapper.classList.toggle('collapsed');
                
                // Sync with the anti-flicker class if it exists or needs to be set
                const isCollapsed = sidebar && sidebar.classList.contains('collapsed');
                if (isCollapsed) {
                    document.documentElement.classList.add('sidebar-is-collapsed');
                } else {
                    document.documentElement.classList.remove('sidebar-is-collapsed');
                }
                
                // Save state to both localStorage and Cookie (Cookie for PHP server-side rendering)
                localStorage.setItem('sidebar_collapsed', isCollapsed);
                setCookie('sidebar_collapsed', isCollapsed);
            }
            
            // Refresh icons
            setTimeout(() => {
                if (window.lucide) lucide.createIcons();
            }, 300);
        });
    }

    // Mobile Close Button
    if (mobileCloseBtn) {
        mobileCloseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            closeMobileSidebar();
        });
    }

    // Click on Backdrop to close mobile sidebar
    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', function(e) {
            e.stopPropagation();
            closeMobileSidebar();
        });
    }

    // Close mobile sidebar on click outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && 
            sidebar &&
            !sidebar.contains(e.target) && 
            toggleBtn &&
            !toggleBtn.contains(e.target) && 
            sidebar.classList.contains('show')) {
            closeMobileSidebar();
        }
    });

    // Close mobile sidebar when viewport resized above mobile breakpoint
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('show')) {
            closeMobileSidebar();
        }
    });


    // Global Search Input (Handled individually by modules for AJAX or standard submit)
});



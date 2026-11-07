<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body style="background-color: var(--bg-main); display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 2rem;">
    <div style="max-width: 500px;">
        <div style="color: var(--accent-color); margin-bottom: 2rem;">
            <i data-lucide="search-x" style="width: 100px; height: 100px;"></i>
        </div>
        <h1 style="font-size: 4rem; font-weight: 700; color: var(--primary-color); margin-bottom: 1rem;">404</h1>
        <h2 style="font-size: 1.5rem; font-weight: 600; color: var(--text-main); margin-bottom: 1.5rem;">Halaman Tidak Ditemukan</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>
        <a href="<?= url('/dashboard') ?>" class="btn btn-primary">
            <i data-lucide="home"></i> Kembali ke Dashboard
        </a>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>

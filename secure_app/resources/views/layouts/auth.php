<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Login') ?></title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
</head>
<body>
    
    <?php
    if (isset($content)) {
        echo $content;
    }
    ?>

    <style>
        .swal-auth-popup {
            background: rgba(30, 41, 59, 0.5) !important;
            backdrop-filter: blur(25px) !important;
            -webkit-backdrop-filter: blur(25px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 20px !important;
            color: #f8fafc !important;
            padding: 1.5rem !important;
            width: 340px !important;
        }
        .swal-auth-title {
            color: #f8fafc !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-top: 0.8rem !important;
        }
        .swal-auth-text {
            color: #cbd5e1 !important;
            font-size: 13px !important;
            margin-top: 0.5rem !important;
        }
        .swal-auth-image {
            filter: brightness(0) invert(1) !important;
            margin-bottom: 0 !important;
            opacity: 0.9;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script>
        <?php if ($flash = flash()): ?>
            Swal.fire({
                imageUrl: '<?= asset('images/polologin.png') ?>',
                imageWidth: 100,
                imageAlt: 'Logo',
                title: '<?= ucfirst($flash['type'] === 'error' ? 'Gagal' : 'Berhasil') ?>',
                text: '<?= addslashes($flash['message']) ?>',
                timer: 4000,
                showConfirmButton: false,
                position: 'center',
                background: 'transparent',
                customClass: {
                    popup: 'swal-auth-popup',
                    title: 'swal-auth-title',
                    htmlContainer: 'swal-auth-text',
                    image: 'swal-auth-image'
                },
                showClass: {
                    popup: 'animate__animated animate__fadeIn animate__faster'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOut animate__faster'
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>

<style>
    :root {
        --login-bg: #cbd5e1;
        --card-bg: rgba(30, 41, 59, 0.2);
        --accent-primary: #3b82f6;
        --text-light: #f8fafc;
        --text-muted: #cbd5e1;
        --input-bg: rgba(255, 255, 255, 0.05);
        --border-light: rgba(255, 255, 255, 0.1);
    }

    #login-wrapper {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--login-bg) !important;
        background-image: url('<?= asset('images/polologin.png') ?>') !important;
        background-size: 80% auto !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        overflow: hidden;
        padding: 2rem;
        font-family: 'Inter', sans-serif;
    }

    .split-card {
        background: var(--card-bg);
        backdrop-filter: blur(40px);
        -webkit-backdrop-filter: blur(40px);
        width: 100%;
        max-width: 680px;
        min-height: 400px;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: row;
        overflow: hidden;
        animation: cardAppear 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 10;
        position: relative;
        border: 1px solid var(--border-light);
    }

    @keyframes cardAppear {
        from { opacity: 0; transform: scale(0.98); }
        to { opacity: 1; transform: scale(1); }
    }

    .split-image {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        border-right: 1px solid var(--border-light);
    }

    .slide-container {
        position: absolute;
        inset: 0;
        z-index: 1;
    }

    .slide {
        position: absolute;
        inset: 0;
        background-position: center center;
        background-repeat: no-repeat;
        opacity: 0;
        transition: opacity 1s ease-in-out;
    }

    .slide-1 {
        background-image: url('<?= asset('images/kp.jpeg') ?>');
        background-size: cover;
        background-position: center;
    }

    .slide-2 {
        background-image: url('<?= asset('images/logounpam.png') ?>');
        background-size: contain;
        background-origin: content-box;
        padding: 2.5rem;
    }

    .slide-3 {
        background-image: url('<?= asset('images/polologin.png') ?>');
        background-size: contain;
        background-origin: content-box;
        padding: 3rem;
    }

    .slide.active {
        opacity: 1;
    }

    .image-overlay {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.1);
        z-index: 2;
        pointer-events: none;
    }

    .split-form {
        flex: 1.3;
        padding: 2.5rem 2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: transparent;
        z-index: 3;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 1.8rem;
    }

    .logo-img {
        width: 100px;
        height: auto;
        margin-bottom: 0.8rem;
        filter: brightness(0) invert(1);
    }

    .welcome-text h1 {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .form-group {
        margin-bottom: 1.2rem;
    }

    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-light);
        margin-bottom: 0.5rem;
    }

    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-icon {
        position: absolute;
        left: 16px;
        color: var(--text-muted);
        width: 18px;
        height: 18px;
        pointer-events: none;
        z-index: 2;
    }

    .form-control {
        width: 100%;
        height: 46px;
        padding: 0 46px;
        background: var(--input-bg) !important;
        border: 1px solid var(--border-light) !important;
        border-radius: 10px !important;
        font-size: 14px;
        color: white !important;
        transition: all 0.3s ease;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.3) !important;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.12) !important;
        border-color: var(--accent-primary) !important;
        outline: none;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .toggle-password {
        position: absolute;
        right: 0;
        height: 100%;
        width: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        cursor: pointer;
        background: transparent;
        border: none;
        padding: 0;
        z-index: 2;
        transition: color 0.3s ease;
    }

    .toggle-password:hover {
        color: var(--text-light);
    }

    .toggle-password .eye-icon {
        width: 18px;
        height: 18px;
    }

    .btn-submit {
        width: 100%;
        height: 42px;
        background: #3b82f6 !important;
        color: white !important;
        border: none;
        border-radius: 10px !important;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 1rem;
    }

    .btn-submit:hover {
        background: #2563eb !important;
        transform: translateY(-1px);
    }

    .copyright-text {
        text-align: center;
        font-size: 11px;
        color: rgba(255, 255, 255, 0.5);
        margin-top: 1.5rem;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    @media (max-width: 768px) {
        .split-card {
            max-width: 400px;
            flex-direction: column;
        }
        .split-image {
            display: none;
        }
    }
</style>

<div id="login-wrapper">
    <div class="split-card">
        <!-- Left Side -->
        <div class="split-image">
            <div class="slide-container">
                <div class="slide slide-1 active"></div>
                <div class="slide slide-2"></div>
                <div class="slide slide-3"></div>
            </div>
            <div class="image-overlay"></div>
        </div>

        <!-- Right Side -->
        <div class="split-form">
            <div class="auth-header">
                <img src="<?= asset('images/polologin.png') ?>" alt="Logo" class="logo-img">
                <div class="welcome-text">
                    <h1>PT. POLO SPORT INDONESIA</h1>
                </div>
            </div>

            <form action="<?= url('/login') ?>" method="POST">
                <?= csrf_field() ?>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" name="username" id="username" class="form-control" placeholder="Username" value="<?= old('username') ?>" required autocomplete="off">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                            <i data-lucide="eye" class="eye-icon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i data-lucide="log-in" style="width: 18px; height: 18px;"></i>
                    <span>Masuk Sekarang</span>
                </button>
            </form>

            <div class="copyright-text">
                &copy; 2026 TIM KP PRODI TI | UNPAM. All Rights Reserved.
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();

    // Toggle Password Visibility
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    togglePassword.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        const iconName = type === 'password' ? 'eye' : 'eye-off';
        this.innerHTML = `<i data-lucide="${iconName}" class="eye-icon"></i>`;
        lucide.createIcons();
    });

    // Slider Logic
    const slides = document.querySelectorAll('.slide');
    let currentSlide = 0;

    function nextSlide() {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }

    setInterval(nextSlide, 5000);
</script>

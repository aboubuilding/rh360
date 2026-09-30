{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion · EXPERT RH 360</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('app/assets/img/favicon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('app/assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/fontawesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/tabler-icons/tabler-icons.min.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />

    <style>
        :root {
            --rh-primary: #1B4965;
            --rh-primary-dark: #0F2E42;
            --rh-primary-deep: #07202F;
            --rh-accent: #D4A94D;
            --rh-accent-soft: #E9CE9B;
            --rh-ink: #1f2d3a;
            --rh-muted: #6f7e8c;
            --rh-icon: #a5895a;
            --rh-danger: #C81E3A;
            --rh-warning: #B8720B;
            --rh-page-bg: #E7E2D4;
            --rh-paper: #FBF8F1;
            --rh-paper-line: #EAE1CC;
            --rh-input-bg: #F7F3E9;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body.account-page {
            font-family: 'Kumbh Sans', sans-serif;
            min-height: 100vh; height: 100vh;
            background: var(--rh-page-bg);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        #global-loader {
            position: fixed; inset: 0; z-index: 99999;
            background: #ffffff; display: flex; align-items: center; justify-content: center;
            transition: opacity .6s ease, visibility .6s ease;
        }
        #global-loader.hidden { opacity: 0; visibility: hidden; pointer-events: none; }

        .school-wrapper {
            position: relative; z-index: 1; width: 100%; height: 100vh;
            display: flex; align-items: stretch; overflow: hidden; background: #ffffff;
        }
        .school-brand-panel {
            flex: 0 0 45%; height: 100vh;
            background: radial-gradient(130% 150% at 12% 8%, #1d5a7d 0%, var(--rh-primary-dark) 48%, var(--rh-primary-deep) 100%);
            padding: 0; color: #fff;
            display: flex; flex-direction: column; justify-content: center;
            position: relative; overflow: hidden;
        }
        .school-frame {
            position: absolute; inset: 30px;
            border: 1px solid rgba(212,169,77,.38); pointer-events: none;
        }
        .school-frame::after {
            content: ''; position: absolute; inset: 7px;
            border: 1px solid rgba(212,169,77,.2);
        }
        .school-corner { position: absolute; width: 32px; height: 32px; color: var(--rh-accent); opacity: .75; pointer-events: none; }
        .school-corner svg { width: 100%; height: 100%; display: block; }
        .school-corner.tl { top: 30px; left: 30px; }
        .school-corner.tr { top: 30px; right: 30px; transform: scaleX(-1); }
        .school-corner.bl { bottom: 30px; left: 30px; transform: scaleY(-1); }
        .school-corner.br { bottom: 30px; right: 30px; transform: scale(-1,-1); }
        .school-glow {
            position: absolute; width: 560px; height: 560px; right: -200px; bottom: -180px;
            background: radial-gradient(circle, rgba(212,169,77,.16) 0%, rgba(212,169,77,0) 70%);
            pointer-events: none;
        }
        .school-brand-content { position: relative; z-index: 2; max-width: 440px; margin: 0 auto; padding: 0 60px; }
        .school-brand-logo { display: flex; align-items: center; gap: 16px; margin-bottom: 56px; }
        .school-brand-logo .logo-icon {
            width: 52px; height: 52px; border-radius: 12px;
            background: linear-gradient(145deg, var(--rh-accent-soft), var(--rh-accent));
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: var(--rh-primary-deep);
            box-shadow: 0 6px 20px rgba(212,169,77,.35);
        }
        .school-brand-logo-text {
            font-family: 'Playfair Display', serif;
            font-weight: 700; font-size: 1.15rem; line-height: 1.3;
        }
        .school-brand-logo-text small {
            display: block; font-family: 'Kumbh Sans', sans-serif;
            font-weight: 500; font-size: .66rem; letter-spacing: 1.4px;
            text-transform: uppercase; color: var(--rh-accent-soft); margin-top: 4px;
        }
        .school-brand-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 4vw, 3.1rem); font-weight: 700;
            line-height: 1.08; letter-spacing: -.5px; margin-bottom: 20px;
        }
        .school-brand-desc {
            font-size: .96rem; color: rgba(255,255,255,.78);
            line-height: 1.7; max-width: 380px; margin-bottom: 44px;
        }
        .school-registry {
            list-style: none; padding: 0; display: flex; flex-direction: column;
            border-top: 1px solid rgba(255,255,255,.14);
        }
        .school-registry li {
            padding: 15px 0 15px 18px;
            border-bottom: 1px solid rgba(255,255,255,.14);
            border-left: 2px solid var(--rh-accent);
            font-size: .9rem; font-weight: 500; color: rgba(255,255,255,.92);
        }
        .school-brand-footer { margin-top: 40px; font-size: .74rem; color: rgba(255,255,255,.42); }

        .school-form-panel {
            flex: 1; height: 100vh; background: var(--rh-paper);
            padding: 56px 64px; display: flex; flex-direction: column;
            justify-content: center; overflow-y: auto;
        }
        .school-form-inner { width: 100%; max-width: 400px; margin: 0 auto; }

        .school-back-link {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 28px;
            font-size: .85rem; font-weight: 600; color: var(--rh-primary); text-decoration: none;
        }
        .school-back-link:hover { color: var(--rh-primary-dark); text-decoration: underline; }
        .school-card-head { margin-bottom: 32px; }
        .school-card-head h3 {
            font-family: 'Playfair Display', serif; font-weight: 700;
            font-size: 1.85rem; color: var(--rh-ink); margin-bottom: 8px;
        }
        .school-card-head p { color: var(--rh-muted); font-size: .9rem; margin: 0; }

        .school-alert-banner {
            display: none; align-items: flex-start; gap: 12px;
            padding: 14px 16px; border-radius: 10px; margin-bottom: 22px;
            font-size: .86rem; line-height: 1.5;
            border: 1px solid transparent;
        }
        .school-alert-banner.show { display: flex; }
        .school-alert-banner i { font-size: 1.1rem; margin-top: 1px; flex-shrink: 0; }
        .school-alert-banner .alert-title { font-weight: 700; display: block; margin-bottom: 2px; }
        .school-alert-banner.type-danger { background: #fdecee; border-color: #f6c4cb; color: var(--rh-danger); }
        .school-alert-banner.type-warning { background: #fdf3e2; border-color: #f2dba9; color: var(--rh-warning); }

        .school-label {
            font-weight: 600; font-size: .8rem; color: var(--rh-ink);
            display: flex; align-items: center; gap: 6px; margin-bottom: 7px;
        }
        .school-label i { color: var(--rh-primary); }

        .school-input-group {
            display: flex; align-items: stretch;
            background: var(--rh-input-bg);
            border: 1.5px solid var(--rh-paper-line);
            border-radius: 10px; transition: all .2s ease; overflow: hidden;
        }
        .school-input-group:focus-within {
            border-color: var(--rh-primary);
            box-shadow: 0 0 0 4px rgba(27,73,101,.10);
            background: #ffffff;
        }
        .school-input-group.is-invalid {
            border-color: var(--rh-danger); background: #fdf6f7;
        }
        .school-input-group .form-control {
            border: none; background: transparent; padding: 13px 14px;
            font-size: .92rem; box-shadow: none !important; color: var(--rh-ink);
        }
        .school-input-group .form-control:focus { outline: none; box-shadow: none; }
        .school-input-group .input-group-text {
            border: none; background: transparent; color: var(--rh-icon);
            padding: 0 14px; font-size: 1.05rem; cursor: pointer;
        }
        .invalid-feedback.d-block {
            display: flex; align-items: center; gap: 6px;
            font-size: .78rem; margin-top: 6px; color: var(--rh-danger);
        }

        .school-row-between {
            display: flex; justify-content: space-between; align-items: center;
            margin: 14px 0 26px; font-size: .85rem;
        }
        .school-row-between .form-check-label { color: #4d5e6e; cursor: pointer; user-select: none; }
        .school-row-between .form-check-input:checked {
            background-color: var(--rh-primary); border-color: var(--rh-primary);
        }
        .school-row-between a { color: var(--rh-primary); font-weight: 600; text-decoration: none; }

        .school-btn-submit {
            width: 100%; border: none; border-radius: 10px; padding: 15px;
            font-weight: 700; font-size: .95rem; color: #fff;
            background: linear-gradient(120deg, var(--rh-primary-dark), var(--rh-primary));
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: all .25s ease;
            box-shadow: 0 12px 26px rgba(27,73,101,.26);
            cursor: pointer; position: relative;
        }
        .school-btn-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 16px 32px rgba(27,73,101,.32); }
        .school-btn-submit:active:not(:disabled) { transform: scale(.97); }
        .school-btn-submit:disabled { opacity: .7; cursor: not-allowed; }

        .school-access-note {
            margin-top: 26px; padding-top: 20px;
            border-top: 1px solid var(--rh-paper-line);
            text-align: center; font-size: .83rem; color: var(--rh-muted); line-height: 1.6;
        }
        .school-footer-note { text-align: center; margin-top: 22px; font-size: .75rem; color: #a8a08c; }

        a:focus-visible, button:focus-visible, input:focus-visible, [role="button"]:focus-visible {
            outline: 2px solid var(--rh-primary); outline-offset: 2px;
        }
        #toast-container > .toast-success { background-color: #1e8a4c !important; }
        #toast-container > .toast-error { background-color: var(--rh-danger) !important; }
        #toast-container > .toast-warning { background-color: var(--rh-warning) !important; }

        @media (max-width: 991.98px) {
            body.account-page { height: auto; overflow-y: auto; }
            .school-wrapper { flex-direction: column; height: auto; }
            .school-brand-panel { flex: none; height: auto; min-height: 260px; justify-content: center; padding: 40px 0; }
            .school-frame, .school-corner { display: none; }
            .school-brand-content { max-width: 100%; text-align: center; padding: 0 32px; }
            .school-registry { display: none; }
            .school-brand-desc { max-width: 100%; margin-bottom: 0; }
            .school-brand-logo { justify-content: center; }
            .school-brand-title { font-size: 2rem; margin-bottom: 12px; }
            .school-brand-footer { display: none; }
            .school-form-panel { flex: none; height: auto; padding: 36px 28px; }
        }
        .school-wrapper { animation: fadeInUp .7s ease-out both; }
        @keyframes fadeInUp { 0% { opacity: 0; transform: translateY(14px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        .shake { animation: shake .45s ease; }
        @media (prefers-reduced-motion: reduce) {
            .school-wrapper, .shake, .school-alert-banner { animation: none !important; }
        }
    </style>
</head>
<body class="account-page">

<div id="global-loader">
    <div class="spinner-border text-primary" role="status" style="width:3.5rem;height:3.5rem;">
        <span class="visually-hidden">Chargement…</span>
    </div>
</div>

<div class="school-wrapper">
    <aside class="school-brand-panel">
        <div class="school-frame"></div>
        <div class="school-corner tl"><svg viewBox="0 0 48 48" fill="none"><path d="M4 44 V16 Q4 4 16 4 H44" stroke="currentColor" stroke-width="1.3"/><circle cx="16" cy="4" r="2.2" fill="currentColor"/><circle cx="4" cy="44" r="2.2" fill="currentColor"/></svg></div>
        <div class="school-corner tr"><svg viewBox="0 0 48 48" fill="none"><path d="M4 44 V16 Q4 4 16 4 H44" stroke="currentColor" stroke-width="1.3"/><circle cx="16" cy="4" r="2.2" fill="currentColor"/><circle cx="4" cy="44" r="2.2" fill="currentColor"/></svg></div>
        <div class="school-corner bl"><svg viewBox="0 0 48 48" fill="none"><path d="M4 44 V16 Q4 4 16 4 H44" stroke="currentColor" stroke-width="1.3"/><circle cx="16" cy="4" r="2.2" fill="currentColor"/><circle cx="4" cy="44" r="2.2" fill="currentColor"/></svg></div>
        <div class="school-corner br"><svg viewBox="0 0 48 48" fill="none"><path d="M4 44 V16 Q4 4 16 4 H44" stroke="currentColor" stroke-width="1.3"/><circle cx="16" cy="4" r="2.2" fill="currentColor"/><circle cx="4" cy="44" r="2.2" fill="currentColor"/></svg></div>
        <div class="school-glow"></div>

        <div class="school-brand-content">
            <div class="school-brand-logo">
                <div class="logo-icon"><i class="fas fa-briefcase"></i></div>
                <div class="school-brand-logo-text">
                    EXPERT RH 360
                    <small>Système d'Information RH</small>
                </div>
            </div>

            <h1 class="school-brand-title">Bienvenue.</h1>
            <p class="school-brand-desc">
                Pilotez l'ensemble de vos processus RH : dossiers salariés, contrats, carrière, congés, paie, formation et santé au travail.
            </p>

            <ul class="school-registry">
                <li>Gestion complète des dossiers salariés</li>
                <li>Contrats, carrière et congés centralisés</li>
                <li>Paie, formation et santé au travail</li>
            </ul>

            <div class="school-brand-footer">
                &copy; {{ date('Y') }} EXPERT RH 360 — Tous droits réservés.
            </div>
        </div>
    </aside>

    <div class="school-form-panel">
        <div class="school-form-inner">

            <a href="{{ route('accueil') }}" class="school-back-link">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Retour à l'accueil
            </a>

            <div class="school-card-head">
                <h3>Connexion</h3>
                <p>Saisissez vos identifiants pour accéder à votre espace.</p>
            </div>

            <div id="alert-banner" class="school-alert-banner" role="alert" aria-live="assertive">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <span class="alert-title" id="alert-banner-title"></span>
                    <span id="alert-banner-text"></span>
                </div>
            </div>

            <form id="form-login" autocomplete="off" novalidate>
                @csrf

                <div class="mb-3">
                    <label class="school-label" for="identifiant"><i class="ti ti-user-circle"></i> Identifiant <span class="text-danger">*</span></label>
                    <div class="school-input-group" id="group-identifiant">
                        <span class="input-group-text"><i class="ti ti-user"></i></span>
                        <input type="text" name="identifiant" id="identifiant" class="form-control"
                               placeholder="Entrez votre identifiant" autocomplete="username" required autofocus
                               aria-describedby="error-identifiant">
                    </div>
                    <div class="invalid-feedback d-block" id="error-identifiant" style="display:none;">
                        <i class="fas fa-exclamation-circle"></i> <span>L'identifiant est obligatoire</span>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="school-label" for="mot_passe"><i class="ti ti-lock"></i> Mot de passe <span class="text-danger">*</span></label>
                    <div class="school-input-group" id="group-password">
                        <span class="input-group-text"><i class="ti ti-key"></i></span>
                        <input type="password" name="password" id="mot_passe" class="form-control"
                               placeholder="••••••••" autocomplete="current-password" required
                               aria-describedby="error-motpasse">
                        <span class="input-group-text toggle-password" id="togglePassword" role="button" tabindex="0" aria-label="Afficher le mot de passe">
                            <i class="ti ti-eye-off" id="eye-icon"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback d-block" id="error-motpasse" style="display:none;">
                        <i class="fas fa-exclamation-circle"></i> <span>Le mot de passe est obligatoire</span>
                    </div>
                </div>

                <div class="school-row-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    <a href="#" id="forgotLink">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="school-btn-submit" id="btn-login">
                    <span class="btn-spinner" style="display:none;">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </span>
                    <span class="btn-text"><i class="ti ti-login"></i> Connexion</span>
                </button>

                <p class="school-access-note mb-0">
                    Vous n'avez pas de compte ? Contactez l'administrateur.
                </p>
            </form>

            <div class="school-footer-note">
                &copy; {{ date('Y') }} <strong>EXPERT RH 360</strong> — Tous droits réservés.
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('app/assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('app/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    var LOGIN_ROUTE = "{{ route('login.post') }}";
    var DASHBOARD_ROUTE = "{{ route('dashboard') }}";

    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000",
        "extendedTimeOut": "2000"
    };

    var ERROR_MAP = {
        // Message volontairement identique pour un identifiant inconnu ou un mauvais mot de passe
        INVALID_CREDENTIALS: {
            field: 'password', banner: 'danger',
            title: 'Connexion refusée',
            text: "Identifiant ou mot de passe incorrect."
        },
        ACCOUNT_INACTIVE: {
            sweetalert: {
                icon: 'warning', title: 'Compte désactivé',
                text: "Votre compte a été désactivé. Contactez l'administrateur."
            }
        },
        ERROR: {
            banner: 'danger', title: 'Erreur technique',
            text: "Une erreur technique est survenue."
        }
    };

    $(document).ready(function() {
        setTimeout(function() { $('#global-loader').addClass('hidden'); }, 400);
        bindEvents();
    });

    function bindEvents() {
        $('#form-login').on('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });

        $('#togglePassword').on('click keydown', function(e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
            const input = $('#mot_passe');
            const icon = $('#eye-icon');
            const isHidden = input.attr('type') === 'password';
            input.attr('type', isHidden ? 'text' : 'password');
            icon.toggleClass('ti-eye-off', !isHidden).toggleClass('ti-eye', isHidden);
            $(this).attr('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });

        $('#identifiant, #mot_passe').on('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); handleLogin(); }
        });
        $('#identifiant').on('blur', function() { validateField('identifiant'); });
        $('#mot_passe').on('blur', function() { validateField('mot_passe'); });
        $('#identifiant, #mot_passe').on('input', function() {
            const field = $(this).attr('id');
            const errorId = field === 'identifiant' ? 'error-identifiant' : 'error-motpasse';
            const groupId = field === 'identifiant' ? 'group-identifiant' : 'group-password';
            clearFieldError(errorId, groupId);
            hideBanner();
        });

        $('#forgotLink').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                icon: 'info', title: 'Mot de passe oublié',
                text: "Veuillez contacter l'administrateur pour réinitialiser votre mot de passe.",
                confirmButtonColor: '#1B4965', confirmButtonText: 'Compris'
            });
        });
    }

    function validateField(field) {
        const value = $('#' + field).val().trim();
        const errorId = field === 'identifiant' ? 'error-identifiant' : 'error-motpasse';
        const groupId = field === 'identifiant' ? 'group-identifiant' : 'group-password';
        if (value === '') {
            showFieldError(errorId, groupId, field === 'identifiant'
                ? "L'identifiant est obligatoire" : 'Le mot de passe est obligatoire');
            return false;
        }
        clearFieldError(errorId, groupId);
        return true;
    }
    function showFieldError(errorId, groupId, message) {
        $('#' + errorId + ' span').text(message);
        $('#' + errorId).show();
        $('#' + groupId).addClass('is-invalid');
    }
    function clearFieldError(errorId, groupId) {
        $('#' + errorId).hide();
        $('#' + groupId).removeClass('is-invalid');
    }
    function clearErrors() {
        clearFieldError('error-identifiant', 'group-identifiant');
        clearFieldError('error-motpasse', 'group-password');
        hideBanner();
    }
    function showBanner(type, title, text) {
        const banner = $('#alert-banner');
        banner.removeClass('type-danger type-warning').addClass('type-' + type);
        banner.find('i').attr('class', type === 'warning' ? 'fas fa-triangle-exclamation' : 'fas fa-exclamation-circle');
        $('#alert-banner-title').text(title);
        $('#alert-banner-text').text(text);
        banner.addClass('show');
    }
    function hideBanner() { $('#alert-banner').removeClass('show type-danger type-warning'); }

    function handleLogin() {
        const identifiant = $('#identifiant').val().trim();
        const password = $('#mot_passe').val();
        clearErrors();

        if (!validateField('identifiant') || !validateField('mot_passe')) {
            toastr.warning('Veuillez corriger les champs en surbrillance.');
            return;
        }
        authentifier(identifiant, password);
    }

    function authentifier(identifiant, password) {
        setLoading(true);
        $.ajax({
            dataType: 'json', type: 'POST', url: LOGIN_ROUTE,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: { identifiant: identifiant, password: password, remember: $('#remember').is(':checked') },
            timeout: 10000,
            success: function(data) {
                if (data.success) {
                    toastr.success(data.message || 'Connexion réussie !', 'Connexion');
                    setRedirecting();
                    setTimeout(function() {
                        window.location.href = data.redirect || DASHBOARD_ROUTE;
                    }, 1000);
                } else {
                    setLoading(false);
                    handleBusinessError(data.code, data.message);
                }
            },
            error: function(xhr) { setLoading(false); handleAjaxError(xhr); }
        });
    }

    function handleBusinessError(code, fallbackMessage) {
        const mapping = ERROR_MAP[code];
        if (mapping && mapping.sweetalert) {
            const text = mapping.sweetalert.text || fallbackMessage;
            toastr.error(text, mapping.sweetalert.title);
            Swal.fire({ icon: mapping.sweetalert.icon, title: mapping.sweetalert.title, text: text,
                confirmButtonColor: '#1B4965', confirmButtonText: "J'ai compris" });
            return;
        }
        if (mapping && mapping.field) {
            const errorId = mapping.field === 'identifiant' ? 'error-identifiant' : 'error-motpasse';
            const groupId = mapping.field === 'identifiant' ? 'group-identifiant' : 'group-password';
            showFieldError(errorId, groupId, mapping.text);
            $(mapping.field === 'identifiant' ? '#identifiant' : '#mot_passe').trigger('focus');
            showBanner(mapping.banner, mapping.title, mapping.text);
            toastr.error(mapping.text, mapping.title);
            shakeForm();
            return;
        }
        showBanner((mapping && mapping.banner) || 'danger', (mapping && mapping.title) || 'Connexion impossible',
            (mapping && mapping.text) || fallbackMessage || 'Identifiant ou mot de passe incorrect.');
        shakeForm();
    }

    function handleAjaxError(xhr) {
        if (xhr.status === 401) {
            try { const r = JSON.parse(xhr.responseText); handleBusinessError(r.code, r.message); }
            catch (e) { handleBusinessError('ERROR', 'Identifiant ou mot de passe incorrect.'); }
            return;
        }
        if (xhr.status === 422) {
            try {
                const r = JSON.parse(xhr.responseText);
                if (r.errors) {
                    const errors = Object.values(r.errors).flat();
                    showBanner('danger', 'Formulaire incomplet', errors.join(' '));
                    toastr.error(errors.join(' '), 'Formulaire incomplet');
                } else {
                    showBanner('danger', 'Données invalides', r.message || 'Veuillez vérifier vos informations.');
                }
            } catch (e) { showBanner('danger', 'Données invalides', 'Veuillez vérifier vos informations.'); }
            shakeForm();
            return;
        }
        if (xhr.status === 429) {
            showBanner('warning', 'Trop de tentatives', 'Veuillez patienter quelques minutes avant de réessayer.');
            return;
        }
        switch (xhr.status) {
            case 0:   showBanner('danger', 'Connexion impossible', 'Impossible de joindre le serveur.'); break;
            case 419: Swal.fire({ icon: 'warning', title: 'Session expirée', text: 'La page va être actualisée.',
                        confirmButtonColor: '#1B4965', confirmButtonText: 'Actualiser'
                    }).then(function() { window.location.reload(); }); break;
            case 500: showBanner('danger', 'Erreur serveur', 'Une erreur interne est survenue.'); break;
            default:  showBanner('danger', 'Erreur ' + xhr.status, 'Veuillez réessayer.');
        }
    }

    function setLoading(state) {
        const btn = $('#btn-login'), text = $('.btn-text'), spinner = $('.btn-spinner');
        if (state) {
            btn.prop('disabled', true);
            text.html('Connexion en cours...');
            spinner.show();
        } else {
            btn.prop('disabled', false);
            text.html('<i class="ti ti-login"></i> Connexion');
            spinner.hide();
        }
    }
    function setRedirecting() {
        $('#btn-login').prop('disabled', true);
        $('.btn-text').html('<i class="ti ti-check"></i> Redirection...');
        $('.btn-spinner').show();
    }
    function shakeForm() {
        $('.school-form-panel').addClass('shake');
        setTimeout(function() { $('.school-form-panel').removeClass('shake'); }, 450);
    }
</script>

</body>
</html>
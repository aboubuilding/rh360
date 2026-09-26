{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="fr" data-theme="light" data-layout="horizontal">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="EXPERT RH 360 - Système d'Information des Ressources Humaines">

    <title>@yield('title', 'Tableau de bord') — EXPERT RH 360</title>

    <link rel="icon" type="image/png" href="{{ asset('app/assets/img/favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('app/assets/img/apple-touch-icon.png') }}">

    {{-- Core CSS --}}
    <link rel="stylesheet" href="{{ asset('app/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/@simonwep/pickr/themes/nano.min.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('app/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/css/mystyle.css') }}">

    <style>
        :root {
            /* Palette EXPERT RH 360 — bleu corporate + accent doré */
            --rh-primary: #1B4965;
            --rh-primary-dark: #0F2E42;
            --rh-primary-deep: #07202F;
            --rh-accent: #D4A94D;
            --rh-accent-soft: #E9CE9B;
            --rh-accent-dark: #B8860B;
            --rh-ink: #1f2d3a;
            --rh-ink-deep: #141d27;
            --rh-muted: #6f7e8c;
            --rh-urgent: #ff7a1a;
            --rh-urgent-dark: #cc5500;
            --rh-paper: #FBF8F1;
            --rh-paper-line: #EAE1CC;
            --rh-page-bg: #F3F1E9;
            --rh-ff: 'Kumbh Sans', sans-serif;
            --rh-ff-display: 'Playfair Display', serif;
        }

        body.menu-horizontal {
            background: var(--rh-page-bg);
            font-family: var(--rh-ff);
        }

        /* ===== Barre de titre de page ===== */
        .page-header-bar {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px; background: #fff;
            border-bottom: 1px solid var(--rh-paper-line);
            padding: 18px 28px;
        }
        .page-title-main {
            display: flex; align-items: center; gap: 12px;
            font-family: var(--rh-ff-display); font-weight: 700;
            font-size: 1.35rem; color: var(--rh-ink); margin: 0;
        }
        .page-title-main .title-icon {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(145deg, var(--rh-primary), var(--rh-primary-dark));
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem; color: var(--rh-accent-soft);
            box-shadow: 0 4px 12px rgba(27,73,101,.25);
        }
        .breadcrumb-custom {
            display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
            list-style: none; margin: 6px 0 0 50px; padding: 0;
            font-size: .8rem; color: var(--rh-muted);
        }
        .breadcrumb-custom li { display: flex; align-items: center; gap: 6px; }
        .breadcrumb-custom li + li::before {
            content: '/'; color: var(--rh-paper-line); margin-right: 6px;
        }
        .breadcrumb-custom a { color: var(--rh-muted); text-decoration: none; }
        .breadcrumb-custom a:hover { color: var(--rh-primary); }
        .breadcrumb-custom li:last-child { color: var(--rh-ink); font-weight: 600; }

        .page-header-right { display: flex; align-items: center; gap: 10px; }
        .content-area { padding: 24px 28px 48px; min-height: 60vh; }

        @media (max-width: 600px) {
            .page-header-bar { padding: 16px 18px; }
            .breadcrumb-custom { margin-left: 0; }
            .content-area { padding: 18px 16px 36px; }
        }

        /* ===== Notifications ===== */
        #toast-container {
            position: fixed; top: 20px; right: 20px; z-index: 12000;
            display: flex; flex-direction: column; gap: 10px; max-width: 360px;
        }
        .toast-item {
            font-family: var(--rh-ff); font-size: .85rem; font-weight: 500;
            padding: 12px 18px; border-radius: 10px; color: #fff;
            box-shadow: 0 10px 30px rgba(20,29,39,.18);
            animation: toast-in .25s ease-out both;
        }
        .toast-success {
            background: linear-gradient(120deg, var(--rh-primary-dark), var(--rh-primary));
            border-left: 4px solid var(--rh-accent);
        }
        .toast-error { background: #8c1c1c; border-left: 4px solid var(--rh-primary-deep); }
        @keyframes toast-in {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @media (prefers-reduced-motion: reduce) { .toast-item { animation: none; } }

        /* ===== Dropdown d'actions ===== */
        .btn-action {
            border-radius: 8px; border: 1px solid var(--rh-paper-line);
            background: #fff; color: #5b6b7a; width: 34px; height: 34px;
            transition: all .15s ease;
        }
        .btn-action:hover {
            background: var(--rh-paper); border-color: var(--rh-primary);
            color: var(--rh-primary);
        }
        .btn-action.dropdown-toggle::after { display: none; }
        .dropdown-menu-actions {
            border-radius: 10px; border: 1px solid var(--rh-paper-line);
            box-shadow: 0 8px 24px rgba(27,73,101,.12); padding: 6px; min-width: 180px;
        }
        .dropdown-menu-actions .dropdown-item {
            display: flex; align-items: center; gap: 8px; border-radius: 6px;
            font-size: .88rem; padding: .5rem .75rem; width: 100%;
            border: none; background: transparent; text-align: left; cursor: pointer;
        }
        .dropdown-menu-actions .dropdown-item i { width: 16px; color: #8fa0ad; }
        .dropdown-menu-actions .dropdown-item:hover { background: var(--rh-paper); }
        .dropdown-menu-actions .dropdown-item.text-danger { color: var(--rh-primary); }
        .dropdown-menu-actions .dropdown-item.text-danger i { color: var(--rh-primary); }
        .dropdown-menu-actions form { margin: 0; }

        /* ===== Pagination ===== */
        .rh-pagination {
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px; margin-top: 20px; font-family: var(--rh-ff);
        }
        .rh-pagination-info { font-size: .85rem; color: var(--rh-muted); }
        .rh-pagination-info strong { color: var(--rh-ink); font-weight: 600; }
        .rh-pagination-list {
            display: flex; align-items: center; gap: 6px;
            list-style: none; margin: 0; padding: 0;
        }
        .rh-page-link, .rh-page-nav {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; padding: 0 10px; border-radius: 8px;
            border: 1px solid var(--rh-paper-line); background: #fff;
            color: var(--rh-ink); font-size: .85rem; font-weight: 600;
            text-decoration: none; transition: all .15s ease;
        }
        .rh-page-link:hover, .rh-page-nav:hover {
            background: var(--rh-paper); border-color: var(--rh-primary);
            color: var(--rh-primary);
        }
        .rh-page-link.active {
            background: linear-gradient(135deg, var(--rh-primary-dark), var(--rh-primary));
            border-color: var(--rh-primary); color: #fff;
            box-shadow: 0 4px 12px rgba(27,73,101,.25);
        }
        .rh-page-dots {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 36px; color: var(--rh-muted); font-weight: 700;
        }
        .rh-page-nav.disabled { opacity: .4; pointer-events: none; cursor: default; }
        .rh-page-nav i { font-size: .75rem; }

        @media (max-width: 576px) {
            .rh-pagination { flex-direction: column; align-items: flex-start; }
        }
    </style>

    @stack('css')
    @yield('css')
</head>

<body class="menu-horizontal">

<div id="toast-container" aria-live="polite" aria-atomic="true">
    @if (session('success'))
        <div class="toast-item toast-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="toast-item toast-error" role="alert">{{ session('error') }}</div>
    @endif
</div>

@include('layouts.partials._search')
@include('layouts.partials._header')

<div class="page-wrapper">
    @hasSection('page_title')
        <div class="page-header-bar">
            <div class="page-header-left">
                <h1 class="page-title-main">
                    <span class="title-icon">
                        <i class="fas @yield('page_icon', 'fa-briefcase')"></i>
                    </span>
                    @yield('page_title')
                </h1>
                @hasSection('breadcrumb')
                    <nav aria-label="Fil d'Ariane">
                        <ul class="breadcrumb-custom">
                            @yield('breadcrumb')
                        </ul>
                    </nav>
                @endif
            </div>
            <div class="page-header-right">
                @yield('page_actions')
            </div>
        </div>
    @endif

    <main class="content-area" role="main">
        @yield('contenu')
    </main>

    @include('layouts.partials._footer')
</div>

{{-- Scripts requis --}}
<script src="{{ asset('app/assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="{{ asset('app/assets/js/feather.min.js') }}"></script>
<script src="{{ asset('app/assets/js/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('app/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('app/assets/js/moment.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('app/assets/plugins/chartjs/chart.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/chartjs/chart-data.js') }}"></script>
<script src="{{ asset('app/assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('app/assets/plugins/@simonwep/pickr/pickr.es5.min.js') }}"></script>
<script src="{{ asset('app/assets/js/theme-colorpicker.js') }}"></script>
<script src="{{ asset('app/assets/js/script.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(function () {
        $('#toast-container .toast-item').each(function () {
            const $t = $(this);
            setTimeout(() => $t.fadeOut(300, () => $t.remove()), 4000);
        });

        const pending = sessionStorage.getItem('pendingToast');
        if (pending) {
            sessionStorage.removeItem('pendingToast');
            const { type, message } = JSON.parse(pending);
            window.showToast(message, type);
        }
    });

    window.showToast = function (message, type = 'success') {
        const $toast = $('<div class="toast-item"></div>')
            .addClass(type === 'error' ? 'toast-error' : 'toast-success')
            .attr('role', type === 'error' ? 'alert' : 'status')
            .text(message);
        $('#toast-container').append($toast);
        setTimeout(() => $toast.fadeOut(300, () => $toast.remove()), 4000);
    };

    window.showToastThenReload = function (message, type = 'success') {
        sessionStorage.setItem('pendingToast', JSON.stringify({ type, message }));
        window.location.reload();
    };

    $(document).on('submit', '.form-confirm-delete', function (e) {
        e.preventDefault();
        const form = this;
        const title = form.dataset.confirmTitle || 'Confirmer la suppression ?';
        const text  = form.dataset.confirmText || 'Cette action peut être annulée plus tard.';

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            cancelButtonColor: '#6f7e8c',
            confirmButtonText: 'Oui, confirmer',
            cancelButtonText: 'Annuler'
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
</script>

@stack('js')
@yield('js')

</body>
</html>
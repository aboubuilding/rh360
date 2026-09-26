{{-- resources/views/layouts/partials/_footer.blade.php --}}
<style>
    .rh-footer {
        font-family: var(--rh-ff);
        background: linear-gradient(120deg, var(--rh-primary-dark), var(--rh-primary));
        color: rgba(255,255,255,.85);
        border-top: 3px solid var(--rh-accent);
        padding: 20px 24px;
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;
        gap: 12px; font-size: .85rem; margin-top: auto; width: 100%;
        box-shadow: 0 -4px 20px rgba(0,0,0,.1);
    }
    .rh-footer .footer-left { display: flex; flex-direction: column; gap: 4px; }
    .rh-footer .footer-left strong { color: #fff; font-weight: 700; letter-spacing: .5px; }
    .rh-footer .footer-left small { font-size: .7rem; opacity: .75; font-weight: 300; }
    .rh-footer .footer-right { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
    .rh-footer .footer-right a { color: rgba(255,255,255,.8); text-decoration: none; font-weight: 500; }
    .rh-footer .footer-right a:hover { color: var(--rh-accent-soft); }
    .rh-footer .footer-right span { color: rgba(255,255,255,.6); font-size: .75rem; background: rgba(255,255,255,.08); padding: 4px 14px; border-radius: 20px; }
    @media (max-width: 600px) {
        .rh-footer { flex-direction: column; text-align: center; padding: 16px 20px; }
        .rh-footer .footer-left { align-items: center; }
        .rh-footer .footer-right { justify-content: center; gap: 12px; }
    }
</style>

<footer class="rh-footer" id="app-footer">
    <div class="footer-left">
        &copy; {{ date('Y') }} — <strong>EXPERT RH 360</strong>
        <small>Système d'Information des Ressources Humaines</small>
    </div>
    <div class="footer-right">
        <a href="{{ route('dashboard') }}" title="Accueil"><i class="fas fa-home"></i></a>
        <span>|</span>
        <a href="#" title="Documentation"><i class="fas fa-book"></i> Documentation</a>
        <span>|</span>
        <a href="#" title="Support"><i class="fas fa-headset"></i> Support</a>
        <span>|</span>
        <span title="Version"><i class="fas fa-code-branch"></i> v{{ config('app.version', '1.0.0') }}</span>
    </div>
</footer>
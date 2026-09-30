{{-- resources/views/accueil.blade.php — page d'accueil publique --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>EXPERT RH 360 · Le système d'information RH de votre entreprise</title>
    <meta name="description" content="EXPERT RH 360 centralise dossiers salariés, contrats, carrière, congés, paie, formation, recrutement et santé au travail, avec des circuits de validation et des accès par profil.">

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('app/assets/img/favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/all.min.css') }}" />

    <style>
        :root {
            --primary: #1B4965;
            --primary-dark: #0F2E42;
            --primary-deep: #07202F;
            --accent: #D4A94D;
            --accent-soft: #E9CE9B;
            --ink: #1f2d3a;
            --muted: #5f6f7e;
            --paper: #FBF8F1;
            --paper-2: #F3EEE1;
            --line: #EAE1CC;
            --white: #ffffff;
            --radius: 14px;
            --shadow: 0 18px 40px rgba(15, 46, 66, .10);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Kumbh Sans', sans-serif; color: var(--ink); background: var(--white); line-height: 1.6; }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; }
        .conteneur { width: 100%; max-width: 1180px; margin: 0 auto; padding: 0 20px; }
        .titre-serif { font-family: 'Playfair Display', serif; letter-spacing: -.5px; }
        a:focus-visible, button:focus-visible, summary:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; border-radius: 6px; }
        .visually-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

        /* Boutons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; font-weight: 700; font-size: .95rem;
               padding: 13px 24px; border-radius: 10px; border: 1.5px solid transparent; cursor: pointer; transition: all .2s ease; }
        .btn-or { background: linear-gradient(135deg, var(--accent-soft), var(--accent)); color: var(--primary-deep); box-shadow: 0 10px 24px rgba(212,169,77,.35); }
        .btn-or:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(212,169,77,.45); }
        .btn-contour { border-color: rgba(255,255,255,.45); color: #fff; }
        .btn-contour:hover { background: rgba(255,255,255,.08); border-color: #fff; }
        .btn-primaire { background: var(--primary); color: #fff; }
        .btn-primaire:hover { background: var(--primary-dark); }
        .btn-petit { padding: 10px 18px; font-size: .88rem; }

        /* En-tête */
        .entete { position: sticky; top: 0; z-index: 50; background: rgba(7, 32, 47, .92); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,.08); }
        .entete .conteneur { display: flex; align-items: center; justify-content: space-between; height: 72px; gap: 20px; }
        .logo { display: flex; align-items: center; gap: 12px; color: #fff; }
        .logo-icone { width: 42px; height: 42px; border-radius: 10px; display: grid; place-items: center; font-size: 18px;
                      background: linear-gradient(145deg, var(--accent-soft), var(--accent)); color: var(--primary-deep); }
        .logo-texte { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.1rem; line-height: 1.1; }
        .logo-texte small { display: block; font-family: 'Kumbh Sans', sans-serif; font-size: .6rem; font-weight: 600; letter-spacing: 1.4px;
                            text-transform: uppercase; color: var(--accent-soft); margin-top: 3px; }
        .nav { display: flex; align-items: center; gap: 28px; }
        .nav ul { display: flex; gap: 26px; list-style: none; }
        .nav ul a { color: rgba(255,255,255,.82); font-weight: 500; font-size: .93rem; }
        .nav ul a:hover { color: var(--accent-soft); }
        .menu-bouton { display: none; background: none; border: 0; color: #fff; font-size: 1.4rem; cursor: pointer; padding: 6px; }

        /* Hero */
        .hero { position: relative; overflow: hidden; color: #fff;
                background: radial-gradient(120% 140% at 10% 0%, #1d5a7d 0%, var(--primary-dark) 45%, var(--primary-deep) 100%); }
        .hero::after { content: ''; position: absolute; width: 640px; height: 640px; right: -220px; bottom: -260px;
                       background: radial-gradient(circle, rgba(212,169,77,.22) 0%, rgba(212,169,77,0) 70%); pointer-events: none; }
        .hero .conteneur { position: relative; z-index: 1; display: grid; grid-template-columns: 1.05fr .95fr; gap: 56px; align-items: center; padding-top: 84px; padding-bottom: 96px; }
        .badge-hero { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px; font-size: .8rem; font-weight: 600;
                      background: rgba(212,169,77,.14); color: var(--accent-soft); border: 1px solid rgba(212,169,77,.35); margin-bottom: 22px; }
        .hero h1 { font-size: clamp(2.3rem, 4.6vw, 3.6rem); line-height: 1.07; margin-bottom: 22px; }
        .hero h1 em { font-style: normal; color: var(--accent); }
        .hero p.chapo { font-size: 1.08rem; color: rgba(255,255,255,.8); max-width: 540px; margin-bottom: 34px; }
        .hero .actions { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 26px; }
        .hero .rassurance { display: flex; flex-wrap: wrap; gap: 20px; font-size: .86rem; color: rgba(255,255,255,.7); }
        .hero .rassurance i { color: var(--accent); margin-right: 6px; }

        /* Aperçu d'application (maquette en CSS) */
        .apercu { background: var(--paper); border-radius: 18px; box-shadow: 0 30px 70px rgba(0,0,0,.35); color: var(--ink); overflow: hidden;
                  border: 1px solid rgba(255,255,255,.12); transform: perspective(1400px) rotateY(-6deg) rotateX(2deg); }
        .apercu-barre { display: flex; align-items: center; gap: 7px; padding: 12px 16px; background: var(--primary-deep); }
        .apercu-barre span { width: 10px; height: 10px; border-radius: 50%; background: rgba(255,255,255,.25); }
        .apercu-barre strong { margin-left: 10px; color: rgba(255,255,255,.75); font-size: .75rem; font-weight: 600; }
        .apercu-corps { padding: 20px; display: grid; gap: 14px; }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .kpi { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; }
        .kpi small { display: block; font-size: .7rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .kpi b { font-size: 1.35rem; color: var(--primary); }
        .kpi .tendance { font-size: .7rem; color: #1e8a4c; font-weight: 700; }
        .graphe { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 14px; }
        .graphe small { font-size: .72rem; color: var(--muted); font-weight: 600; }
        .barres { display: flex; align-items: flex-end; gap: 10px; height: 96px; margin-top: 10px; }
        .barres div { flex: 1; border-radius: 6px 6px 2px 2px; background: linear-gradient(180deg, var(--primary), #2f6d92); }
        .barres div:nth-child(3n) { background: linear-gradient(180deg, var(--accent), var(--accent-soft)); }
        .liste-mini { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 6px 14px; }
        .liste-mini div { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; font-size: .8rem; border-bottom: 1px dashed var(--line); }
        .liste-mini div:last-child { border-bottom: 0; }
        .etiquette { font-size: .68rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; }
        .etiquette.ok { background: #e3f4ea; color: #1e8a4c; }
        .etiquette.attente { background: #fdf3e2; color: #9a6208; }
        .etiquette.info { background: #e6eff5; color: var(--primary); }

        /* Sections */
        section { scroll-margin-top: 80px; }
        .bloc { padding: 96px 0; }
        .bloc.creme { background: var(--paper); }
        .entete-bloc { text-align: center; max-width: 720px; margin: 0 auto 56px; }
        .surtitre { display: inline-block; font-size: .78rem; font-weight: 700; letter-spacing: 1.6px; text-transform: uppercase; color: var(--accent); margin-bottom: 12px; }
        .entete-bloc h2 { font-size: clamp(1.9rem, 3.4vw, 2.6rem); line-height: 1.15; margin-bottom: 14px; color: var(--primary-deep); }
        .entete-bloc p { color: var(--muted); font-size: 1.02rem; }

        /* Atouts */
        .atouts { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; }
        .atout { padding: 28px 24px; border-radius: var(--radius); background: #fff; border: 1px solid var(--line); transition: all .25s ease; }
        .atout:hover { transform: translateY(-4px); box-shadow: var(--shadow); border-color: transparent; }
        .pastille { width: 50px; height: 50px; border-radius: 12px; display: grid; place-items: center; font-size: 1.2rem; margin-bottom: 18px;
                    background: rgba(27,73,101,.08); color: var(--primary); }
        .atout h3 { font-size: 1.05rem; margin-bottom: 8px; color: var(--primary-deep); }
        .atout p { font-size: .92rem; color: var(--muted); }

        /* Modules */
        .modules { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .module { position: relative; padding: 26px 22px 24px; border-radius: var(--radius); background: #fff; border: 1px solid var(--line); overflow: hidden; }
        .module::before { content: ''; position: absolute; left: 0; top: 0; height: 3px; width: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); opacity: 0; transition: opacity .25s; }
        .module:hover::before { opacity: 1; }
        .module:hover { box-shadow: var(--shadow); }
        .module .num { position: absolute; right: 18px; top: 14px; font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: var(--paper-2); }
        .module h3 { font-size: 1.02rem; margin: 14px 0 8px; color: var(--primary-deep); }
        .module ul { list-style: none; font-size: .88rem; color: var(--muted); display: grid; gap: 5px; }
        .module li::before { content: '\f00c'; font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free'; font-weight: 900; font-size: .7rem; color: var(--accent); margin-right: 8px; }

        /* Profils */
        .profils { display: grid; grid-template-columns: 1fr 1.2fr; gap: 56px; align-items: center; }
        .profils h2 { font-size: clamp(1.9rem, 3.2vw, 2.5rem); color: var(--primary-deep); line-height: 1.15; margin-bottom: 16px; }
        .profils > div > p { color: var(--muted); margin-bottom: 24px; }
        .points { list-style: none; display: grid; gap: 14px; }
        .points li { display: flex; gap: 14px; align-items: flex-start; }
        .points i { color: var(--primary); background: rgba(27,73,101,.08); width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; flex-shrink: 0; }
        .points strong { display: block; color: var(--primary-deep); }
        .points span { font-size: .9rem; color: var(--muted); }
        .grille-roles { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
        .role { background: #fff; border: 1px solid var(--line); border-left: 3px solid var(--accent); border-radius: 12px; padding: 16px 18px; }
        .role b { display: block; color: var(--primary-deep); font-size: .95rem; margin-bottom: 4px; }
        .role span { font-size: .84rem; color: var(--muted); }

        /* Étapes */
        .etapes { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; counter-reset: etape; list-style: none; }
        .etape { position: relative; padding: 30px 24px 26px; background: #fff; border-radius: var(--radius); border: 1px solid var(--line); }
        .etape::before { counter-increment: etape; content: counter(etape, decimal-leading-zero); font-family: 'Playfair Display', serif;
                         font-size: 1rem; font-weight: 700; color: var(--primary-deep); width: 46px; height: 46px; border-radius: 50%;
                         display: grid; place-items: center; background: linear-gradient(145deg, var(--accent-soft), var(--accent)); margin-bottom: 18px; }
        .etape h3 { font-size: 1.02rem; color: var(--primary-deep); margin-bottom: 8px; }
        .etape p { font-size: .9rem; color: var(--muted); }

        /* FAQ */
        .faq { max-width: 820px; margin: 0 auto; display: grid; gap: 12px; }
        .faq details { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 0 22px; transition: box-shadow .2s; }
        .faq details[open] { box-shadow: var(--shadow); border-color: transparent; }
        .faq summary { list-style: none; cursor: pointer; padding: 20px 0; font-weight: 700; color: var(--primary-deep); display: flex; justify-content: space-between; gap: 16px; }
        .faq summary::-webkit-details-marker { display: none; }
        .faq summary::after { content: '+'; font-size: 1.4rem; line-height: 1; color: var(--accent); transition: transform .2s; }
        .faq details[open] summary::after { transform: rotate(45deg); }
        .faq details p { padding: 0 0 20px; color: var(--muted); font-size: .95rem; }

        /* Appel à l'action */
        .cta { padding: 0 0 96px; background: var(--paper); }
        .cta-boite { position: relative; overflow: hidden; border-radius: 22px; padding: 64px 48px; text-align: center; color: #fff;
                     background: radial-gradient(120% 160% at 0% 0%, #1d5a7d 0%, var(--primary-dark) 50%, var(--primary-deep) 100%); }
        .cta-boite h2 { font-size: clamp(1.8rem, 3.4vw, 2.6rem); margin-bottom: 14px; }
        .cta-boite p { color: rgba(255,255,255,.78); max-width: 620px; margin: 0 auto 30px; }
        .cta-boite .actions { display: flex; justify-content: center; flex-wrap: wrap; gap: 14px; }

        /* Pied de page */
        .pied { background: var(--primary-deep); color: rgba(255,255,255,.7); padding: 64px 0 28px; font-size: .9rem; }
        .pied-grille { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 44px; }
        .pied h4 { color: #fff; font-size: .95rem; margin-bottom: 14px; }
        .pied ul { list-style: none; display: grid; gap: 8px; }
        .pied a:hover { color: var(--accent-soft); }
        .pied .apropos p { margin-top: 16px; max-width: 320px; }
        .pied-bas { border-top: 1px solid rgba(255,255,255,.1); padding-top: 22px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: .82rem; }

        /* Responsive */
        @media (max-width: 1020px) {
            .hero .conteneur, .profils { grid-template-columns: 1fr; }
            .apercu { transform: none; max-width: 560px; }
            .atouts, .modules, .etapes { grid-template-columns: repeat(2, 1fr); }
            .pied-grille { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 860px) {
            .menu-bouton { display: block; }
            .nav { position: absolute; top: 72px; left: 0; right: 0; flex-direction: column; align-items: stretch; gap: 16px;
                   padding: 20px; background: var(--primary-deep); border-bottom: 1px solid rgba(255,255,255,.08); display: none; }
            .nav.ouvert { display: flex; }
            .nav ul { flex-direction: column; gap: 14px; }
        }
        @media (max-width: 600px) {
            .bloc { padding: 72px 0; }
            .hero .conteneur { padding-top: 56px; padding-bottom: 72px; }
            .atouts, .modules, .etapes, .grille-roles, .kpis { grid-template-columns: 1fr; }
            .pied-grille { grid-template-columns: 1fr; }
            .cta-boite { padding: 48px 22px; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            * { transition: none !important; }
        }
    </style>
</head>
<body>

<a href="#contenu" class="visually-hidden">Aller au contenu</a>

<header class="entete">
    <div class="conteneur">
        <a href="{{ route('accueil') }}" class="logo" aria-label="EXPERT RH 360 — Accueil">
            <span class="logo-icone" aria-hidden="true"><i class="fas fa-briefcase"></i></span>
            <span class="logo-texte">EXPERT RH 360<small>Système d'information RH</small></span>
        </a>

        <button type="button" class="menu-bouton" id="menu-bouton" aria-controls="navigation" aria-expanded="false" aria-label="Ouvrir le menu">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>

        <nav class="nav" id="navigation" aria-label="Navigation principale">
            <ul>
                <li><a href="#atouts">Atouts</a></li>
                <li><a href="#modules">Modules</a></li>
                <li><a href="#profils">Profils</a></li>
                <li><a href="#demarrage">Démarrage</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>
            <a href="{{ route('login') }}" class="btn btn-or btn-petit">
                <i class="fas fa-right-to-bracket" aria-hidden="true"></i> Connexion
            </a>
        </nav>
    </div>
</header>

<main id="contenu">

    {{-- Hero --}}
    <section class="hero" aria-labelledby="titre-hero">
        <div class="conteneur">
            <div>
                <span class="badge-hero"><i class="fas fa-circle-check" aria-hidden="true"></i> Toute la gestion RH, en un seul endroit</span>
                <h1 id="titre-hero" class="titre-serif">Pilotez vos équipes, <em>pas la paperasse.</em></h1>
                <p class="chapo">
                    Dossiers salariés, contrats, carrière, congés, paie, formation, recrutement et santé au travail :
                    EXPERT RH 360 réunit tout le cycle de vie du salarié, avec des circuits de validation clairs
                    et des accès adaptés à chaque profil.
                </p>
                <div class="actions">
                    <a href="{{ route('login') }}" class="btn btn-or">
                        <i class="fas fa-right-to-bracket" aria-hidden="true"></i> Accéder à mon espace
                    </a>
                    <a href="#modules" class="btn btn-contour">Découvrir les modules</a>
                </div>
                <div class="rassurance">
                    <span><i class="fas fa-shield-halved" aria-hidden="true"></i>Accès par profil</span>
                    <span><i class="fas fa-clock-rotate-left" aria-hidden="true"></i>Journal d'audit</span>
                    <span><i class="fas fa-lock" aria-hidden="true"></i>Paie figée après validation</span>
                </div>
            </div>

            <div class="apercu" aria-hidden="true">
                <div class="apercu-barre"><span></span><span></span><span></span><strong>Tableau de bord RH</strong></div>
                <div class="apercu-corps">
                    <div class="kpis">
                        <div class="kpi"><small>Effectif</small><b>248</b> <span class="tendance">+4</span></div>
                        <div class="kpi"><small>Congés en cours</small><b>12</b></div>
                        <div class="kpi"><small>Contrats à échéance</small><b>5</b></div>
                    </div>
                    <div class="graphe">
                        <small>Masse salariale — 6 derniers mois</small>
                        <div class="barres">
                            <div style="height:55%"></div><div style="height:62%"></div><div style="height:58%"></div>
                            <div style="height:70%"></div><div style="height:76%"></div><div style="height:82%"></div>
                        </div>
                    </div>
                    <div class="liste-mini">
                        <div><span>Demande de congé annuel</span><span class="etiquette attente">À autoriser</span></div>
                        <div><span>Avenant au contrat</span><span class="etiquette info">Vérifié</span></div>
                        <div><span>Période de paie de septembre</span><span class="etiquette ok">Validée</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Atouts --}}
    <section id="atouts" class="bloc creme" aria-labelledby="titre-atouts">
        <div class="conteneur">
            <div class="entete-bloc">
                <span class="surtitre">Pourquoi EXPERT RH 360</span>
                <h2 id="titre-atouts" class="titre-serif">Une seule saisie, des décisions tracées</h2>
                <p>Chaque information est saisie une fois et réutilisée partout : contrats, actes de carrière, congés et bulletins de paie.</p>
            </div>
            <div class="atouts">
                <article class="atout">
                    <div class="pastille" aria-hidden="true"><i class="fas fa-id-card"></i></div>
                    <h3>Dossier salarié unique</h3>
                    <p>Identité, famille, documents, affectations et historique réunis dans une fiche complète, avec suivi de la complétude.</p>
                </article>
                <article class="atout">
                    <div class="pastille" aria-hidden="true"><i class="fas fa-diagram-project"></i></div>
                    <h3>Circuits de validation</h3>
                    <p>Proposition, contrôle RH, vérification et validation DRH : chaque acte suit son circuit, sans étape sautée.</p>
                </article>
                <article class="atout">
                    <div class="pastille" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h3>Paie maîtrisée</h3>
                    <p>Saisie des éléments variables, calcul des bulletins en FCFA, puis validation définitive qui fige la période.</p>
                </article>
                <article class="atout">
                    <div class="pastille" aria-hidden="true"><i class="fas fa-user-shield"></i></div>
                    <h3>Confidentialité</h3>
                    <p>Les données de santé restent réservées aux RH habilités ; la direction ne voit que des indicateurs agrégés.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Modules --}}
    <section id="modules" class="bloc" aria-labelledby="titre-modules">
        <div class="conteneur">
            <div class="entete-bloc">
                <span class="surtitre">Modules</span>
                <h2 id="titre-modules" class="titre-serif">Tout le cycle de vie du salarié</h2>
                <p>Huit modules intégrés, activés selon les droits de chaque utilisateur.</p>
            </div>
            <div class="modules">
                <article class="module">
                    <span class="num" aria-hidden="true">01</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-users"></i></div>
                    <h3>Dossiers RH</h3>
                    <ul><li>Création guidée du salarié</li><li>Famille et documents</li><li>Import et export</li></ul>
                </article>
                <article class="module">
                    <span class="num" aria-hidden="true">02</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-file-signature"></i></div>
                    <h3>Contrats</h3>
                    <ul><li>Contrats et avenants</li><li>Période d'essai calculée</li><li>Alertes d'échéance</li></ul>
                </article>
                <article class="module">
                    <span class="num" aria-hidden="true">03</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-arrow-trend-up"></i></div>
                    <h3>Carrière &amp; mobilité</h3>
                    <ul><li>Mutations et promotions</li><li>Avancements et intérims</li><li>Application à la date d'effet</li></ul>
                </article>
                <article class="module">
                    <span class="num" aria-hidden="true">04</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-umbrella-beach"></i></div>
                    <h3>Congés &amp; absences</h3>
                    <ul><li>Demandes et autorisations</li><li>Soldes contrôlés</li><li>Départs et reprises</li></ul>
                </article>
                <article class="module">
                    <span class="num" aria-hidden="true">05</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-money-check-dollar"></i></div>
                    <h3>Paie</h3>
                    <ul><li>Éléments variables</li><li>Calcul des bulletins</li><li>Période figée après validation</li></ul>
                </article>
                <article class="module">
                    <span class="num" aria-hidden="true">06</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-graduation-cap"></i></div>
                    <h3>Développement RH</h3>
                    <ul><li>Formation et plans</li><li>Recrutement des candidats</li><li>Campagnes d'évaluation</li></ul>
                </article>
                <article class="module">
                    <span class="num" aria-hidden="true">07</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-helmet-safety"></i></div>
                    <h3>Santé &amp; sécurité</h3>
                    <ul><li>Visites médicales</li><li>Accidents et incidents</li><li>Risques, EPI, habilitations</li></ul>
                </article>
                <article class="module">
                    <span class="num" aria-hidden="true">08</span>
                    <div class="pastille" aria-hidden="true"><i class="fas fa-chart-pie"></i></div>
                    <h3>Pilotage &amp; administration</h3>
                    <ul><li>Tableaux de bord par profil</li><li>Organisation et permissions</li><li>Journal d'audit</li></ul>
                </article>
            </div>
        </div>
    </section>

    {{-- Profils --}}
    <section id="profils" class="bloc creme" aria-labelledby="titre-profils">
        <div class="conteneur profils">
            <div>
                <span class="surtitre">Accès par profil</span>
                <h2 id="titre-profils" class="titre-serif">Chacun voit ce qu'il doit voir</h2>
                <p>Une matrice de permissions par rôle, complétée d'exceptions individuelles, protège chaque écran et chaque action.</p>
                <ul class="points">
                    <li><i class="fas fa-check" aria-hidden="true"></i><div><strong>Séparation des tâches</strong><span>Le RH prépare, le DRH valide : aucun acteur ne peut tout faire seul.</span></div></li>
                    <li><i class="fas fa-check" aria-hidden="true"></i><div><strong>Traçabilité</strong><span>Les opérations sensibles sont consignées dans le journal d'audit.</span></div></li>
                    <li><i class="fas fa-check" aria-hidden="true"></i><div><strong>Consultation encadrée</strong><span>Direction et auditeur accèdent aux indicateurs, en lecture seule.</span></div></li>
                </ul>
            </div>
            <div class="grille-roles">
                <div class="role"><b>Direction des RH</b><span>Valide contrats, actes de carrière et paie.</span></div>
                <div class="role"><b>Responsable RH</b><span>Gère les dossiers et prépare les actes.</span></div>
                <div class="role"><b>Manager</b><span>Suit son équipe et conduit les évaluations.</span></div>
                <div class="role"><b>Direction générale</b><span>Pilote grâce aux indicateurs consolidés.</span></div>
                <div class="role"><b>Auditeur</b><span>Contrôle la paie, les contrats et l'audit.</span></div>
                <div class="role"><b>Administrateur</b><span>Paramètre l'entreprise et les comptes.</span></div>
            </div>
        </div>
    </section>

    {{-- Démarrage --}}
    <section id="demarrage" class="bloc" aria-labelledby="titre-demarrage">
        <div class="conteneur">
            <div class="entete-bloc">
                <span class="surtitre">Mise en route</span>
                <h2 id="titre-demarrage" class="titre-serif">Opérationnel en quatre étapes</h2>
                <p>Un démarrage progressif, dans l'ordre naturel de la gestion RH.</p>
            </div>
            <ol class="etapes">
                <li class="etape"><h3>Paramétrer l'entreprise</h3><p>Identité, structures, postes et référentiels de classification.</p></li>
                <li class="etape"><h3>Constituer les dossiers</h3><p>Saisie guidée ou import des salariés, puis leurs documents.</p></li>
                <li class="etape"><h3>Contrats et congés</h3><p>Contrats signés, soldes de congés et circuits d'autorisation.</p></li>
                <li class="etape"><h3>Première paie</h3><p>Éléments variables, calcul des bulletins et validation de la période.</p></li>
            </ol>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="bloc creme" aria-labelledby="titre-faq">
        <div class="conteneur">
            <div class="entete-bloc">
                <span class="surtitre">Questions fréquentes</span>
                <h2 id="titre-faq" class="titre-serif">Vous vous posez des questions ?</h2>
            </div>
            <div class="faq">
                <details>
                    <summary>Comment obtenir un accès à EXPERT RH 360 ?</summary>
                    <p>Les comptes sont créés par l'administrateur de votre entreprise, qui attribue à chacun un profil (RH, DRH, manager…). Vous recevez ensuite un identifiant et un mot de passe pour vous connecter.</p>
                </details>
                <details>
                    <summary>Qui peut voir les données de santé des salariés ?</summary>
                    <p>Seuls les utilisateurs habilités au suivi santé et sécurité accèdent aux registres nominatifs. La direction et l'auditeur ne consultent que des indicateurs agrégés, sans nom ni avis médical.</p>
                </details>
                <details>
                    <summary>Peut-on modifier une paie déjà validée ?</summary>
                    <p>Non. Une période validée est figée : ni saisie, ni recalcul. Seul un super administrateur peut exceptionnellement la rouvrir, avec un motif obligatoire consigné dans l'historique.</p>
                </details>
                <details>
                    <summary>Les actes de carrière s'appliquent-ils automatiquement ?</summary>
                    <p>Oui. Un acte validé et programmé est appliqué à sa date d'effet ; un acte à effet rétroactif est appliqué dès sa programmation, avec mise à jour de l'affectation du salarié.</p>
                </details>
                <details>
                    <summary>Peut-on reprendre des salariés existants ?</summary>
                    <p>Oui. Les utilisateurs habilités peuvent importer et exporter la liste des salariés, en plus de la saisie guidée étape par étape.</p>
                </details>
                <details>
                    <summary>Que faire en cas de mot de passe oublié ?</summary>
                    <p>Contactez l'administrateur de votre entreprise : il peut réinitialiser votre mot de passe depuis la gestion des utilisateurs.</p>
                </details>
            </div>
        </div>
    </section>

    {{-- Appel à l'action --}}
    <section class="cta" aria-labelledby="titre-cta">
        <div class="conteneur">
            <div class="cta-boite">
                <h2 id="titre-cta" class="titre-serif">Prêt à simplifier votre gestion RH ?</h2>
                <p>Connectez-vous à votre espace pour retrouver vos dossiers, vos validations en attente et vos indicateurs.</p>
                <div class="actions">
                    <a href="{{ route('login') }}" class="btn btn-or">
                        <i class="fas fa-right-to-bracket" aria-hidden="true"></i> Connexion
                    </a>
                    <a href="#faq" class="btn btn-contour">Consulter la FAQ</a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer class="pied">
    <div class="conteneur">
        <div class="pied-grille">
            <div class="apropos">
                <a href="{{ route('accueil') }}" class="logo">
                    <span class="logo-icone" aria-hidden="true"><i class="fas fa-briefcase"></i></span>
                    <span class="logo-texte">EXPERT RH 360<small>Système d'information RH</small></span>
                </a>
                <p>Le système d'information des ressources humaines qui centralise dossiers, actes, paie et santé au travail.</p>
            </div>
            <div>
                <h4>Produit</h4>
                <ul>
                    <li><a href="#atouts">Atouts</a></li>
                    <li><a href="#modules">Modules</a></li>
                    <li><a href="#profils">Profils</a></li>
                </ul>
            </div>
            <div>
                <h4>Ressources</h4>
                <ul>
                    <li><a href="#demarrage">Mise en route</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h4>Mon espace</h4>
                <ul>
                    <li><a href="{{ route('login') }}">Connexion</a></li>
                </ul>
            </div>
        </div>
        <div class="pied-bas">
            <span>&copy; {{ date('Y') }} EXPERT RH 360 — Tous droits réservés.</span>
            <span>Système d'Information des Ressources Humaines</span>
        </div>
    </div>
</footer>

<script>
    (function () {
        const bouton = document.getElementById('menu-bouton');
        const nav = document.getElementById('navigation');
        bouton.addEventListener('click', function () {
            const ouvert = nav.classList.toggle('ouvert');
            bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            bouton.setAttribute('aria-label', ouvert ? 'Fermer le menu' : 'Ouvrir le menu');
        });
        nav.querySelectorAll('a[href^="#"]').forEach(function (lien) {
            lien.addEventListener('click', function () {
                nav.classList.remove('ouvert');
                bouton.setAttribute('aria-expanded', 'false');
            });
        });
    })();
</script>
</body>
</html>

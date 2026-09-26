{{-- resources/views/layouts/partials/_header.blade.php --}}

<style>
    :root {
        --rh-primary: #1B4965;
        --rh-primary-dark: #0F2E42;
        --rh-primary-deep: #07202F;
        --rh-accent: #D4A94D;
        --rh-accent-soft: #E9CE9B;
        --rh-accent-dark: #B8860B;
        --rh-ink: #1f2d3a;
        --rh-ink-deep: #141d27;
        --rh-urgent: #ff7a1a;
        --rh-urgent-dark: #cc5500;
        --rh-drop-bg: #fffdf9;
        --rh-drop-shadow: 0 16px 40px rgba(20,29,39,0.22);
        --rh-drop-border: #ece4d6;
        --rh-item-hover: #eef4f8;
        --rh-item-txt: #1a1f2e;
        --rh-item-icon: #1B4965;
        --rh-divider: #f0eadf;
        --rh-radius: 8px;
        --rh-ff: 'Kumbh Sans', sans-serif;
        --rh-ff-display: 'Playfair Display', serif;
    }

    .hbtp-root *, .hbtp-root *::before, .hbtp-root *::after { box-sizing: border-box; margin: 0; padding: 0; }
    .hbtp-root { font-family: var(--rh-ff); position: sticky; top: 0; z-index: 1000; width: 100%; }

    .hbtp-top {
        background: linear-gradient(120deg, var(--rh-primary-dark), var(--rh-primary));
        height: 56px; display: flex; align-items: center;
        justify-content: space-between; gap: 16px; padding: 0 22px;
        box-shadow: 0 2px 14px rgba(20,29,39,0.18);
        position: relative; z-index: 2;
    }
    .hbtp-brand { display: flex; align-items: center; gap: 11px; text-decoration: none; flex-shrink: 0; }
    .hbtp-brand-icon {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(145deg, var(--rh-accent-soft), var(--rh-accent));
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; color: var(--rh-primary-deep);
        box-shadow: 0 4px 12px rgba(212,169,77,0.35);
        transition: transform .2s, box-shadow .2s;
    }
    .hbtp-brand:hover .hbtp-brand-icon { transform: translateY(-1px) scale(1.04); }
    .hbtp-brand-title {
        font-size: 19px; font-weight: 700; color: #fff;
        letter-spacing: .2px; line-height: 1.15; display: block;
        font-family: var(--rh-ff-display);
    }
    .hbtp-brand-sub {
        font-size: 9.5px; color: rgba(255,255,255,.55); letter-spacing: 1.1px;
        text-transform: uppercase; line-height: 1; margin-top: 4px;
        display: block; font-weight: 400;
    }

    .hbtp-top-right { display: flex; align-items: center; gap: 5px; flex-shrink: 0; }
    .hbtp-icon-btn {
        width: 35px; height: 35px; border-radius: var(--rh-radius);
        background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.10);
        color: rgba(255,255,255,.75); display: flex; align-items: center;
        justify-content: center; font-size: 14.5px; cursor: pointer;
        transition: background .15s, color .15s, transform .15s;
        position: relative; text-decoration: none;
    }
    .hbtp-icon-btn:hover { background: rgba(255,255,255,.16); color: #fff; transform: translateY(-1px); }

    .hbtp-notif-dot {
        position: absolute; top: 6px; right: 6px; width: 7px; height: 7px;
        background: var(--rh-urgent); border-radius: 50%;
        border: 1.5px solid var(--rh-primary-dark);
        animation: rh-pulse 2s infinite;
    }
    @keyframes rh-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(255,122,26,.55); }
        70%  { box-shadow: 0 0 0 6px rgba(255,122,26,0); }
        100% { box-shadow: 0 0 0 0 rgba(255,122,26,0); }
    }
    .hbtp-sep { width: 1px; height: 24px; background: rgba(255,255,255,.14); margin: 0 6px; flex-shrink: 0; }

    .hbtp-avatar-wrap { position: relative; }
    .hbtp-avatar-btn {
        display: flex; align-items: center; gap: 9px; padding: 0 10px 0 6px;
        height: 37px; background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.10);
        border-radius: var(--rh-radius); cursor: pointer;
        transition: background .15s;
    }
    .hbtp-avatar-btn:hover { background: rgba(255,255,255,.16); }
    .hbtp-avatar-circle {
        width: 27px; height: 27px; border-radius: 50%;
        background: linear-gradient(145deg, var(--rh-accent-soft), var(--rh-accent));
        color: var(--rh-primary-deep); font-size: 11px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; box-shadow: 0 0 0 2px rgba(255,255,255,.15);
    }
    .hbtp-avatar-name { font-size: 12.5px; font-weight: 600; color: #fff; line-height: 1; display: block; }
    .hbtp-avatar-role { font-size: 10px; color: rgba(255,255,255,.45); line-height: 1; margin-top: 3px; display: block; font-weight: 300; }
    .hbtp-avatar-caret { font-size: 10px; color: rgba(255,255,255,.5); margin-left: 2px; transition: transform .2s; }
    .hbtp-avatar-wrap.open .hbtp-avatar-caret { transform: rotate(180deg); }

    .hbtp-user-drop {
        position: absolute; top: calc(100% + 8px); right: 0; width: 236px;
        background: var(--rh-drop-bg); border-radius: 12px;
        box-shadow: var(--rh-drop-shadow); border: 1px solid var(--rh-drop-border);
        display: none; z-index: 9999; overflow: hidden;
    }
    .hbtp-avatar-wrap.open .hbtp-user-drop { display: block; animation: rh-drop-in .16s ease-out; }
    @keyframes rh-drop-in {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .hbtp-udrop-header { padding: 15px 16px; background: linear-gradient(135deg,#faf6ee,#f4ecdb); border-bottom: 1px solid var(--rh-divider); }
    .hbtp-udrop-name { font-size: 13px; font-weight: 700; color: var(--rh-item-txt); }
    .hbtp-udrop-email { font-size: 11px; color: #96979c; margin-top: 2px; }
    .hbtp-udrop-role {
        display: inline-block; margin-top: 7px; background: #f0e0bb;
        color: var(--rh-accent-dark); font-size: 9.5px; font-weight: 800;
        padding: 3px 9px; border-radius: 20px; letter-spacing: .6px;
        text-transform: uppercase;
    }
    .hbtp-udrop-item { display: flex; align-items: center; gap: 10px; padding: 10px 16px; font-size: 13px; color: var(--rh-item-txt); text-decoration: none; transition: background .12s, padding-left .12s; }
    .hbtp-udrop-item:hover { background: var(--rh-item-hover); padding-left: 20px; }
    .hbtp-udrop-item i { font-size: 14px; color: var(--rh-item-icon); width: 16px; }
    .hbtp-udrop-item.danger { color: var(--rh-urgent-dark); }
    .hbtp-udrop-item.danger i { color: var(--rh-urgent-dark); }
    .hbtp-udrop-item.danger:hover { background: #fdf2f2; }
    .hbtp-udrop-div { height: 1px; background: var(--rh-divider); margin: 4px 0; }

    .hbtp-nav {
        background: linear-gradient(90deg, var(--rh-ink-deep), var(--rh-ink));
        height: 46px; display: flex; align-items: stretch; padding: 0 8px;
        position: relative; z-index: 900; overflow: visible;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.05);
    }
    .hbtp-nav-items { display: flex; align-items: stretch; flex: 1; min-width: 0; }

    .hnav-item { position: relative; display: flex; align-items: stretch; flex-shrink: 0; }
    .hnav-trigger {
        display: flex; align-items: center; gap: 7px; padding: 0 14px;
        color: rgba(255,255,255,.82); font-size: 13px; font-weight: 600;
        cursor: pointer; white-space: nowrap; text-decoration: none;
        transition: background .15s, color .15s; font-family: var(--rh-ff);
        border: none; background: transparent; height: 100%; position: relative;
    }
    .hnav-trigger > i:not(.caret) { font-size: 14px; width: 16px; text-align: center; }
    .hnav-trigger .caret { font-size: 10px; opacity: .7; margin-left: 1px; transition: transform .2s; }
    .hnav-item.open > .hnav-trigger .caret { transform: rotate(180deg); }

    .hnav-trigger::after {
        content: ''; position: absolute; left: 14px; right: 14px; bottom: 0;
        height: 3px; background: var(--rh-accent); border-radius: 2px 2px 0 0;
        transform: scaleX(0); transform-origin: center; transition: transform .18s ease;
    }
    .hnav-item:hover > .hnav-trigger,
    .hnav-item.open  > .hnav-trigger { background: rgba(255,255,255,.07); color: #fff; }
    .hnav-item:hover > .hnav-trigger::after,
    .hnav-item.open  > .hnav-trigger::after,
    .hnav-item.active > .hnav-trigger::after { transform: scaleX(1); }
    .hnav-item.active > .hnav-trigger { color: var(--rh-accent-soft); }

    .hnav-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 17px; height: 17px; padding: 0 5px; border-radius: 20px;
        background: var(--rh-urgent); color: #fff; font-size: 10px; font-weight: 800;
        line-height: 1; margin-left: 2px; box-shadow: 0 0 0 2px rgba(0,0,0,.15);
    }

    .hnav-drop {
        position: absolute; top: 46px; left: 0; min-width: 264px;
        max-height: 78vh; overflow-y: auto; background: var(--rh-drop-bg);
        border-radius: 0 0 12px 12px; box-shadow: var(--rh-drop-shadow);
        border: 1px solid var(--rh-drop-border); border-top: 3px solid var(--rh-accent);
        z-index: 99999; padding: 6px 0; opacity: 0; visibility: hidden;
        transform: translateY(-6px);
        transition: opacity .16s ease, transform .16s ease, visibility 0s linear .16s;
        pointer-events: none;
    }
    .hnav-item.open > .hnav-drop {
        opacity: 1; visibility: visible; transform: translateY(0);
        transition: opacity .16s ease, transform .16s ease, visibility 0s linear 0s;
        pointer-events: auto;
    }
    .hnav-drop-title {
        padding: 9px 16px 4px; font-size: 10px; text-transform: uppercase;
        color: #a9a49a; letter-spacing: .9px; font-weight: 800;
        font-family: var(--rh-ff); display: flex; align-items: center; gap: 6px;
    }
    .hnav-drop-title:first-child { padding-top: 8px; }
    .hnav-drop-title i { font-size: 10px; color: var(--rh-accent-dark); }

    .hnav-drop-item {
        display: flex; align-items: center; gap: 10px; padding: 10px 16px;
        font-size: 13px; font-weight: 400; color: var(--rh-item-txt);
        text-decoration: none; transition: background .12s, padding-left .12s, border-color .12s;
        line-height: 1.3; font-family: var(--rh-ff); white-space: nowrap;
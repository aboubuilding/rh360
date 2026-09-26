{{-- resources/views/admin/layouts/partials/_search.blade.php --}}
{{-- Jetons de couleur déjà déclarés dans layouts/app.blade.php : pas de
     :root redéclaré ici, pour la même raison que header/footer. --}}

<style>
    /* ===== SEARCH MODAL ===== */
    .search-modal {
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(74, 12, 25, 0.85);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 8vh;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    .search-modal.open {
        opacity: 1;
        visibility: visible;
    }

    .search-modal-content {
        background: #ffffff;
        border-radius: 16px;
        max-width: 700px;
        width: 92%;
        box-shadow: 0 30px 70px rgba(0,0,0,0.25);
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.15);
        transform: translateY(-20px) scale(0.96);
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .search-modal.open .search-modal-content {
        transform: translateY(0) scale(1);
    }

    .search-modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: linear-gradient(120deg, var(--school-red-dark), var(--school-red));
        border-bottom: 2px solid var(--school-gold);
    }

    .search-modal-header i {
        font-size: 1.2rem;
        color: #fff;
    }

    .search-modal-input {
        flex: 1;
        border: none;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 400;
        color: #fff;
        font-family: var(--school-ff);
        outline: none;
        padding: 10px 16px;
        transition: background 0.3s ease;
    }

    .search-modal-input::placeholder {
        color: rgba(255, 255, 255, 0.6);
        font-weight: 300;
    }

    .search-modal-input:focus {
        background: rgba(255, 255, 255, 0.25);
    }

    .search-modal-close {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        font-size: 1.2rem;
        color: #fff;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.2s ease;
        line-height: 1;
    }

    .search-modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: rotate(90deg);
    }

    .search-modal-close i {
        color: #fff;
    }

    /* --- Résultats --- */
    .search-modal-body {
        padding: 8px 0;
        max-height: 55vh;
        overflow-y: auto;
    }

    .search-result-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 12px 20px;
        cursor: pointer;
        transition: all 0.15s ease;
        border-bottom: 1px solid #f0f2f5;
        font-family: var(--school-ff);
        text-decoration: none;
        color: inherit;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item:hover {
        background: #faf7f0;
        padding-left: 26px;
        border-left: 4px solid var(--school-red);
    }

    .search-result-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(120deg, var(--school-red-dark), var(--school-red));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
    }

    .search-result-info {
        flex: 1;
        min-width: 0;
    }

    .search-result-title {
        font-weight: 600;
        font-size: 0.95rem;
        color: #1a2c3e;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .search-result-sub {
        font-size: 0.8rem;
        color: #6f7e8c;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .search-result-item .badge-category {
        background: var(--school-gold);
        color: #4A0C19;
        font-size: 0.6rem;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 20px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        flex-shrink: 0;
        margin-left: 8px;
    }

    .search-empty {
        padding: 40px 20px;
        text-align: center;
        color: #6f7e8c;
        font-family: var(--school-ff);
    }

    .search-empty i {
        font-size: 3rem;
        color: #dce4ea;
        margin-bottom: 12px;
        display: block;
    }

    /* Scrollbar */
    .search-modal-body::-webkit-scrollbar {
        width: 4px;
    }

    .search-modal-body::-webkit-scrollbar-track {
        background: #f0f2f5;
    }

    .search-modal-body::-webkit-scrollbar-thumb {
        background: var(--school-red);
        border-radius: 4px;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .search-modal { padding-top: 4vh; }
        .search-modal-content { width: 96%; }
        .search-modal-header { padding: 12px 16px; }
        .search-modal-input { font-size: 0.95rem; padding: 8px 12px; }
        .search-result-item { padding: 10px 14px; }
        .search-result-title { font-size: 0.85rem; }
        .search-result-sub { font-size: 0.7rem; }
        .search-result-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    }
</style>

<div class="search-modal" id="searchModal" role="dialog" aria-modal="true" aria-label="Recherche">
    <div class="search-modal-content">
        <div class="search-modal-header">
            <i class="fas fa-search"></i>
            <input type="text" class="search-modal-input" id="searchInput"
                   placeholder="Rechercher un élève, enseignant, classe, facture..."
                   aria-label="Champ de recherche"
                   autocomplete="off">
            <button class="search-modal-close" id="searchModalClose" aria-label="Fermer la recherche">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="search-modal-body" id="searchResults">
            <!-- Résultats dynamiques via JavaScript -->
            <div class="search-empty">
                <i class="fas fa-search-plus"></i>
                <p>Commencez à taper pour rechercher...</p>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const modal = document.getElementById('searchModal');
        const closeBtn = document.getElementById('searchModalClose');
        const input = document.getElementById('searchInput');
        const resultsContainer = document.getElementById('searchResults');

        // url() et non route('recherche') : cette modale est incluse sur
        // TOUTES les pages admin (via layouts/app.blade.php) — un nom de
        // route inexistant planterait donc l'application entière au
        // premier rendu, pas seulement la recherche. Tant que le
        // Controller/route de recherche n'existe pas, l'appel échoue
        // proprement (voir handleError ci-dessous) plutôt que de
        // provoquer un crash au chargement.
        const searchUrl = '{{ url('/recherche') }}';

        // Format JSON attendu du futur endpoint : un tableau d'objets
        // { title, subtitle, icon, category, url } — voir renderResults().

        let searchTimeout;
        let currentRequest = null;

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : String(str)).html();
        }

        function showEmptyState() {
            resultsContainer.innerHTML = `
                <div class="search-empty">
                    <i class="fas fa-search-plus"></i>
                    <p>Commencez à taper pour rechercher...</p>
                </div>
            `;
        }

        function showNoResults() {
            resultsContainer.innerHTML = `
                <div class="search-empty">
                    <i class="fas fa-search-minus"></i>
                    <p>Aucun résultat trouvé.</p>
                    <small style="color: #a8b6c2;">Essayez avec d'autres mots-clés</small>
                </div>
            `;
        }

        function showUnavailable() {
            resultsContainer.innerHTML = `
                <div class="search-empty">
                    <i class="fas fa-tools"></i>
                    <p>La recherche n'est pas encore disponible.</p>
                </div>
            `;
        }

        function renderResults(results) {
            if (!Array.isArray(results) || results.length === 0) {
                showNoResults();
                return;
            }

            let html = '';
            results.forEach(item => {
                html += `
                    <a href="${escapeHtml(item.url)}" class="search-result-item" tabindex="0">
                        <div class="search-result-icon"><i class="fas ${escapeHtml(item.icon || 'fa-circle')}"></i></div>
                        <div class="search-result-info">
                            <div class="search-result-title">
                                ${escapeHtml(item.title)}
                                <span class="badge-category">${escapeHtml(item.category)}</span>
                            </div>
                            <div class="search-result-sub">${escapeHtml(item.subtitle)}</div>
                        </div>
                    </a>
                `;
            });

            resultsContainer.innerHTML = html;
        }

        // ============================================
        // RECHERCHE — appel AJAX avec annulation de la requête
        // précédente si l'utilisateur retape avant qu'elle ne réponde
        // (sinon une réponse lente pourrait écraser un résultat plus
        // récent).
        // ============================================
        function performSearch(query) {
            if (currentRequest) {
                currentRequest.abort();
                currentRequest = null;
            }

            if (!query || query.trim() === '') {
                showEmptyState();
                return;
            }

            currentRequest = $.ajax({
                url: searchUrl,
                method: 'GET',
                data: { q: query },
                dataType: 'json',
                success: function (results) {
                    renderResults(results);
                },
                error: function (xhr) {
                    if (xhr.statusText === 'abort') return; // requête volontairement annulée
                    showUnavailable();
                }
            });
        }

        input.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const query = this.value;
            searchTimeout = setTimeout(() => performSearch(query), 300);
        });

        // Échap pour fermer (Ctrl+K pour ouvrir est géré plus bas, seul
        // point d'entrée pour ce raccourci dans toute l'application —
        // ne pas le redéclarer ailleurs, notamment pas dans le header).
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openModal();
            }
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });

        closeBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        function openModal() {
            modal.classList.add('open');
            setTimeout(() => {
                input.focus();
                input.select();
            }, 100);
            document.body.style.overflow = 'hidden';

            showEmptyState();
            input.value = '';
        }

        function closeModal() {
            if (currentRequest) {
                currentRequest.abort();
                currentRequest = null;
            }
            modal.classList.remove('open');
            document.body.style.overflow = '';
            input.blur();
        }

        // ============================================
        // EXPOSITION DES FONCTIONS GLOBALES
        // ============================================

        window.openSearchModal = openModal;
        window.closeSearchModal = closeModal;

        // ============================================
        // LIEN AVEC LE BOUTON DE RECHERCHE DU HEADER
        // ============================================

        document.addEventListener('DOMContentLoaded', function() {
            const searchBtn = document.querySelector('[data-search-toggle]');
            if (searchBtn) {
                searchBtn.addEventListener('click', openModal);
            }
        });
    })();
</script>
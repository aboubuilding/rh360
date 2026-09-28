@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page_title', 'Tableau de bord')
@section('page_icon', 'fa-chart-pie')

@section('breadcrumb')
    <li>Tableau de bord</li>
@endsection

@section('contenu')

{{-- Contexte utilisateur --}}
<div class="row mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="mb-1">Bonjour <strong>{{ $tdb->contexte['utilisateur'] }}</strong>,</h5>
                <p class="text-muted mb-0">
                    {{ $tdb->contexte['date'] }} —
                    Vous êtes connecté(e) en tant que <strong>{{ $tdb->contexte['role'] }}</strong>
                    @if($tdb->contexte['entreprise'])
                        pour <strong>{{ $tdb->contexte['entreprise'] }}</strong>
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Recherche rapide</div>
                        <div class="small">Ctrl+K</div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.openSearchModal && window.openSearchModal()">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Notifications non lues --}}
<div class="row mb-3" id="zone-notifications" style="display: none;">
    <div class="col-12">
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-0">
            <div>
                <i class="fas fa-bell"></i>
                <strong id="notif-count">0</strong> notification(s) contractuelle(s) non lue(s)
            </div>
            <a href="{{ route('contrats.notifications.index') }}" class="btn btn-sm btn-warning">
                Consulter
            </a>
        </div>
    </div>
</div>

{{-- Widgets --}}
<div class="row">
    @forelse($tdb->widgets as $widget)
        <div class="col-lg-6 col-xl-4 mb-3">
            @include('pilotage.partials._widget', ['widget' => $widget])
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h5>Aucun indicateur disponible</h5>
                <p class="mb-0">
                    Vous n'avez pas encore les permissions nécessaires pour consulter
                    les indicateurs du tableau de bord. Contactez votre administrateur.
                </p>
            </div>
        </div>
    @endforelse
</div>

{{-- Dernière synchro --}}
<div class="text-muted small text-end mt-3">
    <i class="fas fa-sync-alt"></i> Dernière synchronisation : {{ $tdb->derniereSynchro }}
</div>

{{-- Modal aperçu rapide --}}
<div class="modal fade" id="modal-apercu-salarie" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Aperçu rapide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="apercu-contenu">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script>
$(function () {
    // Charger le compteur de notifications au démarrage
    $.get("{{ route('notifications-contrats.compteur') }}")
        .done(function (r) {
            if (r.count > 0) {
                $('#notif-count').text(r.count);
                $('#zone-notifications').show();
            }
        });

    // Aperçu rapide (si on clique sur un widget qui appelle cet aperçu)
    $(document).on('click', '.js-apercu-salarie', function (e) {
        e.preventDefault();
        const url = $(this).data('url');

        $('#apercu-contenu').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-apercu-salarie')).show();

        $.get(url)
            .done(function (data) {
                const html = `
                    <div class="text-center mb-3">
                        ${data.photo_url
                            ? '<img src="' + data.photo_url + '" class="rounded-circle" style="width:80px;height:80px;object-fit:cover;">'
                            : '<div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;font-size:1.5rem;">' + data.initiales + '</div>'}
                    </div>
                    <h5 class="text-center mb-1">${data.nom_complet}</h5>
                    <p class="text-center text-muted small mb-3"><code>${data.matricule}</code></p>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-5">Poste</dt><dd class="col-sm-7">${data.poste ?? '—'}</dd>
                        <dt class="col-sm-5">Structure</dt><dd class="col-sm-7">${data.structure ?? '—'}</dd>
                        <dt class="col-sm-5">Embauche</dt><dd class="col-sm-7">${data.date_embauche ?? '—'}</dd>
                        <dt class="col-sm-5">Ancienneté</dt><dd class="col-sm-7">${data.anciennete ?? '—'}</dd>
                        <dt class="col-sm-5">Statut dossier</dt>
                        <dd class="col-sm-7"><span class="badge bg-${data.statut_dossier_couleur}">${data.statut_dossier}</span></dd>
                    </dl>
                    <div class="text-center mt-3">
                        <a href="${data.fiche_url}" class="btn btn-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i> Ouvrir la fiche
                        </a>
                    </div>
                `;
                $('#apercu-contenu').html(html);
            })
            .fail(function () {
                $('#apercu-contenu').html('<div class="alert alert-danger">Erreur de chargement.</div>');
            });
    });
});
</script>
@endpush
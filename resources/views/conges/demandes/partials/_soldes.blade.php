<div class="card">
    <div class="card-body">
        <h6 class="text-muted border-bottom pb-1">Solde {{ $solde->annee }} — {{ $demande->typeConge?->nom }}</h6>
        <div class="row">
            <div class="col-md-3">
                <div class="text-muted small">Ouverture</div>
                <div class="fw-bold">{{ number_format($solde->solde_ouverture, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Acquis</div>
                <div class="fw-bold">{{ number_format($solde->acquis, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Ajustement</div>
                <div class="fw-bold">{{ number_format($solde->ajustement, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Réservé</div>
                <div class="fw-bold text-warning">{{ number_format($solde->reserve, 2) }}</div>
            </div>
            <div class="col-md-3 mt-3">
                <div class="text-muted small">Consommé</div>
                <div class="fw-bold text-secondary">{{ number_format($solde->consomme, 2) }}</div>
            </div>
            <div class="col-md-3 mt-3">
                <div class="text-muted small">Disponible</div>
                <div class="fw-bold text-success fs-5">{{ number_format($solde->disponible, 2) }}</div>
            </div>
            <div class="col-md-6 mt-3 text-end">
                @can('permission', 'conges.soldes.manage')
                    <a href="{{ route('conges.soldes.pour-salarie', $solde->salarie) }}"
                       class="btn btn-sm btn-outline-secondary">
                        Voir tous les soldes de ce salarié
                    </a>
                @endcan
            </div>
        </div>

        @if((float) $demande->duree_jours > (float) $solde->disponible)
            <div class="alert alert-warning mt-3 mb-0">
                <i class="fas fa-exclamation-triangle"></i>
                La durée demandée ({{ number_format($demande->duree_jours, 2) }} j) dépasse le solde disponible
                ({{ number_format($solde->disponible, 2) }} j).
            </div>
        @endif
    </div>
</div>
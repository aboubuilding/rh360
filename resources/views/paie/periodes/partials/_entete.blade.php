<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Statut</div>
                <span class="badge bg-{{ $periode->statut->couleur() }} fs-6">
                    {{ $periode->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Bulletins</div>
                <div class="fw-bold fs-4">{{ $periode->bulletins->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Brut total</div>
                <div class="fw-bold fs-6">
                    {{ number_format($periode->bulletins->sum('montant_brut'), 0, ',', ' ') }} FCFA
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Net à payer</div>
                <div class="fw-bold fs-6 text-success">
                    {{ number_format($periode->bulletins->sum('montant_net'), 0, ',', ' ') }} FCFA
                </div>
            </div>
        </div>
    </div>
</div>

@if($periode->estFigee())
    <div class="alert alert-success">
        <i class="fas fa-lock"></i>
        <strong>Période figée.</strong>
        Aucune modification n'est possible. Pour corriger, contactez un super administrateur.
    </div>
@endif
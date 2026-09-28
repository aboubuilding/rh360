<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Situation de départ</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Structure</dt>
                    <dd class="col-sm-7">{{ $mouvement->structureDepart?->nom ?? '—' }}</dd>

                    <dt class="col-sm-5">Poste</dt>
                    <dd class="col-sm-7">{{ $mouvement->posteDepart?->intitule ?? '—' }}</dd>

                    <dt class="col-sm-5">Position classification</dt>
                    <dd class="col-sm-7">{{ $mouvement->positionDepart?->libelleComplet() ?? '—' }}</dd>

                    <dt class="col-sm-5">Lieu d'affectation</dt>
                    <dd class="col-sm-7">{{ $mouvement->lieu_affectation_depart ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted border-bottom pb-1">Situation cible</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Structure</dt>
                    <dd class="col-sm-7">{{ $mouvement->structureCible?->nom ?? '—' }}</dd>

                    <dt class="col-sm-5">Poste</dt>
                    <dd class="col-sm-7">{{ $mouvement->posteCible?->intitule ?? '—' }}</dd>

                    <dt class="col-sm-5">Position classification</dt>
                    <dd class="col-sm-7">{{ $mouvement->positionCible?->libelleComplet() ?? '—' }}</dd>

                    <dt class="col-sm-5">Lieu d'affectation</dt>
                    <dd class="col-sm-7">{{ $mouvement->lieu_affectation_cible ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($mouvement->estTemporaire())
            <hr>
            <h6 class="text-muted">Période temporaire</h6>
            <dl class="row mb-0">
                <dt class="col-sm-4">Date de fin prévue</dt>
                <dd class="col-sm-8">{{ $mouvement->date_fin_prevue?->format('d/m/Y') ?? '—' }}</dd>

                <dt class="col-sm-4">Date de fin réelle</dt>
                <dd class="col-sm-8">{{ $mouvement->date_fin_reelle?->format('d/m/Y') ?? '—' }}</dd>

                @if($mouvement->motif_cloture)
                    <dt class="col-sm-4">Motif de clôture</dt>
                    <dd class="col-sm-8">{{ $mouvement->motif_cloture }}</dd>
                @endif
            </dl>
        @endif
    </div>
</div>
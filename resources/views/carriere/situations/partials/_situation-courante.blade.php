<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-user-tag"></i> Situation courante</strong>
        @if($situation->statut_fiabilite && $situation->statut_fiabilite->value !== 'confirmed')
            @can('permission', 'carriere.validate')
                <button type="button" class="btn btn-sm btn-primary js-confirmer-fiabilite">
                    <i class="fas fa-check"></i> Confirmer la fiabilité
                </button>
            @endcan
        @endif
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Position de classification</dt>
            <dd class="col-sm-7">
                <strong>{{ $situation->positionClassification?->libelleComplet() ?? '—' }}</strong>
                @if($situation->positionClassification)
                    <br><small class="text-muted">
                        Code : {{ $situation->positionClassification->code }}
                        @if($situation->positionClassification->montant_salaire)
                            — {{ number_format($situation->positionClassification->montant_salaire, 0, ',', ' ') }} FCFA
                        @endif
                    </small>
                @endif
            </dd>

            <dt class="col-sm-5">Position d'ouverture</dt>
            <dd class="col-sm-7">{{ $situation->positionOuverture?->libelleComplet() ?? '—' }}</dd>

            <dt class="col-sm-5">Date de référence d'ouverture</dt>
            <dd class="col-sm-7">{{ $situation->date_reference_ouverture?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-5">Effet catégorie</dt>
            <dd class="col-sm-7">{{ $situation->date_effet_categorie?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-5">Effet classe</dt>
            <dd class="col-sm-7">{{ $situation->date_effet_classe?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-5">Effet échelon</dt>
            <dd class="col-sm-7">{{ $situation->date_effet_echelon?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-5">Réf. avancement</dt>
            <dd class="col-sm-7">{{ $situation->date_reference_avancement?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-5">Ancienneté</dt>
            <dd class="col-sm-7">{{ $situation->ancienneteAnnees() }} an(s)</dd>

            <dt class="col-sm-5">Source</dt>
            <dd class="col-sm-7">{{ $situation->type_source?->libelle() ?? '—' }}</dd>

            <dt class="col-sm-5">Fiabilité</dt>
            <dd class="col-sm-7">
                @if($situation->statut_fiabilite)
                    <span class="badge bg-{{ $situation->statut_fiabilite->couleur() }}">
                        {{ $situation->statut_fiabilite->libelle() }}
                    </span>
                @endif
            </dd>

            <dt class="col-sm-5">Historique</dt>
            <dd class="col-sm-7">
                @if($situation->statut_historique)
                    <span class="badge bg-{{ $situation->statut_historique->couleur() }}">
                        {{ $situation->statut_historique->libelle() }}
                    </span>
                @endif
            </dd>
        </dl>

        @if($situation->observations)
            <hr>
            <h6 class="text-muted">Observations</h6>
            <p class="mb-0 small">{{ $situation->observations }}</p>
        @endif
    </div>
</div>
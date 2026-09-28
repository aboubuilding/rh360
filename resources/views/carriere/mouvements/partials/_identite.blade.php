<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Numéro</dt>
                    <dd class="col-sm-7"><code>{{ $mouvement->numero_mouvement }}</code></dd>

                    <dt class="col-sm-5">Salarié</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('personnel.salaries.show', $mouvement->salarie) }}">
                            {{ $mouvement->salarie?->nom_complet }}
                        </a>
                        <br><small class="text-muted">{{ $mouvement->salarie?->matricule }}</small>
                    </dd>

                    <dt class="col-sm-5">Type</dt>
                    <dd class="col-sm-7">{{ $mouvement->type_mouvement->libelle() }}</dd>

                    @if($mouvement->sous_type_mouvement)
                        <dt class="col-sm-5">Sous-type</dt>
                        <dd class="col-sm-7">{{ $mouvement->sous_type_mouvement }}</dd>
                    @endif

                    <dt class="col-sm-5">Source</dt>
                    <dd class="col-sm-7">
                        {{ $mouvement->type_source?->libelle() ?? '—' }}
                        @if($mouvement->reference_source)
                            <br><small class="text-muted">{{ $mouvement->reference_source }}</small>
                        @endif
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Date de proposition</dt>
                    <dd class="col-sm-7">{{ $mouvement->date_proposition?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Date d'éligibilité</dt>
                    <dd class="col-sm-7">{{ $mouvement->date_eligibilite?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Date de décision</dt>
                    <dd class="col-sm-7">{{ $mouvement->date_decision?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Date d'effet</dt>
                    <dd class="col-sm-7">
                        <strong>{{ $mouvement->date_effet?->format('d/m/Y') ?? '—' }}</strong>
                    </dd>

                    @if($mouvement->reference_acte)
                        <dt class="col-sm-5">Référence acte</dt>
                        <dd class="col-sm-7">{{ $mouvement->reference_acte }}</dd>
                    @endif

                    @if($mouvement->date_acte)
                        <dt class="col-sm-5">Date acte</dt>
                        <dd class="col-sm-7">{{ $mouvement->date_acte?->format('d/m/Y') }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($mouvement->motif)
            <hr>
            <h6 class="text-muted">Motif</h6>
            <p class="mb-0">{{ $mouvement->motif }}</p>
        @endif
    </div>
</div>
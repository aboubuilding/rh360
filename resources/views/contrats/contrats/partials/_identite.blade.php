<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Référence</dt>
                    <dd class="col-sm-7"><code>{{ $contrat->reference }}</code></dd>

                    <dt class="col-sm-5">Type de contrat</dt>
                    <dd class="col-sm-7">{{ $contrat->type_contrat }}</dd>

                    <dt class="col-sm-5">Date de début</dt>
                    <dd class="col-sm-7">{{ $contrat->date_debut?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Date de fin</dt>
                    <dd class="col-sm-7">{{ $contrat->date_fin?->format('d/m/Y') ?? 'Durée indéterminée' }}</dd>

                    <dt class="col-sm-5">Poste</dt>
                    <dd class="col-sm-7">{{ $contrat->poste?->intitule ?? '—' }}</dd>

                    <dt class="col-sm-5">Position de classification</dt>
                    <dd class="col-sm-7">{{ $contrat->positionClassification?->libelleComplet() ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Révision</dt>
                    <dd class="col-sm-7">{{ $contrat->revision }}</dd>

                    <dt class="col-sm-5">Clé de soumission</dt>
                    <dd class="col-sm-7"><small class="text-muted">{{ $contrat->cle_soumission }}</small></dd>

                    <dt class="col-sm-5">Créé par</dt>
                    <dd class="col-sm-7">{{ $contrat->creePar?->nom_complet ?? '—' }}</dd>

                    <dt class="col-sm-5">Date de signature</dt>
                    <dd class="col-sm-7">{{ $contrat->date_signature?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Référence signée</dt>
                    <dd class="col-sm-7">{{ $contrat->reference_signee ?? '—' }}</dd>

                    @if($contrat->parent)
                        <dt class="col-sm-5">Contrat d'origine</dt>
                        <dd class="col-sm-7">
                            <a href="{{ route('contrats.contrats.show', $contrat->parent) }}">
                                {{ $contrat->parent->reference }}
                            </a>
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
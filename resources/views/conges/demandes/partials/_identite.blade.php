<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Numéro</dt>
                    <dd class="col-sm-7"><code>{{ $demande->numero_demande }}</code></dd>

                    <dt class="col-sm-5">Salarié</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('personnel.salaries.show', $demande->salarie) }}">
                            {{ $demande->salarie?->nom_complet }}
                        </a>
                    </dd>

                    <dt class="col-sm-5">Type</dt>
                    <dd class="col-sm-7">{{ $demande->typeConge?->nom ?? '—' }}</dd>

                    <dt class="col-sm-5">Date de la demande</dt>
                    <dd class="col-sm-7">{{ $demande->date_demande?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Date de début</dt>
                    <dd class="col-sm-7"><strong>{{ $demande->date_debut?->format('d/m/Y') ?? '—' }}</strong></dd>

                    <dt class="col-sm-5">Date de reprise</dt>
                    <dd class="col-sm-7"><strong>{{ $demande->date_reprise?->format('d/m/Y') ?? '—' }}</strong></dd>

                    <dt class="col-sm-5">Durée</dt>
                    <dd class="col-sm-7">{{ number_format($demande->duree_jours, 2) }} jour(s)</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Remplaçant</dt>
                    <dd class="col-sm-7">{{ $demande->remplacant ?? '—' }}</dd>

                    <dt class="col-sm-5">Référence acte</dt>
                    <dd class="col-sm-7">{{ $demande->reference_acte ?? '—' }}</dd>

                    <dt class="col-sm-5">Date acte</dt>
                    <dd class="col-sm-7">{{ $demande->date_acte?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Date de décision</dt>
                    <dd class="col-sm-7">{{ $demande->date_decision?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Créée par</dt>
                    <dd class="col-sm-7">{{ $demande->creePar?->nom_complet ?? '—' }}</dd>

                    <dt class="col-sm-5">Autorisée par</dt>
                    <dd class="col-sm-7">{{ $demande->validePar?->nom_complet ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        @if($demande->motif)
            <hr>
            <h6 class="text-muted">Motif</h6>
            <p class="mb-0">{{ $demande->motif }}</p>
        @endif
    </div>
</div>
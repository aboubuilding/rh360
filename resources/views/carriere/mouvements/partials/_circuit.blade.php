<div class="card">
    <div class="card-body">
        @php
            $etapes = [
                ['statut' => 'proposed',   'libelle' => 'Proposé',    'date' => $mouvement->date_proposition, 'user' => $mouvement->creePar],
                ['statut' => 'to_check',   'libelle' => 'Contrôlé',   'date' => $mouvement->date_controle,    'user' => $mouvement->controlePar],
                ['statut' => 'checked',    'libelle' => 'Vérifié',    'date' => null,                          'user' => null],
                ['statut' => 'validated',  'libelle' => 'Validé',     'date' => $mouvement->date_decision,    'user' => $mouvement->validePar],
                ['statut' => 'scheduled',  'libelle' => 'Programmé',  'date' => null,                          'user' => null],
                ['statut' => 'effective',  'libelle' => 'Effectif',   'date' => $mouvement->date_effet,       'user' => null],
            ];
            $statutActuel = $mouvement->statut->value;
            $indexActuel = array_search($statutActuel, array_column($etapes, 'statut'));
        @endphp

        @if(in_array($statutActuel, ['rejected', 'cancelled']))
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i>
                Mouvement <strong>{{ $mouvement->statut->libelle() }}</strong>.
                @if($mouvement->motif_cloture)
                    Motif : <em>{{ $mouvement->motif_cloture }}</em>
                @endif
            </div>
        @endif

        <div class="d-flex justify-content-between position-relative" style="margin: 20px 0;">
            @foreach($etapes as $i => $e)
                @php
                    $done = $indexActuel !== false && $i <= $indexActuel;
                    $current = $statutActuel === $e['statut'];
                    $color = $done ? 'success' : 'secondary';
                @endphp
                <div class="text-center flex-fill">
                    <div class="rounded-circle bg-{{ $color }} text-white mx-auto d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px; {{ $current ? 'box-shadow: 0 0 0 4px rgba(25,135,84,0.25);' : '' }}">
                        @if($done && ! $current)
                            <i class="fas fa-check"></i>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="small fw-bold mt-2 {{ $current ? 'text-success' : 'text-muted' }}">{{ $e['libelle'] }}</div>
                    @if($e['date'])
                        <div class="small text-muted">{{ \Carbon\Carbon::parse($e['date'])->format('d/m/Y') }}</div>
                    @endif
                    @if($e['user'])
                        <div class="small text-muted">{{ $e['user']->nom_complet }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        <hr>

        <h6 class="text-muted">Métadonnées</h6>
        <dl class="row mb-0">
            <dt class="col-sm-3">Créé par</dt>
            <dd class="col-sm-3">{{ $mouvement->creePar?->nom_complet ?? '—' }}</dd>

            <dt class="col-sm-3">Contrôlé par</dt>
            <dd class="col-sm-3">{{ $mouvement->controlePar?->nom_complet ?? '—' }}</dd>

            <dt class="col-sm-3">Validé par</dt>
            <dd class="col-sm-3">{{ $mouvement->validePar?->nom_complet ?? '—' }}</dd>

            <dt class="col-sm-3">Créé le</dt>
            <dd class="col-sm-3">{{ $mouvement->created_at->format('d/m/Y H:i') }}</dd>

            <dt class="col-sm-3">Dernière modif.</dt>
            <dd class="col-sm-3">{{ $mouvement->updated_at->format('d/m/Y H:i') }}</dd>
        </dl>
    </div>
</div>
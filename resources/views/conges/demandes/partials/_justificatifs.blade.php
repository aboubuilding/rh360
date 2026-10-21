{{-- Justificatifs de la demande et leur contrôle. --}}
<div class="card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2">
        <strong>Justificatifs</strong>
        @if($demande->typeConge?->necessiteJustificatif())
            <span>
                Justificatif requis
                @if($demande->echeance_justificatif)
                    — à remettre au plus tard le <strong>{{ $demande->echeance_justificatif->format('d/m/Y') }}</strong>
                @endif
            </span>
        @else
            <span class="text-muted">Aucun justificatif requis pour ce type.</span>
        @endif
    </div>

    <ul class="list-group list-group-flush">
        @forelse($demande->pieces as $p)
            <li class="list-group-item">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <span>
                        <a href="{{ route('conges.demandes.pieces.show', [$demande, $p]) }}"><i class="fas fa-paperclip"></i> {{ $p->nature }}</a>
                        <small class="text-muted">reçu le {{ $p->recue_le->format('d/m/Y H:i') }}</small>
                    </span>
                    <span>
                        @if($p->conforme === true)
                            <span class="badge bg-success">Conforme</span>
                        @elseif($p->conforme === false)
                            <span class="badge bg-danger">Non conforme</span>
                        @else
                            <span class="badge bg-warning text-dark">À contrôler</span>
                        @endif
                        @if($p->controlePar)
                            <small class="text-muted">par {{ $p->controlePar->nom_complet }}, le {{ $p->controle_le->format('d/m/Y') }}</small>
                        @endif
                    </span>
                </div>
                @if($p->conforme === false && $p->motif_non_conforme)
                    <div class="small text-danger mt-1">{{ $p->motif_non_conforme }}</div>
                @endif

                @if($p->conforme === null)
                    @can('permission', 'conges.validate')
                        <form method="POST" action="{{ route('conges.demandes.pieces.controler', [$demande, $p]) }}" class="row g-2 mt-1">
                            @csrf
                            <div class="col-12 col-md-6">
                                <input type="text" name="motif_non_conforme" class="form-control form-control-sm"
                                       placeholder="Motif, si la pièce n'est pas conforme" aria-label="Motif de non-conformité">
                            </div>
                            <div class="col-12 col-md-6 d-flex flex-wrap gap-2">
                                <button name="conforme" value="1" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Conforme</button>
                                <button name="conforme" value="0" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i> Non conforme</button>
                            </div>
                        </form>
                    @endcan
                @endif
            </li>
        @empty
            <li class="list-group-item text-muted">Aucun justificatif reçu.</li>
        @endforelse
    </ul>

    @if($demande->statut->value !== 'cancelled')
        @can('permission', 'conges.manage')
            <div class="card-body border-top">
                <form method="POST" action="{{ route('conges.demandes.pieces.store', $demande) }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-12 col-md-5">
                        <x-field name="nature" label="Nature" required :value="$demande->evenement?->piece_attendue" />
                    </div>
                    <div class="col-12 col-md-7">
                        <x-field name="fichier" label="Fichier" type="file" required />
                    </div>
                    @can('permission', 'conges.validate')
                        <div class="col-12">
                            <x-field name="conforme" label="J'ai vérifié la pièce : elle est conforme" type="checkbox" />
                        </div>
                    @endcan
                    <div class="col-12">
                        <button class="btn btn-outline-primary"><i class="fas fa-upload"></i> Ajouter le justificatif</button>
                    </div>
                </form>
            </div>
        @endcan
    @endif
</div>

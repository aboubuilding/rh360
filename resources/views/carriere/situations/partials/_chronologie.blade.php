<div class="card">
    <div class="card-header">
        <strong><i class="fas fa-history"></i> Chronologie de carrière</strong>
    </div>
    <div class="card-body">
        @forelse($mouvements as $m)
            <div class="border-start border-3 border-primary ps-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge bg-{{ $m->type_mouvement->couleur() }}">
                            {{ $m->type_mouvement->libelle() }}
                        </span>
                        <span class="badge bg-{{ $m->statut->couleur() }} ms-1">
                            {{ $m->statut->libelle() }}
                        </span>
                    </div>
                    <small class="text-muted">{{ $m->date_effet?->format('d/m/Y') ?? '—' }}</small>
                </div>
                <div class="mt-1 small">
                    @if($m->posteDepart)
                        {{ $m->posteDepart->intitule }}
                        <i class="fas fa-arrow-right mx-1 text-muted"></i>
                    @endif
                    <strong>{{ $m->posteCible?->intitule ?? '—' }}</strong>
                </div>
                @if($m->positionCible)
                    <div class="small text-muted">
                        Position cible : {{ $m->positionCible->libelleComplet() }}
                    </div>
                @endif
                <div class="mt-1">
                    <a href="{{ route('carriere.mouvements.show', $m) }}" class="small">
                        Voir le détail →
                    </a>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">Aucun mouvement enregistré pour ce salarié.</p>
        @endforelse
    </div>
</div>
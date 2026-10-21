{{-- Historique des changements de statut (non modifiable). --}}
<div class="card mt-3">
    <div class="card-header"><strong>Historique</strong></div>
    <ul class="list-group list-group-flush">
        @forelse($demande->historique as $h)
            <li class="list-group-item">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <span>
                        @if($h->de_statut && $h->de_statut !== $h->vers_statut)
                            {{ $h->de_statut->libelle() }} <i class="fas fa-arrow-right small text-muted"></i>
                        @endif
                        <span class="badge bg-{{ $h->vers_statut->couleur() }}">{{ $h->vers_statut->libelle() }}</span>
                    </span>
                    <small class="text-muted">
                        {{ $h->created_at->format('d/m/Y H:i') }} —
                        {{ $h->automatique ? 'automatique' : ($h->acteur?->nom_complet ?? 'système') }}
                    </small>
                </div>
                @if($h->motif)
                    <div class="small mt-1">{{ $h->motif }}</div>
                @endif
            </li>
        @empty
            <li class="list-group-item text-muted">Aucun historique.</li>
        @endforelse
    </ul>
</div>

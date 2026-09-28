<div class="card">
    <div class="card-header">
        <strong><i class="fas fa-history"></i> Historique des transitions</strong>
    </div>
    <div class="card-body">
        @forelse($contrat->historique as $h)
            <div class="border-start border-3 border-primary ps-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>{{ $h->action }}</strong>
                        @if(($h->instantane['statut_avant'] ?? null) && ($h->instantane['statut_apres'] ?? null))
                            <span class="badge bg-secondary">{{ $h->instantane['statut_avant'] }}</span>
                            <i class="fas fa-arrow-right mx-1 text-muted"></i>
                            <span class="badge bg-primary">{{ $h->instantane['statut_apres'] }}</span>
                        @endif
                    </div>
                    <small class="text-muted">{{ $h->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <div class="small text-muted mt-1">
                    Par <strong>{{ $h->utilisateur?->nom_complet ?? '—' }}</strong>
                </div>
                @if(!empty($h->instantane['motif']))
                    <div class="mt-2 small fst-italic">« {{ $h->instantane['motif'] }} »</div>
                @endif
            </div>
        @empty
            <p class="text-muted mb-0">Aucune transition enregistrée.</p>
        @endforelse
    </div>
</div>
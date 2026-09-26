<div class="card">
    <div class="card-header">
        <strong><i class="fas fa-sitemap"></i> Affectations</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Structure</th>
                    <th>Poste</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>En cours</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salarie->affectations as $a)
                    <tr>
                        <td>{{ $a->structure?->nom ?? '—' }}</td>
                        <td>{{ $a->poste?->intitule ?? '—' }}</td>
                        <td>{{ $a->date_debut?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $a->date_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($a->en_cours)
                                <span class="badge bg-success">Oui</span>
                            @else
                                <span class="badge bg-secondary">Non</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">Aucune affectation.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
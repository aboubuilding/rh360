<div class="card">
    <div class="card-header">
        <strong><i class="fas fa-plus-square"></i> Avenants à ce contrat</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Type</th>
                    <th>Effet</th>
                    <th>Fin</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contrat->avenants as $a)
                    <tr>
                        <td><code>{{ $a->reference }}</code></td>
                        <td>{{ $a->type_contrat }}</td>
                        <td>{{ $a->date_debut?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $a->date_fin?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $a->statut->couleur() }}">
                                {{ $a->statut->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('contrats.contrats.show', $a) }}" class="btn btn-sm btn-action">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
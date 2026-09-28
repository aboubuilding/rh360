<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Éléments variables saisis pour cette période</strong>
        @if(! $periode->estFigee())
            @can('permission', 'paie.manage')
                <button type="button" class="btn btn-sm btn-primary js-saisir-element">
                    <i class="fas fa-plus"></i> Nouvelle saisie
                </button>
            @endcan
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Rubrique</th>
                    <th class="text-end">Quantité</th>
                    <th class="text-end">Taux</th>
                    <th class="text-end">Montant</th>
                    <th>Observations</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $saisies = \App\Domain\Paie\Models\SaisiePaie::where('periode_id', $periode->id)
                        ->with(['salarie', 'rubrique'])
                        ->orderBy('salarie_id')
                        ->get();
                @endphp

                @forelse($saisies as $s)
                    <tr>
                        <td>{{ $s->salarie?->nom_complet }}</td>
                        <td>
                            <code>{{ $s->rubrique?->code }}</code>
                            <br><small class="text-muted">{{ $s->rubrique?->nom }}</small>
                        </td>
                        <td class="text-end">{{ number_format($s->quantite, 2) }}</td>
                        <td class="text-end">{{ number_format($s->taux, 2) }} %</td>
                        <td class="text-end fw-bold">{{ number_format($s->montant, 0, ',', ' ') }}</td>
                        <td class="small text-muted">{{ Str::limit($s->observations, 40) }}</td>
                        <td class="text-end">
                            @if(! $periode->estFigee())
                                <button type="button" class="btn btn-sm btn-action text-danger js-supprimer-saisie"
                                        data-id="{{ $s->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Aucune saisie variable pour cette période.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
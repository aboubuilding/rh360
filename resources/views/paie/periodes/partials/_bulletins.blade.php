<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Salarié</th>
                    <th class="text-end">Brut</th>
                    <th class="text-end">Retenues</th>
                    <th class="text-end">Net à payer</th>
                    <th class="text-end">IRPP</th>
                    <th class="text-end">Calculé le</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($periode->bulletins as $b)
                    <tr>
                        <td><code>{{ $b->salarie?->matricule }}</code></td>
                        <td>
                            <strong>{{ $b->salarie?->nom_complet }}</strong>
                        </td>
                        <td class="text-end">
                            {{ number_format($b->montant_brut, 0, ',', ' ') }}
                        </td>
                        <td class="text-end text-danger">
                            {{ number_format($b->montant_retenues, 0, ',', ' ') }}
                        </td>
                        <td class="text-end fw-bold text-success">
                            {{ number_format($b->montant_net, 0, ',', ' ') }}
                        </td>
                        <td class="text-end">
                            {{ number_format($b->montant_irpp, 0, ',', ' ') }}
                        </td>
                        <td class="text-end small">
                            {{ $b->calcule_le?->format('d/m/Y H:i') }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('paie.bulletins.show', $b) }}" class="btn btn-sm btn-action" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('paie.bulletins.pdf', $b) }}" class="btn btn-sm btn-action" title="PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Aucun bulletin. Cliquez sur « Calculer la paie » pour les générer.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($periode->bulletins->count() > 0)
                <tfoot class="table-light">
                    <tr>
                        <th colspan="2" class="text-end">Totaux :</th>
                        <th class="text-end">{{ number_format($periode->bulletins->sum('montant_brut'), 0, ',', ' ') }}</th>
                        <th class="text-end text-danger">{{ number_format($periode->bulletins->sum('montant_retenues'), 0, ',', ' ') }}</th>
                        <th class="text-end text-success">{{ number_format($periode->bulletins->sum('montant_net'), 0, ',', ' ') }}</th>
                        <th class="text-end">{{ number_format($periode->bulletins->sum('montant_irpp'), 0, ',', ' ') }}</th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
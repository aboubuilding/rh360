<div class="card">
    <div class="card-header">
        <strong><i class="fas fa-bell"></i> Alertes contractuelles</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Intitulé</th>
                    <th>Base de calcul</th>
                    <th>Date d'échéance</th>
                    <th>Jours restants</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contrat->alertes as $a)
                    <tr>
                        <td>{{ $a->intitule }}</td>
                        <td>
                            <small class="text-muted">
                                {{ $a->base_calcul instanceof \App\Domain\Contrats\Enums\BaseCalculEcheance
                                    ? $a->base_calcul->libelle()
                                    : $a->base_calcul }}
                            </small>
                        </td>
                        <td>{{ $a->date_echeance?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($a->en_cours)
                                @php $jours = $a->joursRestants(); @endphp
                                @if($a->estEchue())
                                    <span class="badge bg-danger">Échue</span>
                                @elseif($jours <= 7)
                                    <span class="badge bg-warning text-dark">J-{{ $jours }}</span>
                                @elseif($jours <= 30)
                                    <span class="badge bg-info text-dark">J-{{ $jours }}</span>
                                @else
                                    <span class="badge bg-light text-dark">J-{{ $jours }}</span>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($a->en_cours)
                                <span class="badge bg-primary">En cours</span>
                            @else
                                <span class="badge bg-secondary">Clôturée</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($a->en_cours)
                                @can('permission', 'contrats.manage')
                                    <form method="POST" action="{{ route('contrats.alertes.cloturer', $a) }}"
                                          class="d-inline form-confirm-delete"
                                          data-confirm-title="Clôturer cette alerte ?">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-action">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucune alerte.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-paperclip"></i> Pièces justificatives</strong>
        @can('permission', 'contrats.manage')
            <button type="button" class="btn btn-sm btn-primary js-nouvelle-piece">
                <i class="fas fa-plus"></i> Ajouter
            </button>
        @endcan
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Objet</th>
                    <th>Libellé</th>
                    <th>Type</th>
                    <th>Taille empreinte</th>
                    <th>Ajouté par</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contrat->pieces as $p)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">
                                {{ $p->objet instanceof \App\Domain\Contrats\Enums\ObjetPieceContrat
                                    ? $p->objet->libelle()
                                    : $p->objet }}
                            </span>
                        </td>
                        <td>{{ $p->libelle }}</td>
                        <td><small class="text-muted">{{ $p->type_mime }}</small></td>
                        <td>
                            <small class="text-muted" title="{{ $p->empreinte_sha256 }}">
                                {{ substr($p->empreinte_sha256, 0, 12) }}...
                            </small>
                        </td>
                        <td>{{ $p->creePar?->nom_complet ?? '—' }}</td>
                        <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('contrats.contrats.pieces.voir', [$contrat, $p]) }}"
                               target="_blank" class="btn btn-sm btn-action" title="Télécharger">
                                <i class="fas fa-download"></i>
                            </a>
                            @can('permission', 'contrats.manage')
                                <form method="POST"
                                      action="{{ route('contrats.contrats.pieces.destroy', [$contrat, $p]) }}"
                                      class="d-inline form-confirm-delete"
                                      data-confirm-title="Supprimer cette pièce ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-action text-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucune pièce jointe.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
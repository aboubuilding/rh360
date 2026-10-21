{{-- Durées des permissions exceptionnelles par événement. Variables : $evenements --}}
@if($evenements->isNotEmpty())
<div class="card mt-3">
    <div class="card-header fw-bold">Permissions exceptionnelles par événement</div>
    <div class="card-body pb-0">
        <p class="text-muted small mb-2">
            Ces permissions sont payées et ne sont pas déduites du congé payé. Une durée ne peut être
            qu'augmentée par rapport à celle prévue par les textes.
        </p>
    </div>
    @foreach($evenements as $e)
        <form method="POST" action="{{ route('conges.evenements.update', $e) }}" id="form-evenement-{{ $e->id }}">
            @csrf
            @method('PUT')
        </form>
    @endforeach
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Événement</th>
                <th>Durée (jours)</th>
                <th>À prendre dans les (mois)</th>
                <th>Pièce attendue</th>
                <th>Actif</th>
                @can('permission', 'conges.manage')<th class="text-end">Actions</th>@endcan
            </tr>
        </thead>
        <tbody>
            @foreach($evenements as $e)
                @php $formulaire = 'form-evenement-'.$e->id; @endphp
                <tr>
                    <td><strong>{{ $e->libelle }}</strong></td>
                    @can('permission', 'conges.manage')
                        <td><input type="number" name="jours" form="{{ $formulaire }}" value="{{ (float) $e->jours }}" step="0.5" min="0.5" class="form-control form-control-sm" aria-label="Durée en jours"></td>
                        <td><input type="number" name="delai_prise_mois" form="{{ $formulaire }}" value="{{ $e->delai_prise_mois }}" min="1" class="form-control form-control-sm" placeholder="—" aria-label="Délai de prise en mois"></td>
                        <td><input type="text" name="piece_attendue" form="{{ $formulaire }}" value="{{ $e->piece_attendue }}" class="form-control form-control-sm" aria-label="Pièce attendue"></td>
                        <td>
                            <input type="hidden" name="actif" value="0" form="{{ $formulaire }}">
                            <input type="checkbox" name="actif" value="1" form="{{ $formulaire }}" class="form-check-input" @checked($e->actif) aria-label="Actif">
                        </td>
                        <td class="text-end">
                            <button type="submit" form="{{ $formulaire }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-save"></i> Enregistrer</button>
                        </td>
                    @else
                        <td>{{ (float) $e->jours }}</td>
                        <td>{{ $e->delai_prise_mois ?? '—' }}</td>
                        <td>{{ $e->piece_attendue ?? '—' }}</td>
                        <td>{{ $e->actif ? 'Oui' : 'Non' }}</td>
                    @endcan
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@extends('layouts.app')

@section('title', 'Types de congés')
@section('page_title', 'Référentiel des types de congés')
@section('page_icon', 'fa-tags')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Congés & Absences</li>
    <li>Types & règles</li>
@endsection

@section('page_actions')
    @can('permission', 'conges.manage')
        <button type="button" class="btn btn-primary js-nouveau-type">
            <i class="fas fa-plus"></i> Nouveau type
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Rechercher code ou nom...">
            </div>
            <div class="col-md-3">
                <select name="categorie" class="form-select">
                    <option value="">Toutes catégories</option>
                    @foreach($categories as $val => $lib)
                        <option value="{{ $val }}" @selected(request('categorie') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Catégorie</th>
                    <th>Unité</th>
                    <th>Droit annuel</th>
                    <th>Durée max</th>
                    <th>Rémunéré</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $t)
                    <tr>
                        <td><code>{{ $t->code }}</code></td>
                        <td><strong>{{ $t->nom }}</strong></td>
                        <td><span class="badge bg-secondary">{{ $t->categorie?->libelle() }}</span></td>
                        <td>{{ $t->unite?->libelle() }}</td>
                        <td>{{ number_format($t->droit_annuel, 2) }}</td>
                        <td>{{ $t->duree_max ?? '—' }}</td>
                        <td>
                            @if($t->remunere)
                                <span class="badge bg-success">Oui</span>
                            @else
                                <span class="badge bg-secondary">Non</span>
                            @endif
                        </td>
                        <td>
                            @if($t->estActif())
                                <span class="badge bg-success">Actif</span>
                            @elseif($t->estInactif())
                                <span class="badge bg-secondary">Inactif</span>
                            @else
                                <span class="badge bg-danger">Supprimé</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    @can('permission', 'conges.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-type"
                                                    data-id="{{ $t->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'code' => $t->code,
                                                        'nom' => $t->nom,
                                                        'categorie' => $t->categorie?->value,
                                                        'unite' => $t->unite?->value,
                                                        'droit_annuel' => $t->droit_annuel,
                                                        'remunere' => $t->remunere,
                                                        'justificatif_requis' => $t->justificatif_requis,
                                                        'reference_legale' => $t->reference_legale,
                                                        'duree_max' => $t->duree_max,
                                                        'portee_duree_max' => $t->portee_duree_max,
                                                        'traitement_salarial' => $t->traitement_salarial?->value,
                                                        'impact_conge_annuel' => $t->impact_conge_annuel?->value,
                                                        'impact_anciennete' => $t->impact_anciennete?->value,
                                                        'delai_justification_jours' => $t->delai_justification_jours,
                                                        'autorisation_prealable_requise' => $t->autorisation_prealable_requise,
                                                        'actif' => $t->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('conges.types.destroy', $t) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer ce type ?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash"></i> Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucun type de congé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $types->links() }}</div>
</div>

@include('conges.types._modal', ['categories' => $categories])
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('conges.types.store') }}";
    const URL_UPDATE = "{{ route('conges.types.update', ['type' => '__ID__']) }}";

    function ouvrirModal(id = null, donnees = null) {
        const $form = $('#form-type');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $('#method-type').val('POST');
        $('#id-type').val('');

        if (id) {
            $('#titre-modal-type').text('Modifier le type');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $('#id-type').val(id);
            if (donnees) {
                $.each(donnees, function (k, v) {
                    const $el = $form.find('[name="' + k + '"]');
                    if (! $el.length) return;
                    if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                    else $el.val(v ?? '');
                });
            }
        } else {
            $('#titre-modal-type').text('Nouveau type de congé');
            $form.attr('action', URL_STORE);
        }

        bootstrap.Modal.getOrCreateInstance($('#modal-type')[0]).show();
    }

    $(document).on('click', '.js-nouveau-type', () => ouvrirModal());
    $(document).on('click', '.js-edit-type', function () {
        ouvrirModal($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-type').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-type')).hide();
            window.showToastThenReload(r.message || 'Enregistré.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
                window.showToast('Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
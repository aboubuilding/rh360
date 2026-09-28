@extends('layouts.app')

@section('title', 'Règles de contrat')
@section('page_title', 'Règles de contrat')
@section('page_icon', 'fa-cog')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('contrats.contrats.index') }}">Contrats</a></li>
    <li>Règles</li>
@endsection

@section('page_actions')
    @can('permission', 'contrats.validate')
        <button type="button" class="btn btn-primary js-nouvelle-regle">
            <i class="fas fa-plus"></i> Nouvelle règle
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <select name="type_contrat" class="form-select">
                    <option value="">Tous types</option>
                    @foreach($types as $val => $lib)
                        <option value="{{ $val }}" @selected(request('type_contrat') === $val)>{{ $lib }}</option>
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
                    <th>Type</th>
                    <th>Catégorie</th>
                    <th>Date d'effet</th>
                    <th>Essai max</th>
                    <th>Renouvellements</th>
                    <th>Créée par</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($regles as $r)
                    <tr>
                        <td>{{ $r->type_contrat }}</td>
                        <td>{{ $r->categorie?->libelle ?? '—' }}</td>
                        <td>{{ $r->date_effet?->format('d/m/Y') }}</td>
                        <td>{{ $r->duree_max_essai ?? '—' }} j</td>
                        <td>{{ $r->nombre_renouvellements_max }}</td>
                        <td>{{ $r->creePar?->nom_complet ?? '—' }}</td>
                        <td class="text-end">
                            @can('permission', 'contrats.validate')
                                <button type="button" class="btn btn-sm btn-action js-edit-regle"
                                        data-id="{{ $r->id }}"
                                        data-donnees="{{ json_encode([
                                            'type_contrat' => $r->type_contrat,
                                            'categorie_id' => $r->categorie_id,
                                            'date_effet' => $r->date_effet?->format('Y-m-d'),
                                            'parametres' => $r->parametres,
                                        ]) }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('contrats.regles.destroy', $r) }}"
                                      class="d-inline form-confirm-delete"
                                      data-confirm-title="Supprimer cette règle ?">
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
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune règle définie.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $regles->links() }}</div>
</div>

@include('contrats.regles._modal', ['types' => $types, 'categories' => \App\Domain\Classification\Models\CategorieClassification::orderBy('ordre')->get()])
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('contrats.regles.store') }}";
    const URL_UPDATE = "{{ route('contrats.regles.update', ['regle' => '__ID__']) }}";

    function ouvrirModal(id, donnees) {
        const $modal = $('#modal-regle');
        const $form  = $('#form-regle');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $form.find('input[name="_method"]').val('POST');
        $('#method-regle').val('POST');
        $('#id-regle').val('');

        if (id) {
            $('#titre-modal-regle').text('Modifier la règle');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $('#id-regle').val(id);
            if (donnees) {
                $.each(donnees, function (k, v) {
                    if (k === 'parametres') {
                        $.each(v, function (kp, vp) {
                            $form.find('[name="parametres[' + kp + ']"]').val(vp ?? '');
                        });
                    } else {
                        $form.find('[name="' + k + '"]').val(v ?? '');
                    }
                });
            }
        } else {
            $('#titre-modal-regle').text('Nouvelle règle');
            $form.attr('action', URL_STORE);
        }
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $(document).on('click', '.js-nouvelle-regle', () => ouvrirModal());
    $(document).on('click', '.js-edit-regle', function () {
        ouvrirModal($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-regle').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-regle')).hide();
            window.showToastThenReload(r.message || 'Règle enregistrée.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
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
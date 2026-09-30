
@extends('layouts.app')

@section('title', 'Structures')
@section('page_title', 'Structures organisationnelles')
@section('page_icon', 'fa-sitemap')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Organisation</li>
    <li>Structures</li>
@endsection

@section('page_actions')
    <a href="{{ route('organisation.structures.organigramme') }}" class="btn btn-secondary">
        <i class="fas fa-project-diagram"></i> Organigramme
    </a>
    @can('permission', 'organisation.manage')
        <button type="button" class="btn btn-primary js-nouvelle-structure">
            <i class="fas fa-plus"></i> Nouvelle structure
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher nom ou code...">
            </div>
            <div class="col-md-4">
                <select name="type_structure_id" class="form-select">
                    <option value="">Tous les types</option>
                    @foreach($types as $type)
                        <option value="{{ $type->id }}" @selected(request('type_structure_id') == $type->id)>{{ $type->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
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
                    <th>Type</th>
                    <th>Parent</th>
                    <th>Localisation</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($structures as $structure)
                    <tr>
                        <td><code>{{ $structure->code }}</code></td>
                        <td><strong>{{ $structure->nom }}</strong></td>
                        <td>{{ $structure->typeStructure?->nom ?? '—' }}</td>
                        <td>{{ $structure->parent?->nom ?? '—' }}</td>
                        <td>{{ $structure->localisation ?? '—' }}</td>
                        <td>
                            @if($structure->estActif())
                                <span class="badge bg-success">Actif</span>
                            @elseif($structure->estInactif())
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
                                    <li><a class="dropdown-item" href="{{ route('organisation.structures.show', $structure) }}"><i class="fas fa-eye"></i> Voir</a></li>
                                    @can('permission', 'organisation.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-structure"
                                                    data-id="{{ $structure->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'type_structure_id' => $structure->type_structure_id,
                                                        'parent_id' => $structure->parent_id,
                                                        'code' => $structure->code,
                                                        'nom' => $structure->nom,
                                                        'localisation' => $structure->localisation,
                                                        'centre_cout' => $structure->centre_cout,
                                                        'actif' => $structure->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item js-fusionner-structure"
                                                    data-id="{{ $structure->id }}"
                                                    data-nom="{{ $structure->nom }}">
                                                <i class="fas fa-code-branch"></i> Fusionner
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('organisation.structures.destroy', $structure) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer cette structure ?">
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
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune structure.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $structures->links() }}</div>
</div>

@include('organisation.structures._modal')
@include('organisation.structures._modal-fusion')
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('organisation.structures.store') }}";
    const URL_UPDATE = "{{ route('organisation.structures.update', ['structure' => '__ID__']) }}";

    function ouvrir(id = null, donnees = null) {
        const $form = $('#form-structure');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        if (id) {
            $('#titre-modal-structure').text('Modifier la structure');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-structure').text('Nouvelle structure');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-structure')).show();
    }

    $(document).on('click', '.js-nouvelle-structure', () => ouvrir());
    $(document).on('click', '.js-edit-structure', function () {
        ouvrir($(this).data('id'), $(this).data('donnees'));
    });

    $(document).on('click', '.js-fusionner-structure', function () {
        const id = $(this).data('id');
        const nom = $(this).data('nom');
        $('#fusion-source-nom').text(nom);
        $('#form-fusion-structure').attr('action',
            "{{ url('/admin/organisation/structures/__ID__/fusionner') }}".replace('__ID__', id));
        $('#form-fusion-structure')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-fusion-structure')).show();
    });

    function soumettreModal(formSelector, modalId, url) {
        $(formSelector).on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const texte = $btn.html();

            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').remove();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

            $.ajax({
                url: url,
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (r) {
                bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                window.showToastThenReload(r.message || 'Enregistré.');
            })
            .fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    $.each(xhr.responseJSON.errors, function (champ, messages) {
                        const $el = $form.find('[name="' + champ + '"]');
                        $el.addClass('is-invalid');
                        $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                } else { window.showToast('Erreur.', 'error'); }
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    }

    $('#form-structure').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-structure')).hide();
            window.showToastThenReload(r.message || 'Enregistré.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else { window.showToast('Erreur.', 'error'); }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });

    $('#form-fusion-structure').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-fusion-structure')).hide();
            window.showToastThenReload(r.message || 'Fusion effectuée.');
        })
        .fail(function (xhr) {
            window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
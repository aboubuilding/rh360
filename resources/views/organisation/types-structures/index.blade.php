@extends('layouts.app')

@section('title', 'Types de structures')
@section('page_title', 'Types de structures')
@section('page_icon', 'fa-layer-group')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Organisation</li>
    <li>Types de structures</li>
@endsection

@section('page_actions')
    @can('permission', 'organisation.manage')
        <button type="button" class="btn btn-primary js-nouveau-type">
            <i class="fas fa-plus"></i> Nouveau type
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Ordre</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($types as $type)
                    <tr>
                        <td><code>{{ $type->code }}</code></td>
                        <td><strong>{{ $type->nom }}</strong></td>
                        <td>{{ $type->ordre }}</td>
                        <td>
                            @if($type->estActif())
                                <span class="badge bg-success">Actif</span>
                            @elseif($type->estInactif())
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
                                    @can('permission', 'organisation.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-type"
                                                    data-id="{{ $type->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'code' => $type->code,
                                                        'nom' => $type->nom,
                                                        'ordre' => $type->ordre,
                                                        'actif' => $type->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('organisation.types-structures.destroy', $type) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer ce type de structure ?">
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
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun type de structure.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $types->links() }}</div>
</div>

@include('organisation.types-structures._modal')
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('organisation.types-structures.store') }}";
    const URL_UPDATE = "{{ route('organisation.types-structures.update', ['typeStructure' => '__ID__']) }}";

    function ouvrir(id = null, donnees = null) {
        const $form = $('#form-type-structure');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        if (id) {
            $('#titre-modal-type-structure').text('Modifier le type de structure');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-type-structure').text('Nouveau type de structure');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-type-structure')).show();
    }

    $(document).on('click', '.js-nouveau-type', () => ouvrir());
    $(document).on('click', '.js-edit-type', function () {
        ouvrir($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-type-structure').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-type-structure')).hide();
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
});
</script>
@endpush
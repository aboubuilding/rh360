@extends('layouts.app')

@section('title', 'Catégories')
@section('page_title', 'Catégories de classification')
@section('page_icon', 'fa-tags')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Classification</li>
    <li>Catégories</li>
@endsection

@section('page_actions')
    @can('permission', 'classification.manage')
        <button type="button" class="btn btn-primary js-nouvelle-categorie">
            <i class="fas fa-plus"></i> Nouvelle catégorie
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-6">
                <select name="referentiel_id" class="form-select">
                    <option value="">Tous les référentiels</option>
                    @foreach($referentiels as $r)
                        <option value="{{ $r->id }}" @selected(request('referentiel_id') == $r->id)>{{ $r->nom }}</option>
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
                <tr><th>Référentiel</th><th>Code</th><th>Libellé</th><th>Ordre</th><th>État</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($categories as $c)
                    <tr>
                        <td>{{ $c->referentiel?->nom ?? '—' }}</td>
                        <td><code>{{ $c->code }}</code></td>
                        <td><strong>{{ $c->libelle }}</strong></td>
                        <td>{{ $c->ordre }}</td>
                        <td>@if($c->estActif())<span class="badge bg-success">Actif</span>@else<span class="badge bg-secondary">Inactif</span>@endif</td>
                        <td class="text-end">
                            @can('permission', 'classification.manage')
                                <button type="button" class="btn btn-sm btn-action js-edit-categorie"
                                        data-id="{{ $c->id }}"
                                        data-donnees="{{ json_encode([
                                            'referentiel_id' => $c->referentiel_id,
                                            'code' => $c->code,
                                            'libelle' => $c->libelle,
                                            'ordre' => $c->ordre,
                                            'actif' => $c->actif,
                                        ]) }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('classification.categories.destroy', $c) }}"
                                      class="d-inline form-confirm-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-action text-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune catégorie.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $categories->links() }}</div>
</div>

@include('classification.categories._modal')
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('classification.categories.store') }}";
    const URL_UPDATE = "{{ route('classification.categories.update', ['category' => '__ID__']) }}";

    function ouvrir(id = null, donnees = null) {
        const $form = $('#form-categorie');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');

        if (id) {
            $('#titre-modal-categorie').text('Modifier la catégorie');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-categorie').text('Nouvelle catégorie');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-categorie')).show();
    }

    $(document).on('click', '.js-nouvelle-categorie', () => ouvrir());
    $(document).on('click', '.js-edit-categorie', function () {
        ouvrir($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-categorie').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-categorie')).hide();
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
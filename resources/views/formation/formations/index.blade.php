@extends('layouts.app')

@section('title', 'Catalogue formations')
@section('page_title', 'Catalogue des formations')
@section('page_icon', 'fa-graduation-cap')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Développement RH</li>
    <li>Catalogue formations</li>
@endsection

@section('page_actions')
    @can('permission', 'formation.manage')
        <button type="button" class="btn btn-primary js-nouvelle-formation">
            <i class="fas fa-plus"></i> Nouvelle formation
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Code, intitulé, domaine...">
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
                    <th>Intitulé</th>
                    <th>Domaine</th>
                    <th>Durée</th>
                    <th>Modalité</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($formations as $f)
                    <tr>
                        <td><code>{{ $f->code }}</code></td>
                        <td><strong>{{ $f->intitule }}</strong></td>
                        <td>{{ $f->domaine ?? '—' }}</td>
                        <td>{{ number_format($f->duree_heures, 1) }} h</td>
                        <td>{{ $f->modalite?->libelle() }}</td>
                        <td>
                            @if($f->estActif())
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    @can('permission', 'formation.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-formation"
                                                    data-id="{{ $f->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'code' => $f->code,
                                                        'intitule' => $f->intitule,
                                                        'domaine' => $f->domaine,
                                                        'objectif' => $f->objectif,
                                                        'duree_heures' => $f->duree_heures,
                                                        'modalite' => $f->modalite?->value,
                                                        'actif' => $f->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('formation.formations.destroy', $f) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer cette formation ?">
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
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune formation.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $formations->links() }}</div>
</div>

@include('formation.formations._modal')
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('formation.formations.store') }}";
    const URL_UPDATE = "{{ route('formation.formations.update', ['formation' => '__ID__']) }}";

    function ouvrirModal(id = null, donnees = null) {
        const $form = $('#form-formation');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        if (id) {
            $('#titre-modal-formation').text('Modifier la formation');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-formation').text('Nouvelle formation');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-formation')).show();
    }

    $(document).on('click', '.js-nouvelle-formation', () => ouvrirModal());
    $(document).on('click', '.js-edit-formation', function () {
        ouvrirModal($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-formation').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-formation')).hide();
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
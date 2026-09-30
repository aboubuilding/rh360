@extends('layouts.app')

@section('title', 'Postes')
@section('page_title', 'Postes')
@section('page_icon', 'fa-id-badge')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Organisation</li>
    <li>Postes</li>
@endsection

@section('page_actions')
    @can('permission', 'organisation.manage')
        <button type="button" class="btn btn-primary js-nouveau-poste">
            <i class="fas fa-plus"></i> Nouveau poste
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher intitulé ou code...">
            </div>
            <div class="col-md-4">
                <select name="structure_id" class="form-select">
                    <option value="">Toutes les structures</option>
                    @foreach($structures as $s)
                        <option value="{{ $s->id }}" @selected(request('structure_id') == $s->id)>{{ $s->nom }}</option>
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
                    <th>Intitulé</th>
                    <th>Structure</th>
                    <th>Catégorie</th>
                    <th>Effectif cible</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($postes as $poste)
                    <tr>
                        <td><code>{{ $poste->code }}</code></td>
                        <td><strong>{{ $poste->intitule }}</strong></td>
                        <td>{{ $poste->structure?->nom ?? '—' }}</td>
                        <td>{{ $poste->categorie ?? '—' }}</td>
                        <td>{{ $poste->effectif_cible ?? '—' }}</td>
                        <td>
                            @if($poste->estActif())
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
                                    @can('permission', 'organisation.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-poste"
                                                    data-id="{{ $poste->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'structure_id' => $poste->structure_id,
                                                        'code' => $poste->code,
                                                        'intitule' => $poste->intitule,
                                                        'categorie' => $poste->categorie,
                                                        'effectif_cible' => $poste->effectif_cible,
                                                        'actif' => $poste->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('organisation.postes.destroy', $poste) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer ce poste ?">
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
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun poste.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $postes->links() }}</div>
</div>

@include('organisation.postes._modal')
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('organisation.postes.store') }}";
    const URL_UPDATE = "{{ route('organisation.postes.update', ['poste' => '__ID__']) }}";

    function ouvrir(id = null, donnees = null) {
        const $form = $('#form-poste');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');

        if (id) {
            $('#titre-modal-poste').text('Modifier le poste');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-poste').text('Nouveau poste');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-poste')).show();
    }

    $(document).on('click', '.js-nouveau-poste', () => ouvrir());
    $(document).on('click', '.js-edit-poste', function () {
        ouvrir($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-poste').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-poste')).hide();
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
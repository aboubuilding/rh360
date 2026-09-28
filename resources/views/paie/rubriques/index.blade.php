@extends('layouts.app')

@section('title', 'Rubriques de paie')
@section('page_title', 'Référentiel des rubriques de paie')
@section('page_icon', 'fa-list')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Rubriques</li>
@endsection

@section('page_actions')
    @can('permission', 'paie.manage')
        <button type="button" class="btn btn-primary js-nouvelle-rubrique">
            <i class="fas fa-plus"></i> Nouvelle rubrique
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
                <select name="nature" class="form-select">
                    <option value="">Toutes natures</option>
                    @foreach($natures as $val => $lib)
                        <option value="{{ $val }}" @selected(request('nature') === $val)>{{ $lib }}</option>
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
                    <th>Nature</th>
                    <th>Récurrence</th>
                    <th class="text-end">Taux</th>
                    <th class="text-end">Montant déf.</th>
                    <th>Imposable</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rubriques as $r)
                    <tr>
                        <td><code>{{ $r->code }}</code></td>
                        <td><strong>{{ $r->nom }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $r->nature?->couleur() ?? 'secondary' }}">
                                {{ $r->nature?->libelle() }}
                            </span>
                        </td>
                        <td>{{ $r->recurrence?->libelle() }}</td>
                        <td class="text-end">{{ number_format($r->taux, 2) }} %</td>
                        <td class="text-end">{{ number_format($r->montant_defaut, 0, ',', ' ') }}</td>
                        <td>
                            @if($r->imposable)
                                <span class="badge bg-success">Oui</span>
                            @else
                                <span class="badge bg-secondary">Non</span>
                            @endif
                        </td>
                        <td>
                            @if($r->estActif())
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
                                    @can('permission', 'paie.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-rubrique"
                                                    data-id="{{ $r->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'code' => $r->code,
                                                        'nom' => $r->nom,
                                                        'description' => $r->description,
                                                        'nature' => $r->nature?->value,
                                                        'recurrence' => $r->recurrence?->value,
                                                        'mode_calcul' => $r->mode_calcul?->value,
                                                        'taux' => $r->taux,
                                                        'montant_defaut' => $r->montant_defaut,
                                                        'imposable' => $r->imposable,
                                                        'traitement_fiscal' => $r->traitement_fiscal?->value,
                                                        'pourcentage_imposable' => $r->pourcentage_imposable,
                                                        'soumis_cotisation' => $r->soumis_cotisation,
                                                        'actif' => $r->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('paie.rubriques.destroy', $r) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer cette rubrique ?">
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
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucune rubrique.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $rubriques->links() }}</div>
</div>

@include('paie.rubriques._modal')
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('paie.rubriques.store') }}";
    const URL_UPDATE = "{{ route('paie.rubriques.update', ['rubrique' => '__ID__']) }}";

    function ouvrirModal(id = null, donnees = null) {
        const $form = $('#form-rubrique');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $('#method-rubrique').val('POST');
        $('#id-rubrique').val('');

        if (id) {
            $('#titre-modal-rubrique').text('Modifier la rubrique');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $('#id-rubrique').val(id);
            if (donnees) {
                $.each(donnees, function (k, v) {
                    const $el = $form.find('[name="' + k + '"]');
                    if (! $el.length) return;
                    if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                    else $el.val(v ?? '');
                });
            }
        } else {
            $('#titre-modal-rubrique').text('Nouvelle rubrique');
            $form.attr('action', URL_STORE);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-rubrique')).show();
    }

    $(document).on('click', '.js-nouvelle-rubrique', () => ouvrirModal());
    $(document).on('click', '.js-edit-rubrique', function () {
        ouvrirModal($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-rubrique').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-rubrique')).hide();
            window.showToastThenReload(r.message || 'Enregistré.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
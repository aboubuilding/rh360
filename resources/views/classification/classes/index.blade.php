@extends('layouts.app')

@section('title', 'Classes')
@section('page_title', 'Classes de classification')
@section('page_icon', 'fa-layer-group')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Classification</li>
    <li>Classes</li>
@endsection

@section('page_actions')
    @can('permission', 'classification.manage')
        <button type="button" class="btn btn-primary js-nouveau-classe">
            <i class="fas fa-plus"></i> Nouvelle classe
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
                <tr>
                    <th>Référentiel</th>
                    <th>Code</th>
                    <th>Libellé</th>
                    <th>Ordre</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $classe)
                    <tr>
                        <td>{{ $classe->referentiel?->nom ?? '—' }}</td>
                        <td><code>{{ $classe->code }}</code></td>
                        <td><strong>{{ $classe->libelle }}</strong></td>
                        <td>{{ $classe->ordre }}</td>
                        <td>
                            @if($classe->estActif())
                                <span class="badge bg-success">Actif</span>
                            @elseif($classe->estInactif())
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
                                    @can('permission', 'classification.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-classe"
                                                    data-id="{{ $classe->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'referentiel_id' => $classe->referentiel_id,
                                                        'code' => $classe->code,
                                                        'libelle' => $classe->libelle,
                                                        'ordre' => $classe->ordre,
                                                        'actif' => $classe->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <form method="POST"
                                                  action="{{ route('classification.classes.destroy', $classe) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer cette classe ?">
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
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune classe.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $classes->links() }}</div>
</div>

@include('classification.classes._modal')
@endsection

@push('js')
<script>
$(function () {
    'use strict';

    const URL_STORE  = "{{ route('classification.classes.store') }}";
    const URL_UPDATE = "{{ route('classification.classes.update', ['classe' => '__ID__']) }}";
    const MODAL_ID   = 'modal-classe';
    const FORM_ID    = 'form-classe';

    function ouvrirModal(id = null, donnees = null) {
        const $modal  = $('#' + MODAL_ID);
        const $form   = $('#' + FORM_ID);
        const $method = $('#method-classe');
        const $titre  = $('#titre-modal-classe');
        const $id     = $('#id-classe');

        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $id.val('');

        if (id) {
            $titre.text('Modifier la classe');
            $method.val('PUT');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $id.val(id);

            if (donnees) {
                $.each(donnees, function (champ, valeur) {
                    const $el = $form.find('[name="' + champ + '"]');
                    if (!$el.length) return;
                    if ($el.attr('type') === 'checkbox') {
                        $el.prop('checked', !!valeur);
                    } else {
                        $el.val(valeur !== null && valeur !== undefined ? valeur : '');
                    }
                });
            }
        } else {
            $titre.text('Nouvelle classe');
            $method.val('POST');
            $form.attr('action', URL_STORE);
        }

        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $(document).on('click', '.js-nouveau-classe', function () { ouvrirModal(); });
    $(document).on('click', '.js-edit-classe', function () {
        ouvrirModal($(this).data('id'), $(this).data('donnees'));
    });

    $('#' + FORM_ID).on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn  = $form.find('button[type="submit"]');
        const texteBtn = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (response) {
            bootstrap.Modal.getInstance(document.getElementById(MODAL_ID)).hide();
            window.showToastThenReload(response.message || 'Enregistré.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.invalid-feedback').remove();
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
                window.showToast('Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast('Erreur lors de l\'enregistrement.', 'error');
            }
        })
        .always(function () {
            $btn.prop('disabled', false).html(texteBtn);
        });
    });
});
</script>
@endpush
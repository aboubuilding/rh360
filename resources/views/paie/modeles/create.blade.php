@extends('layouts.app')

@section('title', 'Nouveau modèle de paie')
@section('page_title', 'Nouveau modèle de paie')
@section('page_icon', 'fa-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.modeles.index') }}">Modèles</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-modele" method="POST" action="{{ route('paie.modeles.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="nom" label="Nom du modèle" required />
                </div>
                <div class="col-md-6">
                    <x-field name="categorie_id" label="Catégorie de classification" type="select" required
                             :options="$categories->pluck('libelle', 'id')->all()" />
                </div>
            </div>

            <div class="form-check mb-3">
                <input type="hidden" name="actif" value="0">
                <input type="checkbox" name="actif" id="actif" value="1" class="form-check-input" checked>
                <label class="form-check-label" for="actif">Modèle actif</label>
            </div>

            <hr>
            <h6 class="text-muted mb-3">Rubriques rattachées</h6>

            <div id="rubriques-wrapper">
                <div class="row align-items-end mb-2 rubrique-ligne">
                    <div class="col-md-5">
                        <label class="form-label small">Rubrique</label>
                        <select name="rubriques[0][rubrique_id]" class="form-select">
                            <option value="">— Choisir —</option>
                            @foreach($rubriques as $r)
                                <option value="{{ $r->id }}">{{ $r->code }} — {{ $r->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Montant par défaut</label>
                        <input type="number" name="rubriques[0][montant_defaut]" class="form-control" step="0.01" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ordre</label>
                        <input type="number" name="rubriques[0][ordre]" class="form-control" value="0">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger js-supprimer-ligne w-100">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-ajouter-ligne">
                <i class="fas fa-plus"></i> Ajouter une rubrique
            </button>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Créer le modèle
                </button>
                <a href="{{ route('paie.modeles.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    let index = 1;

    $('#btn-ajouter-ligne').on('click', function () {
        const template = $('.rubrique-ligne').first().clone();
        template.find('select, input').each(function () {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
            }
            if ($(this).is('input[type="number"]')) $(this).val(0);
            else if ($(this).is('select')) $(this).val('');
        });
        template.find('.is-invalid').removeClass('is-invalid');
        $('#rubriques-wrapper').append(template);
        index++;
    });

    $(document).on('click', '.js-supprimer-ligne', function () {
        if ($('.rubrique-ligne').length > 1) {
            $(this).closest('.rubrique-ligne').remove();
        } else {
            window.showToast('Au moins une rubrique est requise.', 'error');
        }
    });

    $('#form-modele').on('submit', function (e) {
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
            window.showToast(r.message || 'Enregistré.');
            setTimeout(() => { window.location.href = r.redirect || "{{ route('paie.modeles.index') }}"; }, 600);
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
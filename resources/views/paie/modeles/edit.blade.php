@extends('layouts.app')

@section('title', 'Modifier le modèle de paie')
@section('page_title', 'Modifier le modèle — '.$modele->nom)
@section('page_icon', 'fa-edit')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.modeles.index') }}">Modèles</a></li>
    <li>Modifier</li>
@endsection

@section('page_actions')
    <a href="{{ route('paie.modeles.apercu', $modele) }}" class="btn btn-outline-primary"><i class="fas fa-eye"></i> Aperçu du bulletin</a>
@endsection

@section('contenu')
@php
    // Lignes affichées : celles du modèle, ou une ligne vide pour un modèle sans rubrique.
    $lignes = $modele->rubriques->sortBy('ordre')->values();
@endphp
<div class="card">
    <div class="card-body">
        <form id="form-modele" method="POST" action="{{ route('paie.modeles.update', $modele) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <x-field name="nom" label="Nom du modèle" required :value="$modele->nom" />
                </div>
                <div class="col-md-6">
                    <x-field name="categorie_id" label="Catégorie de classification" type="select" required
                             :value="$modele->categorie_id"
                             :options="$categories->pluck('libelle', 'id')->all()" />
                </div>
            </div>

            <div class="form-check mb-3">
                <input type="hidden" name="actif" value="0">
                <input type="checkbox" name="actif" id="actif" value="1" class="form-check-input" @checked($modele->actif)>
                <label class="form-check-label" for="actif">Modèle actif</label>
            </div>

            <hr>
            <h6 class="text-muted mb-1">Rubriques du modèle</h6>
            <p class="text-muted small">
                Le salaire de base, la prime d'ancienneté, les cotisations et l'impôt sont calculés par la paie. Le montant
                par défaut s'applique aux salariés qui n'ont pas de montant individuel pour la rubrique (0 : montant individuel uniquement).
            </p>

            <div id="rubriques-wrapper">
                @foreach($lignes->isEmpty() ? [null] : $lignes as $i => $ligne)
                    <div class="row g-2 align-items-end mb-2 rubrique-ligne">
                        <div class="col-12 col-md-5">
                            <label class="form-label small">Rubrique</label>
                            <select name="rubriques[{{ $i }}][rubrique_id]" class="form-select">
                                <option value="">— Choisir —</option>
                                @foreach($rubriques as $r)
                                    <option value="{{ $r->id }}" @selected($ligne?->rubrique_id === $r->id)>({{ $r->effetSalaire()['signe'] }}) {{ $r->code }} — {{ $r->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Montant par défaut</label>
                            <input type="number" name="rubriques[{{ $i }}][montant_defaut]" class="form-control" step="1" min="0" value="{{ (float) ($ligne?->montant_defaut ?? 0) }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small">Ordre</label>
                            <input type="number" name="rubriques[{{ $i }}][ordre]" class="form-control" min="0" value="{{ $ligne?->ordre ?? ($i + 1) * 10 }}">
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="button" class="btn btn-outline-danger js-supprimer-ligne w-100" title="Retirer la rubrique">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-ajouter-ligne">
                <i class="fas fa-plus"></i> Ajouter une rubrique
            </button>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer les modifications
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
    let index = {{ max(1, $lignes->count()) }};

    $('#btn-ajouter-ligne').on('click', function () {
        const template = $('.rubrique-ligne').first().clone();
        template.find('select, input').each(function () {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
            }
            if ($(this).is('select')) $(this).val('');
            else if ($(this).attr('name')?.includes('[ordre]')) $(this).val((index + 1) * 10);
            else $(this).val(0);
        });
        template.find('.is-invalid').removeClass('is-invalid');
        template.find('.invalid-feedback').remove();
        $('#rubriques-wrapper').append(template);
        index++;
    });

    // La dernière ligne est vidée plutôt que supprimée : un modèle peut n'avoir aucune rubrique.
    $(document).on('click', '.js-supprimer-ligne', function () {
        const $ligne = $(this).closest('.rubrique-ligne');
        if ($('.rubrique-ligne').length > 1) {
            $ligne.remove();
        } else {
            $ligne.find('select').val('');
            $ligne.find('input[name$="[montant_defaut]"]').val(0);
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
                    // « rubriques.2.rubrique_id » → « rubriques[2][rubrique_id] »
                    const parties = champ.split('.');
                    const nom = parties[0] + parties.slice(1).map(p => '[' + p + ']').join('');
                    const $el = $form.find('[name="' + nom + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
                window.showToast(xhr.responseJSON.message || 'Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'Modifier l\'entreprise')
@section('page_title', 'Modifier la fiche entreprise')
@section('page_icon', 'fa-building')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('admin.entreprise.index') }}">Entreprise</a></li>
    <li>Modifier</li>
@endsection

@section('contenu')
<form id="form-entreprise" method="POST" action="{{ route('admin.entreprise.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card mb-3">
        <div class="card-header"><strong>Identité</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><x-field name="nom" label="Raison sociale" required :value="$entreprise->nom" /></div>
                <div class="col-md-3"><x-field name="sigle" label="Sigle" :value="$entreprise->sigle" /></div>
                <div class="col-md-3"><x-field name="forme_juridique" label="Forme juridique" :value="$entreprise->forme_juridique" /></div>
                <div class="col-md-4"><x-field name="nif" label="NIF" :value="$entreprise->nif" /></div>
                <div class="col-md-4"><x-field name="numero_employeur_cnss" label="N° employeur CNSS" :value="$entreprise->numero_employeur_cnss" /></div>
                <div class="col-md-4"><x-field name="secteur" label="Secteur" :value="$entreprise->secteur" /></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Coordonnées</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12"><x-field name="adresse" label="Adresse" type="textarea" :value="$entreprise->adresse" /></div>
                <div class="col-md-6"><x-field name="ville" label="Ville" :value="$entreprise->ville" /></div>
                <div class="col-md-6"><x-field name="pays" label="Pays" :value="$entreprise->pays" /></div>
                <div class="col-md-6"><x-field name="telephone" label="Téléphone" :value="$entreprise->telephone" /></div>
                <div class="col-md-6"><x-field name="email" label="Email" type="email" :value="$entreprise->email" /></div>
                <div class="col-md-3"><x-field name="devise" label="Devise" :value="$entreprise->devise" /></div>
                <div class="col-md-3"><x-field name="date_bascule" label="Date de bascule" type="date" :value="$entreprise->date_bascule?->format('Y-m-d')" /></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Présentation des documents</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><x-field name="direction_emettrice" label="Direction émettrice" :value="$entreprise->direction_emettrice" /></div>
                <div class="col-md-6"><x-field name="service_emetteur" label="Service émetteur" :value="$entreprise->service_emetteur" /></div>
                <div class="col-md-12"><x-field name="texte_en_tete" label="Texte d'en-tête" type="textarea" :value="$entreprise->texte_en_tete" /></div>
                <div class="col-md-12"><x-field name="texte_pied_page" label="Texte de pied de page" type="textarea" :value="$entreprise->texte_pied_page" /></div>
                <div class="col-md-4"><x-field name="nom_signataire" label="Nom du signataire" :value="$entreprise->nom_signataire" /></div>
                <div class="col-md-4"><x-field name="fonction_signataire" label="Fonction du signataire" :value="$entreprise->fonction_signataire" /></div>
                <div class="col-md-4"><x-field name="lieu_signature" label="Lieu de signature" :value="$entreprise->lieu_signature" /></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Logo</strong></div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    @if($entreprise->chemin_logo)
                        <img src="{{ route('admin.entreprise.logo') }}" alt="Logo" class="img-fluid mb-2" style="max-height:100px;">
                    @else
                        <div class="text-muted mb-2"><i class="fas fa-image fa-3x"></i></div>
                    @endif
                </div>
                <div class="col-md-9">
                    <x-field name="logo" label="Changer le logo" type="file" help="PNG, JPG ou JPEG — 2 Mo max." />
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
        <a href="{{ route('admin.entreprise.index') }}" class="btn btn-secondary">Annuler</a>
    </div>
</form>
@endsection

@push('js')
<script>
$(function () {
    $('#form-entreprise').on('submit', function (e) {
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
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToastThenReload(r.message || 'Fiche mise à jour.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
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
@extends('layouts.app')

@section('title', 'Nouvel utilisateur')
@section('page_title', 'Nouvel utilisateur')
@section('page_icon', 'fa-user-plus')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('admin.utilisateurs.index') }}">Utilisateurs</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-utilisateur" method="POST" action="{{ route('admin.utilisateurs.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="nom_complet" label="Nom complet" required />
                </div>
                <div class="col-md-6">
                    <x-field name="email" label="Email" type="email" />
                </div>

                <div class="col-md-6">
                    <x-field name="identifiant" label="Identifiant de connexion" required
                             help="Utilisé pour se connecter. Unique dans l'entreprise." />
                </div>
                <div class="col-md-6">
                    <x-field name="role" label="Rôle" type="select" required
                             :options="\App\Domain\Administration\Models\Utilisateur::roles()" />
                </div>

                <div class="col-md-6">
                    <x-field name="password" label="Mot de passe" type="password" required
                             help="Minimum 8 caractères, majuscule, minuscule et chiffre." />
                </div>
                <div class="col-md-6">
                    <x-field name="password_confirmation" label="Confirmer le mot de passe" type="password" required />
                </div>

                <div class="col-md-12">
                    <x-field name="actif" label="Compte actif" type="checkbox" :value="true" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Créer
                </button>
                <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-utilisateur').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Utilisateur créé.');
            setTimeout(() => { window.location.href = r.redirect; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
                window.showToast('Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast('Erreur lors de la création.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
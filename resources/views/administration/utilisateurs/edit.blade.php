@extends('layouts.app')

@section('title', 'Modifier ' . $utilisateur->nom_complet)
@section('page_title', 'Modifier l\'utilisateur')
@section('page_icon', 'fa-user-edit')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('admin.utilisateurs.index') }}">Utilisateurs</a></li>
    <li><a href="{{ route('admin.utilisateurs.show', $utilisateur) }}">{{ $utilisateur->nom_complet }}</a></li>
    <li>Modifier</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-utilisateur" method="POST" action="{{ route('admin.utilisateurs.update', $utilisateur) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <x-field name="nom_complet" label="Nom complet" required :value="$utilisateur->nom_complet" />
                </div>
                <div class="col-md-6">
                    <x-field name="email" label="Email" type="email" :value="$utilisateur->email" />
                </div>

                <div class="col-md-6">
                    <x-field name="identifiant" label="Identifiant de connexion" required
                             :value="$utilisateur->identifiant" />
                </div>
                <div class="col-md-6">
                    <x-field name="role" label="Rôle" type="select" required
                             :value="$utilisateur->role"
                             :options="\App\Domain\Administration\Models\Utilisateur::roles()" />
                </div>

                <div class="col-md-6">
                    <x-field name="password" label="Nouveau mot de passe" type="password"
                             help="Laisser vide pour conserver le mot de passe actuel." />
                </div>
                <div class="col-md-6">
                    <x-field name="password_confirmation" label="Confirmer le mot de passe" type="password" />
                </div>

                <div class="col-md-12">
                    <x-field name="actif" label="Compte actif" type="checkbox" :value="$utilisateur->actif" />
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('admin.utilisateurs.show', $utilisateur) }}" class="btn btn-secondary">Annuler</a>
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
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Utilisateur mis à jour.');
            setTimeout(() => { window.location.href = "{{ route('admin.utilisateurs.show', $utilisateur) }}"; }, 600);
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
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
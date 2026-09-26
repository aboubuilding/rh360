@extends('layouts.app')

@section('title', 'Utilisateurs')
@section('page_title', 'Utilisateurs')
@section('page_icon', 'fa-users-cog')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Administration</li>
    <li>Utilisateurs</li>
@endsection

@section('page_actions')
    @can('permission', 'admin.utilisateurs.manage')
        <button type="button" class="btn btn-primary js-nouveau-utilisateur">
            <i class="fas fa-plus"></i> Nouvel utilisateur
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Rechercher nom, identifiant, email...">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">Tous les rôles</option>
                    @foreach(\App\Domain\Administration\Models\Utilisateur::roles() as $code => $libelle)
                        <option value="{{ $code }}" @selected(request('role') === $code)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="etat" class="form-select">
                    <option value="">Tous les états</option>
                    <option value="1" @selected(request('etat') === '1')>Actif</option>
                    <option value="0" @selected(request('etat') === '0')>Inactif</option>
                    <option value="-1" @selected(request('etat') === '-1')>Supprimé</option>
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
                    <th>Nom complet</th>
                    <th>Identifiant</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>État</th>
                    <th>Dernière connexion</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($utilisateurs as $u)
                    <tr>
                        <td><strong>{{ $u->nom_complet }}</strong></td>
                        <td>{{ $u->identifiant }}</td>
                        <td>{{ $u->email ?? '—' }}</td>
                        <td><span class="badge bg-primary">{{ $u->libelleRole() }}</span></td>
                        <td>
                            @if($u->estActif())
                                <span class="badge bg-success">Actif</span>
                            @elseif($u->estInactif())
                                <span class="badge bg-secondary">Inactif</span>
                            @else
                                <span class="badge bg-danger">Supprimé</span>
                            @endif
                        </td>
                        <td>{{ $u->derniere_connexion?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.utilisateurs.show', $u) }}">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </li>
                                    @can('permission', 'admin.utilisateurs.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-edit-utilisateur"
                                                    data-id="{{ $u->id }}"
                                                    data-donnees="{{ json_encode([
                                                        'nom_complet' => $u->nom_complet,
                                                        'email' => $u->email,
                                                        'identifiant' => $u->identifiant,
                                                        'role' => $u->role,
                                                        'actif' => $u->actif,
                                                    ]) }}">
                                                <i class="fas fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item js-toggle-utilisateur"
                                                    data-id="{{ $u->id }}"
                                                    data-nom="{{ $u->nom_complet }}"
                                                    data-actif="{{ $u->actif ? '1' : '0' }}">
                                                <i class="fas fa-{{ $u->actif ? 'ban' : 'check' }}"></i>
                                                {{ $u->actif ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </li>
                                    @endcan
                                    @can('permission', 'admin.permissions.manage')
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('admin.permissions.exceptions', $u) }}">
                                                <i class="fas fa-key"></i> Exceptions
                                            </a>
                                        </li>
                                    @endcan
                                    @can('permission', 'admin.utilisateurs.manage')
                                        <li>
                                            <form method="POST"
                                                  action="{{ route('admin.utilisateurs.destroy', $u) }}"
                                                  class="form-confirm-delete"
                                                  data-confirm-title="Supprimer cet utilisateur ?">
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
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $utilisateurs->links() }}</div>
</div>

@include('administration.utilisateurs._modal')
@endsection

@push('js')
<script>
$(function () {
    'use strict';

    const URL_STORE  = "{{ route('admin.utilisateurs.store') }}";
    const URL_UPDATE = "{{ route('admin.utilisateurs.update', ['utilisateur' => '__ID__']) }}";
    const MODAL_ID   = 'modal-utilisateur';
    const FORM_ID    = 'form-utilisateur';

    function ouvrirModal(id = null, donnees = null) {
        const $modal  = $('#' + MODAL_ID);
        const $form   = $('#' + FORM_ID);
        const $method = $('#method-utilisateur');
        const $titre  = $('#titre-modal-utilisateur');
        const $id     = $('#id-utilisateur');

        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $id.val('');

        if (id) {
            $titre.text('Modifier l\'utilisateur');
            $method.val('PUT');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $id.val(id);
            if (donnees) {
                $.each(donnees, function (champ, valeur) {
                    const $el = $form.find('[name="' + champ + '"]');
                    if (!$el.length) return;
                    if ($el.attr('type') === 'checkbox') $el.prop('checked', !!valeur);
                    else $el.val(valeur !== null && valeur !== undefined ? valeur : '');
                });
            }
        } else {
            $titre.text('Nouvel utilisateur');
            $method.val('POST');
            $form.attr('action', URL_STORE);
        }

        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $(document).on('click', '.js-nouveau-utilisateur', function () { ouvrirModal(); });
    $(document).on('click', '.js-edit-utilisateur', function () {
        ouvrirModal($(this).data('id'), $(this).data('donnees'));
    });

    // Toggle actif/inactif par AJAX
    $(document).on('click', '.js-toggle-utilisateur', function () {
        const $btn = $(this);
        const id = $btn.data('id');
        const nom = $btn.data('nom');
        const actif = $btn.data('actif') == 1;

        Swal.fire({
            title: actif ? 'Désactiver cet utilisateur ?' : 'Activer cet utilisateur ?',
            text: nom,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            cancelButtonColor: '#6f7e8c',
            confirmButtonText: 'Oui',
            cancelButtonText: 'Annuler'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('admin.utilisateurs.toggle-actif', ['utilisateur' => '__ID__']) }}".replace('__ID__', id),
                method: 'POST',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (response) {
                window.showToastThenReload(response.message || 'Statut modifié.');
            })
            .fail(function () {
                window.showToast('Erreur lors de la modification du statut.', 'error');
            });
        });
    });

    // Soumission AJAX du modal
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
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
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher nom, identifiant, email...">
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
                            {{-- « Désactivé » = connexion bloquée (colonne actif) ; « Supprimé » = suppression logique (etat) --}}
                            @if($u->estSupprime())
                                <span class="badge bg-danger">Supprimé</span>
                            @elseif(! $u->estActif())
                                <span class="badge bg-secondary">Désactivé</span>
                            @else
                                <span class="badge bg-success">Actif</span>
                            @endif
                        </td>
                        <td>{{ $u->derniere_connexion?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.utilisateurs.show', $u) }}"><i class="fas fa-eye"></i> Voir</a></li>
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
                                            <form method="POST" action="{{ route('admin.utilisateurs.toggle-actif', $u) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-{{ $u->actif ? 'ban' : 'check' }}"></i>
                                                    {{ $u->actif ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>
                                        </li>
                                    @endcan
                                    @can('permission', 'admin.permissions.manage')
                                        <li><a class="dropdown-item" href="{{ route('admin.permissions.exceptions', $u) }}"><i class="fas fa-key"></i> Exceptions</a></li>
                                    @endcan
                                    @can('permission', 'admin.utilisateurs.manage')
                                        <li>
                                            <form method="POST" action="{{ route('admin.utilisateurs.destroy', $u) }}"
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
    const URL_STORE  = "{{ route('admin.utilisateurs.store') }}";
    const URL_UPDATE = "{{ route('admin.utilisateurs.update', ['utilisateur' => '__ID__']) }}";

    function ouvrir(id = null, donnees = null) {
        const $form = $('#form-utilisateur');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        if (id) {
            $('#titre-modal-utilisateur').text('Modifier l\'utilisateur');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-utilisateur').text('Nouvel utilisateur');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-utilisateur')).show();
    }

    $(document).on('click', '.js-nouveau-utilisateur', () => ouvrir());
    $(document).on('click', '.js-edit-utilisateur', function () {
        ouvrir($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-utilisateur').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-utilisateur')).hide();
            window.showToastThenReload(r.message || 'Enregistré.');
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
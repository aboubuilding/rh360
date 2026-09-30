@extends('layouts.app')

@section('title', 'Absences')
@section('page_title', 'Constat et régularisation des absences')
@section('page_icon', 'fa-user-slash')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Congés & Absences</li>
    <li>Absences</li>
@endsection

@section('page_actions')
    @can('permission', 'conges.manage')
        <button type="button" class="btn btn-secondary js-transmettre-paie">
            <i class="fas fa-paper-plane"></i> Transmettre à la paie
        </button>
        <a href="{{ route('conges.absences.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Constater une absence
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, matricule...">
            </div>
            <div class="col-md-2">
                <select name="qualification" class="form-select">
                    <option value="">Toutes qualifications</option>
                    @foreach($qualifications as $val => $lib)
                        <option value="{{ $val }}" @selected(request('qualification') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="statut_paie" class="form-select">
                    <option value="">Tous états paie</option>
                    @foreach($statutsPaie as $val => $lib)
                        <option value="{{ $val }}" @selected(request('statut_paie') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="du" value="{{ request('du') }}" class="form-control">
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
                    <th style="width:40px;">
                        <input type="checkbox" id="select-all-absences" class="form-check-input">
                    </th>
                    <th>Salarié</th>
                    <th>Type</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Durée</th>
                    <th>Qualification</th>
                    <th>Paie</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absences as $a)
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input js-absence-check"
                                   value="{{ $a->id }}"
                                   @if($a->estTransmise()) disabled @endif>
                        </td>
                        <td>
                            <strong>{{ $a->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $a->salarie?->matricule }}</small>
                        </td>
                        <td>{{ $a->typeConge?->nom ?? '—' }}</td>
                        <td>{{ $a->debut_le?->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($a->fin_le)
                                {{ $a->fin_le->format('d/m/Y H:i') }}
                            @else
                                <span class="badge bg-warning text-dark">Ouverte</span>
                            @endif
                        </td>
                        <td>{{ number_format($a->duree_heures ?? $a->dureeJours(), 2) }}</td>
                        <td>
                            @if($a->qualification)
                                <span class="badge bg-{{ $a->qualification->couleur() }}">
                                    {{ $a->qualification->libelle() }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($a->statut_transmission_paie)
                                <span class="badge bg-{{ $a->statut_transmission_paie->couleur() }}">
                                    {{ $a->statut_transmission_paie->libelle() }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('conges.absences.show', $a) }}">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </li>
                                    @can('permission', 'conges.manage')
                                        <li>
                                            <button type="button" class="dropdown-item js-qualifier"
                                                    data-id="{{ $a->id }}"
                                                    data-type="{{ $a->typeConge?->nom }}">
                                                <i class="fas fa-tag"></i> Qualifier
                                            </button>
                                        </li>
                                    @endcan
                                    @can('permission', 'conges.validate')
                                        <li>
                                            <button type="button" class="dropdown-item js-regulariser"
                                                    data-id="{{ $a->id }}">
                                                <i class="fas fa-balance-scale"></i> Régulariser
                                            </button>
                                        </li>
                                    @endcan
                                    @can('permission', 'conges.manage')
                                        @if(! $a->estTransmise())
                                            <li>
                                                <form method="POST"
                                                      action="{{ route('conges.absences.destroy', $a) }}"
                                                      class="form-confirm-delete"
                                                      data-confirm-title="Supprimer cette absence ?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucune absence.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $absences->links() }}</div>
</div>

@include('conges.absences.partials._modal-qualifier')
@include('conges.absences.partials._modal-regulariser')
@include('conges.absences.partials._modal-transmettre')
@endsection

@push('js')
<script>
$(function () {
    // Sélection multiple
    $('#select-all-absences').on('change', function () {
        $('.js-absence-check:not(:disabled)').prop('checked', $(this).prop('checked'));
    });

    // Qualifier
    $(document).on('click', '.js-qualifier', function () {
        const id = $(this).data('id');
        const type = $(this).data('type') || '';
        $('#titre-modal-qualifier').text('Qualifier : ' + type);
        $('#form-qualifier').attr('action', "{{ url('/conges/absences/__ID__/qualifier') }}".replace('__ID__', id));
        $('#form-qualifier')[0].reset();
        $('#form-qualifier').find('.is-invalid').removeClass('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-qualifier')).show();
    });

    // Régulariser
    $(document).on('click', '.js-regulariser', function () {
        const id = $(this).data('id');
        $('#form-regulariser').attr('action', "{{ url('/conges/absences/__ID__/regulariser') }}".replace('__ID__', id));
        $('#form-regulariser')[0].reset();
        $('#form-regulariser').find('.is-invalid').removeClass('is-invalid');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-regulariser')).show();
    });

    // Transmettre paie
    $(document).on('click', '.js-transmettre-paie', function () {
        const ids = $('.js-absence-check:checked').map(function () { return $(this).val(); }).get();
        if (ids.length === 0) {
            window.showToast('Sélectionnez au moins une absence.', 'error');
            return;
        }
        $('#form-transmettre').find('[name="absence_ids[]"]').remove();
        ids.forEach(function (id) {
            $('#form-transmettre').append('<input type="hidden" name="absence_ids[]" value="' + id + '">');
        });
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-transmettre')).show();
    });

    // Soumissions AJAX
    function soumettreModal(formSelector, modalId) {
        $(formSelector).on('submit', function (e) {
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
                bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
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
    }

    soumettreModal('#form-qualifier',   'modal-qualifier');
    soumettreModal('#form-regulariser', 'modal-regulariser');
    soumettreModal('#form-transmettre', 'modal-transmettre');
});
</script>
@endpush
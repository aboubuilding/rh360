<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-users"></i> Membres du foyer</strong>
        @can('permission', 'salaries.manage')
            <button type="button" class="btn btn-sm btn-primary js-nouveau-membre">
                <i class="fas fa-plus"></i> Ajouter
            </button>
        @endcan
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Nom complet</th>
                    <th>Lien</th>
                    <th>Date naissance</th>
                    <th>Enfant déclaré</th>
                    <th>À charge</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salarie->membresFoyer as $m)
                    <tr>
                        <td>{{ $m->nom_complet }}</td>
                        <td>{{ $m->lien_parente }}</td>
                        <td>{{ $m->date_naissance?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if($m->est_enfant_declare)
                                <span class="badge bg-success">Oui</span>
                            @else
                                <span class="badge bg-secondary">Non</span>
                            @endif
                        </td>
                        <td>
                            @if($m->est_a_charge)
                                <span class="badge bg-success">Oui</span>
                            @else
                                <span class="badge bg-secondary">Non</span>
                            @endif
                        </td>
                        <td>
                            @if($m->etat === \App\Domain\Shared\Enums\Etat::ACTIF)
                                <span class="badge bg-success">Actif</span>
                            @elseif($m->etat === \App\Domain\Shared\Enums\Etat::INACTIF)
                                <span class="badge bg-secondary">Archivé</span>
                            @else
                                <span class="badge bg-danger">Supprimé</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('permission', 'salaries.manage')
                                <button type="button" class="btn btn-sm btn-action js-edit-membre"
                                        data-id="{{ $m->id }}"
                                        data-donnees="{{ json_encode([
                                            'lien_parente' => $m->lien_parente,
                                            'nom' => $m->nom,
                                            'prenoms' => $m->prenoms,
                                            'date_naissance' => $m->date_naissance?->format('Y-m-d'),
                                            'lieu_naissance' => $m->lieu_naissance,
                                            'est_enfant_declare' => $m->est_enfant_declare,
                                            'est_a_charge' => $m->est_a_charge,
                                            'date_debut_charge' => $m->date_debut_charge?->format('Y-m-d'),
                                        ]) }}"
                                        title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('personnel.salaries.foyer.archiver', [$salarie, $m]) }}"
                                      class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-action" title="Archiver">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">
                            Aucun membre du foyer enregistré.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('js')
<script>
$(function () {
    'use strict';

    const URL_STORE  = "{{ route('personnel.salaries.foyer.store', $salarie) }}";
    const URL_UPDATE = "{{ route('personnel.salaries.foyer.update', [$salarie, '__ID__']) }}";

    function ouvrirModal(id, donnees) {
        const $modal = $('#modal-membre-foyer');
        const $form  = $('#form-membre-foyer');

        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        if (id) {
            $('#titre-modal-membre').text('Modifier un membre');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (!$el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v !== null && v !== undefined ? v : '');
            });
        } else {
            $('#titre-modal-membre').text('Ajouter un membre');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $(document).on('click', '.js-nouveau-membre', function () { ouvrirModal(null, null); });
    $(document).on('click', '.js-edit-membre', function () {
        ouvrirModal($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-membre-foyer').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn  = $form.find('button[type="submit"]');
        const texte = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-membre-foyer')).hide();
            window.showToastThenReload(r.message || 'Enregistré.');
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
            $btn.prop('disabled', false).html(texte);
        });
    });
});
</script>
@endpush
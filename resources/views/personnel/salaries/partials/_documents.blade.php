<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-folder-open"></i> Documents</strong>
        @can('permission', 'salaries.manage')
            <button type="button" class="btn btn-sm btn-primary js-nouveau-document">
                <i class="fas fa-plus"></i> Ajouter
            </button>
        @endcan
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Date document</th>
                    <th>Expiration</th>
                    <th>État</th>
                    <th>Observations</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salarie->documents as $d)
                    <tr>
                        <td>{{ $d->type_document }}</td>
                        <td>{{ $d->date_document?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $d->date_expiration?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @if(! $d->actif)
                                <span class="badge bg-secondary">Archivé</span>
                            @elseif($d->estExpire())
                                <span class="badge bg-danger">Expiré</span>
                            @elseif($d->expireBientot(30))
                                <span class="badge bg-warning text-dark">À renouveler</span>
                            @else
                                <span class="badge bg-success">Actif</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($d->observations, 40) ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('personnel.salaries.documents.voir', [$salarie, $d]) }}"
                               target="_blank" class="btn btn-sm btn-action" title="Télécharger">
                                <i class="fas fa-download"></i>
                            </a>
                            @can('permission', 'salaries.manage')
                                <button type="button" class="btn btn-sm btn-action js-renouveler-document"
                                        data-id="{{ $d->id }}"
                                        data-type="{{ $d->type_document }}"
                                        title="Renouveler">
                                    <i class="fas fa-sync"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('personnel.salaries.documents.destroy', [$salarie, $d]) }}"
                                      class="d-inline form-confirm-delete"
                                      data-confirm-title="Supprimer ce document ?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-action text-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Aucun document.</td>
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

    const URL_STORE = "{{ route('personnel.salaries.documents.store', $salarie) }}";

    $(document).on('click', '.js-nouveau-document', function () {
        const $modal = $('#modal-document');
        const $form = $('#form-document');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $('#titre-modal-document').text('Ajouter un document');
        $form.attr('action', URL_STORE);
        $('#modal-document').data('mode', 'creation');
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    $(document).on('click', '.js-renouveler-document', function () {
        const $modal = $('#modal-document');
        const $form = $('#form-document');
        const id = $(this).data('id');
        const type = $(this).data('type');

        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $('#titre-modal-document').text('Renouveler : ' + type);
        $form.attr('action', "{{ route('personnel.salaries.documents.renouveler', [$salarie, '__ID__']) }}".replace('__ID__', id));
        $('#modal-document').data('mode', 'renouvellement');
        $form.find('[name="type_document"]').val(type);
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    $('#form-document').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
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
            bootstrap.Modal.getInstance(document.getElementById('modal-document')).hide();
            window.showToastThenReload(r.message || 'Document enregistré.');
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
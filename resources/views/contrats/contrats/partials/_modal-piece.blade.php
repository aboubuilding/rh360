<div class="modal fade" id="modal-piece" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-piece" enctype="multipart/form-data"
              action="{{ route('contrats.contrats.pieces.store', $contrat) }}">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une pièce justificative</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <x-field name="objet" label="Objet" type="select" required
                             :options="\App\Domain\Contrats\Enums\ObjetPieceContrat::options()" />
                    <x-field name="libelle" label="Libellé (optionnel)" />
                    <x-field name="fichier" label="Fichier" type="file" required
                             help="PDF, PNG, JPG — 8 Mo max." />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Envoyer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-nouvelle-piece', function () {
        const $modal = $('#modal-piece');
        const $form = $('#form-piece');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    });

    $('#form-piece').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Envoi...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-piece')).hide();
            window.showToastThenReload(r.message || 'Pièce ajoutée.');
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
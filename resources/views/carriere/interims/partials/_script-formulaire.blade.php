<script>
// Envoi AJAX d'un formulaire d'intérim (pièces jointes comprises) avec affichage des erreurs 422.
function soumettreFormulaireInterim(selecteur, succes) {
    $(selecteur).on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $form.find('.alert-erreur-interim').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(succes)
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    if ($el.length) {
                        $el.addClass('is-invalid');
                        $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    } else {
                        $form.prepend('<div class="alert alert-danger alert-erreur-interim">' + messages[0] + '</div>');
                    }
                });
                window.showToast('Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
}
</script>

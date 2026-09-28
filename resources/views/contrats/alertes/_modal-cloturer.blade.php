<div class="modal fade" id="modal-cloturer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-cloturer">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-cloturer">Clôturer l'alerte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <x-field name="note_cloture" label="Note de clôture (optionnel)" type="textarea" />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Clôturer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
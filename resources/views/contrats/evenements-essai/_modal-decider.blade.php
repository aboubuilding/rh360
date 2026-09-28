<div class="modal fade" id="modal-decider-essai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-decider-essai">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-decider">Décision</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <x-field name="note_decision" label="Note de décision" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success" id="btn-decider">
                        <i class="fas fa-check"></i> Valider
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-valider" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-valider">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Validation DRH</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Vous allez valider le mouvement <strong>{{ $mouvement->numero_mouvement }}</strong>.</p>
                    <x-field name="note_validation" label="Note de validation (optionnel)" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-double"></i> Valider
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
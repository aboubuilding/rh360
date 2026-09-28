<div class="modal fade" id="modal-auto-evaluation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-auto-evaluation">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Auto-évaluation du salarié</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="note_auto_evaluation" id="note_auto_evaluation"
                             label="Note d'auto-évaluation (sur 20)" type="number" step="0.5"
                             min="0" max="20" required />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-retenir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-retenir">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Retenir le candidat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Le candidat passera à l'étape « Offre » et sera marqué comme <strong>retenu</strong>.</p>
                    <x-field name="commentaire" label="Commentaire (optionnel)" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Retenir</button>
                </div>
            </div>
        </form>
    </div>
</div>
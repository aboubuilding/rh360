<div class="modal fade" id="modal-annuler" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-annuler">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Annuler l'événement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="motif_annulation" label="Motif d'annulation" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Confirmer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
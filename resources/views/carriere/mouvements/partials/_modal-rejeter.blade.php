<div class="modal fade" id="modal-rejeter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-rejeter">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Rejeter le mouvement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Cette action est définitive pour ce mouvement.
                    </div>
                    <x-field name="motif" label="Motif de rejet" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
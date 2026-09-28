<div class="modal fade" id="modal-revoquer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-revoquer">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Révoquer l'habilitation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle"></i>
                        La révocation est définitive. Un renouvellement sera nécessaire.
                    </div>
                    <x-field name="motif" label="Motif de révocation" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Révoquer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
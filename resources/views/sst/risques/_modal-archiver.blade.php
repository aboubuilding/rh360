<div class="modal fade" id="modal-archiver" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-archiver">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Archiver le risque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle"></i>
                        L'archivage est tracé. Le risque ne sera plus actif mais restera consultable.
                    </div>
                    <x-field name="motif" label="Motif d'archivage" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-archive"></i> Archiver
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
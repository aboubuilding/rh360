<div class="modal fade" id="modal-cloturer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-cloturer">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Clôturer l'événement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i>
                        La clôture enregistre la synthèse et empêche toute modification ultérieure.
                    </div>
                    <x-field name="synthese_cloture" label="Synthèse de clôture" type="textarea" required
                             help="Obligatoire. Minimum 10 caractères." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-flag-checkered"></i> Clôturer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
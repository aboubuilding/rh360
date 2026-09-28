<div class="modal fade" id="modal-cloturer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-cloturer">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Clôturer l'intérim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Le salarié reprendra son poste permanent. Cette action est définitive.
                    </div>
                    <x-field name="date_fin_reelle" label="Date de fin réelle" type="date" required
                             :value="now()->format('Y-m-d')" />
                    <x-field name="motif_cloture" label="Motif (optionnel)" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-dark">
                        <i class="fas fa-flag-checkered"></i> Clôturer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
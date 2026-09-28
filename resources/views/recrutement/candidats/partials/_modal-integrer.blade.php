<div class="modal fade" id="modal-integrer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-integrer">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Intégrer le candidat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i>
                        Le candidat sera marqué comme intégré. Vous pourrez ensuite créer son dossier salarié.
                    </div>
                    <x-field name="date_integration" label="Date d'intégration" type="date" required
                             :value="now()->format('Y-m-d')" />
                    <x-field name="observations" label="Observations" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Intégrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
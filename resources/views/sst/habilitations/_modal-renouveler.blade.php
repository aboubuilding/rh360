<div class="modal fade" id="modal-renouveler" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-renouveler">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Renouveler l'habilitation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i>
                        L'ancienne habilitation passera au statut « Expirée » et une nouvelle sera créée.
                    </div>

                    <x-field name="date_debut" label="Nouvelle date de début" type="date" required
                             :value="now()->format('Y-m-d')" />
                    <x-field name="date_fin" label="Nouvelle date de fin" type="date" required
                             :value="now()->addYear()->format('Y-m-d')" />
                    <x-field name="date_revue" label="Date de revue" type="date" />
                    <x-field name="emetteur" label="Émetteur" />
                    <x-field name="reference_decision" label="Référence décision" />
                    <x-field name="reference_formation" label="Référence formation" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-sync"></i> Renouveler
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
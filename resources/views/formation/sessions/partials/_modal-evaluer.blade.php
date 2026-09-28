<div class="modal fade" id="modal-evaluer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-evaluer">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-evaluer">Évaluer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="statut_presence" id="statut_presence" label="Présence" type="select" required
                                     :options="\App\Domain\Formation\Enums\StatutPresenceParticipant::options()" />
                        </div>
                        <div class="col-md-3">
                            <x-field name="score_avant" id="score_avant" label="Score avant (/100)" type="number" step="0.01" />
                        </div>
                        <div class="col-md-3">
                            <x-field name="score_apres" id="score_apres" label="Score après (/100)" type="number" step="0.01" />
                        </div>

                        <div class="col-md-4">
                            <x-field name="note_satisfaction" id="note_satisfaction" label="Satisfaction (/5)"
                                     type="number" step="0.1" />
                        </div>
                        <div class="col-md-8">
                            <x-field name="reference_attestation" id="reference_attestation" label="Référence attestation" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="commentaire_evaluation" id="commentaire_evaluation"
                                     label="Commentaire d'évaluation" type="textarea" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
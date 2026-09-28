<div class="modal fade" id="modal-evaluer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-evaluer">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle évaluation du risque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="date_evaluation" label="Date d'évaluation" type="date" required
                                     :value="now()->format('Y-m-d')" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="motif" label="Motif" type="select"
                                     :value="'periodique'"
                                     :options="['initial' => 'Initial', 'periodique' => 'Périodique', 'post_incident' => 'Post-incident', 'changement' => 'Changement', 'revision' => 'Révision']" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="gravite" id="gravite" label="Gravité (1 à 5)" type="select" required
                                     :options="[1=>'1 — Négligeable', 2=>'2 — Faible', 3=>'3 — Modérée', 4=>'4 — Grave', 5=>'5 — Très grave']" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="probabilite" id="probabilite" label="Probabilité (1 à 5)" type="select" required
                                     :options="[1=>'1 — Très improbable', 2=>'2 — Improbable', 3=>'3 — Possible', 4=>'4 — Probable', 5=>'5 — Très probable']" />
                        </div>

                        <div class="col-md-12 text-center my-2">
                            <div class="text-muted small mb-1">Score calculé (gravité × probabilité)</div>
                            <div id="apercu-score">—</div>
                        </div>

                        <div class="col-md-12">
                            <x-field name="mesures_existantes" label="Mesures de prévention existantes" type="textarea" required />
                        </div>

                        <div class="col-md-12">
                            <x-field name="justification" label="Justification de l'évaluation" type="textarea" required />
                        </div>

                        <div class="col-md-6">
                            <x-field name="version_methode" label="Version de la méthode" :value="'v1'" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="date_prochaine_revue" label="Prochaine revue (optionnel)" type="date"
                                     help="Si vide, suggérée automatiquement selon le niveau." />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer l'évaluation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
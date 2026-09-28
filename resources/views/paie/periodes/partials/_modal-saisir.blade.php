<div class="modal fade" id="modal-saisir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-saisir">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Saisir un élément variable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="salarie_id" label="Salarié" type="select" required
                                     :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="rubrique_id" label="Rubrique" type="select" required
                                     :options="$rubriquesVariables->mapWithKeys(fn($r) => [$r->id => $r->code.' — '.$r->nom])->all()" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="quantite" label="Quantité" type="number" step="0.01" :value="1" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="taux" label="Taux (%)" type="number" step="0.01" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="montant" label="Montant (FCFA)" type="number" step="0.01" required />
                        </div>
                        <div class="col-md-12">
                            <x-field name="observations" label="Observations" type="textarea" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-objectif" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-objectif" action="{{ route('performance.objectifs.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvel objectif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="campagne_id" label="Campagne" type="select" required
                                     :options="$campagnes->pluck('intitule', 'id')->all()" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="salarie_id" label="Salarié" type="select" required
                                     :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet])->all()" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="intitule" label="Intitulé de l'objectif" required />
                        </div>

                        <div class="col-md-6">
                            <x-field name="indicateur" label="Indicateur de mesure" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="cible" label="Cible à atteindre" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="ponderation" label="Pondération" type="number" step="0.1"
                                     required :value="1" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="date_echeance" label="Échéance" type="date" />
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
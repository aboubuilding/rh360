<div class="modal fade" id="modal-regle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-regle" action="{{ route('contrats.regles.store') }}">
            @csrf
            <input type="hidden" name="_method" id="method-regle" value="POST">
            <input type="hidden" name="id" id="id-regle">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-regle">Nouvelle règle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="type_contrat" label="Type de contrat" type="select" required
                                     :options="$types" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="categorie_id" label="Catégorie" type="select" required
                                     :options="$categories->pluck('libelle', 'id')->all()" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="date_effet" label="Date d'effet" type="date" required
                                     :value="now()->format('Y-m-d')" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="parametres[duree_max_essai_jours]" label="Durée max. essai (jours)"
                                     type="number" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="parametres[duree_max_essai_renouvellement_jours]"
                                     label="Durée max. renouvellement (jours)" type="number" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="parametres[nombre_renouvellements_max]" label="Nombre max. renouvellements"
                                     type="number" :value="0" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="parametres[plafond_remuneration]" label="Plafond rémunération (FCFA)"
                                     type="number" />
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
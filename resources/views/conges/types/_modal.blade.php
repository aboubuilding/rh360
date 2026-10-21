<div class="modal fade" id="modal-type" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-type" action="{{ route('conges.types.store') }}">
            @csrf
            <input type="hidden" name="_method" id="method-type" value="POST">
            <input type="hidden" name="id" id="id-type">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-type">Nouveau type de congé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <x-field name="code" label="Code" required />
                        </div>
                        <div class="col-md-8">
                            <x-field name="nom" label="Nom" required />
                        </div>

                        <div class="col-md-6">
                            <x-field name="categorie" label="Catégorie" type="select" required
                                     :options="$categories" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="unite" label="Unité" type="select" required
                                     :options="\App\Domain\Conges\Enums\UniteConge::options()" />
                        </div>

                        <div class="col-md-4">
                            <x-field name="droit_annuel" label="Droit annuel" type="number" step="0.5"
                                     :value="0" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="duree_max" label="Durée maximale" type="number" step="0.5" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="portee_duree_max" label="Portée durée max"
                                     help="Ex. par événement, par an" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="traitement_salarial" label="Traitement salarial" type="select" required
                                     :options="\App\Domain\Conges\Enums\TraitementSalarial::options()" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="impact_conge_annuel" label="Impact congé annuel" type="select" required
                                     :options="\App\Domain\Conges\Enums\ImpactCongeAnnuel::options()" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="impact_anciennete" label="Impact ancienneté" type="select" required
                                     :options="\App\Domain\Conges\Enums\ImpactAnciennete::options()" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="delai_justification_jours" label="Délai justification (jours)" type="number" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="reference_legale" label="Référence légale" />
                        </div>

                        <div class="col-md-4">
                            <x-field name="remunere" label="Rémunéré" type="checkbox" :value="true" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="justificatif_requis" label="Justificatif requis" type="checkbox" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="autorisation_prealable_requise" label="Autorisation préalable" type="checkbox" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="actif" label="Actif" type="checkbox" :value="true" />
                        </div>

                        <div class="col-md-12"><hr><h6 class="fw-bold">Règles de la demande</h6></div>

                        <div class="col-md-6">
                            <x-field name="famille" label="Famille" type="select"
                                     :options="[null => '—'] + \App\Domain\Conges\Enums\FamilleDemande::options()" />
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <x-field name="de_droit" label="Absence de droit (accordée dès que le justificatif est conforme)" type="checkbox" />
                        </div>

                        <div class="col-md-4">
                            <x-field name="delai_depot" label="Délai de dépôt avant l'absence" type="number" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="delai_depot_unite" label="Unité du délai" type="select"
                                     :options="[null => '—'] + \App\Domain\Conges\Models\TypeConge::UNITES_DELAI_DEPOT" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="delai_information_heures" label="Information de l'employeur (heures)" type="number" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="delai_justification_depart" label="Délai de justification compté depuis" type="select"
                                     :options="[null => '—'] + \App\Domain\Conges\Models\TypeConge::DEPARTS_JUSTIFICATION" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="anciennete_min_mois" label="Ancienneté minimale (mois)" type="number" />
                        </div>

                        <div class="col-md-3">
                            <x-field name="plafond_valeur" label="Plafond" type="number" step="0.5" />
                        </div>
                        <div class="col-md-3">
                            <x-field name="plafond_unite" label="Unité" type="select"
                                     :options="[null => '—'] + \App\Domain\Conges\Models\TypeConge::UNITES_PLAFOND" />
                        </div>
                        <div class="col-md-3">
                            <x-field name="plafond_periode" label="Période" type="select"
                                     :options="[null => '—'] + \App\Domain\Conges\Enums\PeriodePlafond::options()" />
                        </div>
                        <div class="col-md-3">
                            <x-field name="plafond_base" label="Appliqué" type="select"
                                     :options="[null => '—'] + \App\Domain\Conges\Enums\BasePlafond::options()" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="prise_continue_autorisee" label="Peut être pris d'un seul tenant" type="checkbox" :value="true" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="report_credit_autorise" label="Le crédit non utilisé se reporte" type="checkbox" :value="true" />
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
<div class="modal fade" id="modal-rubrique" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-rubrique" action="{{ route('paie.rubriques.store') }}">
            @csrf
            <input type="hidden" name="_method" id="method-rubrique" value="POST">
            <input type="hidden" name="id" id="id-rubrique">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-rubrique">Nouvelle rubrique</h5>
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

                        <div class="col-md-12">
                            <x-field name="description" label="Description" type="textarea" rows="2" />
                        </div>

                        {{-- Effet sur le salaire : connu avant d'ajouter la rubrique à un modèle ou à un bulletin. --}}
                        <div class="col-md-8">
                            <x-field name="effet" label="Effet sur le salaire" type="select" required
                                     :options="[
                                         'gain' => '➕ Gain : ajoute au salaire brut',
                                         'frais' => '➕ Remboursement de frais : ajouté au net, hors brut et hors cotisations',
                                         'retenue' => '➖ Retenue : retranchée du net à payer',
                                         'cotisation' => '➖ Cotisation salariale : retranchée du salaire',
                                     ]" />
                            <div class="small mt-n2 mb-2" id="aide-effet"></div>
                        </div>
                        <div class="col-md-4 js-champ-type-retenue">
                            <x-field name="type_retenue" label="Cas de retenue autorisé" type="select"
                                     :options="[null => '—'] + \App\Domain\Paie\Enums\TypeRetenue::options()" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="recurrence" label="Récurrence" type="select" required
                                     :options="\App\Domain\Paie\Enums\RecurrenceRubrique::options()" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="mode_calcul" label="Mode de calcul" type="select" required
                                     :options="\App\Domain\Paie\Enums\ModeCalculRubrique::options()" />
                        </div>

                        <div class="col-md-4">
                            <x-field name="taux" label="Taux (%)" type="number" step="0.01" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="montant_defaut" label="Montant par défaut" type="number" step="0.01" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="traitement_fiscal" label="Traitement fiscal" type="select" required
                                     :options="\App\Domain\Paie\Enums\TraitementFiscal::options()" />
                        </div>

                        <div class="col-md-4">
                            <x-field name="pourcentage_imposable" label="% imposable" type="number" step="0.01"
                                     :value="100" />
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <div class="form-check form-check-inline">
                                <input type="hidden" name="imposable" value="0">
                                <input type="checkbox" name="imposable" id="imposable" value="1"
                                       class="form-check-input" checked>
                                <label class="form-check-label" for="imposable">Imposable</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="hidden" name="soumis_cotisation" value="0">
                                <input type="checkbox" name="soumis_cotisation" id="soumis_cotisation" value="1"
                                       class="form-check-input" checked>
                                <label class="form-check-label" for="soumis_cotisation">Soumis à cotisation</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input type="hidden" name="actif" value="0">
                                <input type="checkbox" name="actif" id="actif" value="1"
                                       class="form-check-input" checked>
                                <label class="form-check-label" for="actif">Actif</label>
                            </div>
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

@push('js')
<script>
$(function () {
    const aides = {
        gain: ['text-success', 'Le montant s\'ajoute au salaire brut : il entre dans les cotisations et l\'impôt selon les cases cochées.'],
        frais: ['text-info', 'Le montant est versé avec le salaire mais reste hors du brut, des cotisations, de l\'impôt et de l\'allocation de congé.'],
        retenue: ['text-danger', 'Le montant est retranché du net à payer. Seuls les cas prévus par la loi sont admis : choisissez-en un.'],
        cotisation: ['text-warning', 'Le montant est retranché du salaire au titre d\'une cotisation salariale.'],
    };
    function majEffet() {
        const effet = $('#form-rubrique [name="effet"]').val();
        const [classe, texte] = aides[effet] || ['', ''];
        $('#aide-effet').attr('class', 'small mt-n2 mb-2 ' + classe).text(texte);
        $('.js-champ-type-retenue').toggle(effet === 'retenue');
    }
    $(document).on('change', '#form-rubrique [name="effet"]', majEffet);
    $(document).on('shown.bs.modal', '#modal-rubrique', majEffet);
    majEffet();
});
</script>
@endpush
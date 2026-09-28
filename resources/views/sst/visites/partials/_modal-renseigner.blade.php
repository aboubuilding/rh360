<div class="modal fade" id="modal-renseigner" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-renseigner">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="titre-modal-renseigner">Renseigner la visite</h5>
                        <small class="text-muted" id="sous-titre-visite"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="fas fa-lock"></i>
                        Les informations médicales sont strictement confidentielles. Ne saisissez que les
                        éléments administratifs (date, aptitude, restrictions).
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="date_realisation" label="Date de réalisation" type="date" required
                                     :value="now()->format('Y-m-d')" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="aptitude" label="Aptitude" type="select" required
                                     :options="\App\Domain\Sst\Enums\AptitudeMedicale::options()" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="reference_avis" label="Référence de l'avis médical" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="prestataire" label="Prestataire / médecin" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="restrictions" label="Restrictions / aménagements" type="textarea"
                                     help="Uniquement les restrictions administratives d'aptitude." />
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
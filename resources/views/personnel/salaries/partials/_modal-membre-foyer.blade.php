<div class="modal fade" id="modal-membre-foyer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-membre-foyer" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-membre">Ajouter un membre du foyer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="lien_parente" label="Lien de parenté" type="select" required
                                     :options="\App\Domain\Personnel\Enums\LienParente::options()" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="date_naissance" label="Date de naissance" type="date" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="nom" label="Nom" required />
                        </div>
                        <div class="col-md-6">
                            <x-field name="prenoms" label="Prénoms" required />
                        </div>
                        <div class="col-md-6">
                            <x-field name="lieu_naissance" label="Lieu de naissance" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="photo" label="Photo" type="file" help="PNG/JPG — 2 Mo max." />
                        </div>
                        <div class="col-md-6">
                            <x-field name="acte_naissance" label="Acte de naissance (PDF)" type="file"
                                     help="PDF, PNG, JPG — 8 Mo max." />
                        </div>
                        <div class="col-md-6">
                            <x-field name="date_debut_charge" label="Début de charge" type="date" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="est_enfant_declare" label="Enfant déclaré" type="checkbox" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="est_a_charge" label="À charge" type="checkbox" />
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
<div class="modal fade" id="modal-document" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-document" enctype="multipart/form-data">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-document">Ajouter un document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <x-field name="type_document" label="Type de document" type="select" required
                             :options="\App\Domain\Personnel\Enums\TypeDocumentSalarie::options()" />
                    <x-field name="fichier" label="Fichier" type="file" required
                             help="PDF, PNG, JPG — 8 Mo max." />
                    <x-field name="date_document" label="Date du document" type="date" />
                    <x-field name="date_expiration" label="Date d'expiration" type="date" />
                    <x-field name="observations" label="Observations" type="textarea" />
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
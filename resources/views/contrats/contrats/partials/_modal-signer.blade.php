<div class="modal fade" id="modal-signer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-signer" enctype="multipart/form-data">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Référencer la signature</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <x-field name="date_signature" label="Date de signature réelle" type="date" required
                             :value="now()->format('Y-m-d')" />
                    <x-field name="reference_signee" label="Référence du document signé" required
                             help="Ex. numéro d'ordre, référence de l'acte..." />
                    <x-field name="piece" label="Pièce signée (PDF/PNG/JPG)" type="file"
                             help="8 Mo max. Sera enregistrée comme pièce justificative." />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-signature"></i> Référencer la signature
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

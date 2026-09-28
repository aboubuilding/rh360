<div class="modal fade" id="modal-retourner" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-retourner">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Retourner au brouillon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>Le contrat <strong>{{ $contrat->reference }}</strong> sera renvoyé en brouillon.</p>
                    <x-field name="motif" label="Motif" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo"></i> Retourner au brouillon
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
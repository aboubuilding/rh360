<div class="modal fade" id="modal-valider" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-valider">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Valider le contrat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p>Le contrat <strong>{{ $contrat->reference }}</strong> passera au statut <strong>Validé</strong>.</p>
                    <x-field name="note_derogation" label="Note de dérogation (optionnel)" type="textarea"
                             help="À renseigner uniquement en cas d'acceptation explicite d'une dérogation." />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Valider
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
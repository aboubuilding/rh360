<div class="modal fade" id="modal-ajuster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-ajuster">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-ajuster">Ajuster le solde</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Un ajustement positif ajoute des jours ; un négatif en retire.
                        La valeur est cumulative et le motif est obligatoire.
                    </div>
                    <x-field name="ajustement" label="Ajustement (jours)" type="number" step="0.5" required />
                    <x-field name="motif" label="Motif" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
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
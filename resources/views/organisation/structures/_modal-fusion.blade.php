<div class="modal fade" id="modal-fusion-structure" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-fusion-structure">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fusionner une structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        La structure <strong id="fusion-source-nom"></strong> sera absorbée par la structure cible.
                        Ses enfants et postes seront rattachés à la cible.
                    </p>
                    <x-field name="cible_id" label="Structure cible" type="select" required
                             :options="$structures->pluck('nom', 'id')->all()" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-code-branch"></i> Fusionner</button>
                </div>
            </div>
        </form>
    </div>
</div>
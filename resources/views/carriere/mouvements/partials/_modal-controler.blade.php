<div class="modal fade" id="modal-controler" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-controler">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contrôle RH</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Vous allez contrôler le mouvement <strong>{{ $mouvement->numero_mouvement }}</strong>.</p>
                    <x-field name="observations" label="Observations (optionnel)" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check"></i> Valider le contrôle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
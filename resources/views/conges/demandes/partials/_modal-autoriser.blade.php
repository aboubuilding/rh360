<div class="modal fade" id="modal-autoriser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-autoriser">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Autoriser la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>La demande <strong>{{ $demande->numero_demande }}</strong> sera autorisée.</p>
                    <x-field name="reference_acte" label="Référence de l'acte (optionnel)" />
                    <x-field name="date_acte" label="Date de l'acte (optionnel)" type="date" :value="now()->format('Y-m-d')" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Autoriser
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
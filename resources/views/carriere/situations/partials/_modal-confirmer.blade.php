<div class="modal fade" id="modal-confirmer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-confirmer"
              action="{{ route('carriere.situations.confirmer-fiabilite', $situation) }}">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la fiabilité</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>La situation passera au statut <strong>Confirmé</strong> et l'historique deviendra <strong>Complet</strong>.</p>
                    <x-field name="note" label="Note (optionnel)" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Confirmer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
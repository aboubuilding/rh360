<div class="modal fade" id="modal-soumettre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-soumettre">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Soumettre le mouvement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Le mouvement <strong>{{ $mouvement->numero_mouvement }}</strong> sera transmis pour contrôle.</p>
                    <x-field name="date_eligibilite" label="Date d'éligibilité (optionnel)" type="date"
                             :value="$mouvement->date_eligibilite?->format('Y-m-d')" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Soumettre
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
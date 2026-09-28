<div class="modal fade" id="modal-valider" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-valider" action="{{ route('paie.periodes.valider', $periode) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Validation définitive</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention.</strong> La validation est <strong>définitive</strong>.
                        Aucune saisie, aucun recalcul ne sera plus possible.
                        Seul un super administrateur pourra exceptionnellement rouvrir la période.
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="confirmation" id="confirmation" value="1" required>
                        <label class="form-check-label" for="confirmation">
                            Je confirme la validation définitive de la période
                            <strong>{{ $periode->libelle }}</strong>
                            ({{ $periode->bulletins->count() }} bulletins).
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Valider définitivement
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
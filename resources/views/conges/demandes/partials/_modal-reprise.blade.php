<div class="modal fade" id="modal-reprise" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-reprise">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la reprise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Confirmez la reprise effective du salarié.</p>
                    <x-field name="date_reprise_reelle" label="Date de reprise réelle (optionnel)" type="date"
                             :value="$demande->date_reprise?->format('Y-m-d')"
                             help="Si vide, utilise la date prévue." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-flag-checkered"></i> Confirmer la reprise
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
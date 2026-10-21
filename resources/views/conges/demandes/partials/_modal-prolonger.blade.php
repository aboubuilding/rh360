<div class="modal fade" id="modal-prolonger" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-prolonger">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prolonger le délai du justificatif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="echeance" label="Nouvelle échéance" type="date" required />
                    <x-field name="motif" label="Cas de force majeure" type="textarea" required />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-plus"></i> Prolonger</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-rappel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-rappel">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rappeler le salarié pendant son congé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Le congé est prolongé des jours travaillés : la date de reprise est repoussée d'autant.</p>
                    <x-field name="du" label="Travaillé du" type="date" required />
                    <x-field name="au" label="Au" type="date" required />
                    <x-field name="motif" label="Motif du rappel" type="textarea" required />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-phone"></i> Enregistrer le rappel</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-participant" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-participant">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un participant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="salarie_id" label="Salarié" type="select" required
                             :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Ajouter</button>
                </div>
            </div>
        </form>
    </div>
</div>
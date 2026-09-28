<div class="modal fade" id="modal-changer-etape" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-changer-etape">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changer d'étape</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="etape" label="Nouvelle étape" type="select" required
                             :options="$etapesPossibles->mapWithKeys(fn($e) => [$e->value => $e->libelle()])->all()" />
                    <x-field name="score" label="Score (0-100)" type="number" min="0" max="100" :value="$candidat->score" />
                    <x-field name="date_entretien" label="Date entretien" type="date"
                             :value="$candidat->date_entretien?->format('Y-m-d')" />
                    <x-field name="observations" label="Observations" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal-realiser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-realiser">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Réaliser l'entretien</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="note_manager" label="Note du manager (sur 20)" type="number"
                                     step="0.5" min="0" max="20" required :value="$entretien->note_manager" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="date_entretien" label="Date de l'entretien" type="date"
                                     :value="$entretien->date_entretien?->format('Y-m-d') ?? now()->format('Y-m-d')" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="points_forts" label="Points forts" type="textarea" :value="$entretien->points_forts" />
                        </div>
                        <div class="col-md-12">
                            <x-field name="besoins_developpement" label="Besoins de développement" type="textarea"
                                     :value="$entretien->besoins_developpement" />
                        </div>
                        <div class="col-md-12">
                            <x-field name="commentaire_manager" label="Commentaire du manager" type="textarea"
                                     :value="$entretien->commentaire_manager" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="action_amelioration" label="Plan d'amélioration" type="textarea"
                                     :value="$entretien->action_amelioration" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="date_echeance_amelioration" label="Échéance du plan" type="date"
                                     :value="$entretien->date_echeance_amelioration?->format('Y-m-d')" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
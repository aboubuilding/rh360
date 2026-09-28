<div class="modal fade" id="modal-anciennete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-anciennete">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-anciennete">Règle d'ancienneté</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="annees_min" label="Années minimum" type="number" required :value="2" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="mode_base" label="Mode de base" required :value="'salary_base'" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="taux_initial" label="Taux initial (%)" type="number" step="0.01" required :value="3" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="increment_annuel" label="Incrément annuel (%)" type="number" step="0.01" required :value="1" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="taux_max" label="Taux max (%)" type="number" step="0.01" required :value="15" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="debut_effet" label="Début d'effet" type="date" required :value="now()->format('Y-m-d')" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="fin_effet" label="Fin d'effet" type="date" />
                        </div>
                        <div class="col-md-12">
                            <x-field name="reference_legale" label="Référence légale" />
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="hidden" name="actif" value="0">
                                <input type="checkbox" name="actif" id="anc-actif" value="1" class="form-check-input" checked>
                                <label class="form-check-label" for="anc-actif">Règle active</label>
                            </div>
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
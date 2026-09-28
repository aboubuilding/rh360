<div class="modal fade" id="modal-operation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-operation"
              action="{{ route('sst.epi.operations.store', $dotation) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle opération sur la dotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <x-field name="nature" label="Nature de l'opération" type="select" required
                             :options="$natures" />
                    <x-field name="date_evenement" label="Date de l'opération" type="date" required
                             :value="now()->format('Y-m-d')" />
                    <x-field name="quantite" label="Quantité concernée" type="number" min="1" />
                    <x-field name="date_prochaine_verification" label="Prochaine vérification (si applicable)"
                             type="date" />
                    <x-field name="intervenant" label="Intervenant" :value="auth()->user()->nom_complet" />
                    <x-field name="resultat" label="Résultat / observations" type="textarea" />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
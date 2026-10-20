<div class="modal fade" id="modal-avantage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-avantage" action="{{ route('carriere.interims.avantages.store', $interim) }}" enctype="multipart/form-data">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Avantage du poste occupé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        Les avantages du poste occupé ne sont jamais calculés automatiquement : ils résultent d'un accord
                        entre les parties.
                    </div>
                    <x-field name="rubrique_id" label="Rubrique de paie" type="select" required
                             :options="[null => '—'] + $rubriques->pluck('nom', 'id')->all()" />
                    <x-field name="montant_mensuel" label="Montant mensuel (FCFA)" type="number" required />
                    <x-field name="date_debut" label="Date de début" type="date" required :value="now()->format('Y-m-d')" />
                    <x-field name="date_fin" label="Date de fin" type="date" />
                    <x-field name="reference_accord" label="Référence de l'accord" required />
                    <x-field name="justificatif" label="Justificatif de l'accord" type="file" required />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>

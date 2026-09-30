<div class="modal fade" id="modal-classe" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-classe" action="{{ route('classification.classes.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-classe">Nouvelle classe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="referentiel_id" label="Référentiel" type="select" required
                             :options="$referentiels->pluck('nom', 'id')->all()" />
                    <x-field name="code" label="Code" required />
                    <x-field name="libelle" label="Libellé" required />
                    <x-field name="ordre" label="Ordre" type="number" :value="100" />
                    <x-field name="actif" label="Actif" type="checkbox" :value="true" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
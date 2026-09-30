<div class="modal fade" id="modal-type-structure" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-type-structure" action="{{ route('organisation.types-structures.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-type-structure">Nouveau type de structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="code" label="Code" required help="Ex. DG, DIR, DEP..." />
                    <x-field name="nom" label="Nom" required />
                    <x-field name="ordre" label="Ordre d'affichage" type="number" :value="100" />
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
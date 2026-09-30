<div class="modal fade" id="modal-structure" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-structure" action="{{ route('organisation.structures.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-structure">Nouvelle structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="type_structure_id" label="Type de structure" type="select" required
                                     :options="$types->pluck('nom', 'id')->all()" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="parent_id" label="Structure parente" type="select"
                                     :options="[null => '— Aucune —'] + $parents->pluck('nom', 'id')->all()" />
                        </div>
                        <div class="col-md-4"><x-field name="code" label="Code" required /></div>
                        <div class="col-md-8"><x-field name="nom" label="Nom" required /></div>
                        <div class="col-md-6"><x-field name="localisation" label="Localisation" /></div>
                        <div class="col-md-6"><x-field name="centre_cout" label="Centre de coût" /></div>
                        <div class="col-md-12"><x-field name="actif" label="Actif" type="checkbox" :value="true" /></div>
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
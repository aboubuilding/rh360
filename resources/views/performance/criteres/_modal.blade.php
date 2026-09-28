<div class="modal fade" id="modal-critere" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-critere" action="{{ route('performance.criteres.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-critere">Nouveau critère</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <x-field name="code" label="Code" required />
                        </div>
                        <div class="col-md-8">
                            <x-field name="libelle" label="Libellé" required />
                        </div>

                        <div class="col-md-6">
                            <x-field name="famille" label="Famille" type="select" required :options="$familles" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="ponderation" label="Pondération" type="number" step="0.1"
                                     required :value="1" />
                        </div>

                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="hidden" name="actif" value="0">
                                <input type="checkbox" name="actif" value="1" class="form-check-input" checked>
                                <label class="form-check-label">Critère actif</label>
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
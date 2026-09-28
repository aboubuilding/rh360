<div class="modal fade" id="modal-formation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-formation" action="{{ route('formation.formations.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-formation">Nouvelle formation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <x-field name="code" label="Code" required />
                        </div>
                        <div class="col-md-8">
                            <x-field name="intitule" label="Intitulé" required />
                        </div>

                        <div class="col-md-6">
                            <x-field name="domaine" label="Domaine" />
                        </div>
                        <div class="col-md-3">
                            <x-field name="duree_heures" label="Durée (h)" type="number" step="0.5" required />
                        </div>
                        <div class="col-md-3">
                            <x-field name="modalite" label="Modalité" type="select" required
                                     :options="$modalites" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="objectif" label="Objectif pédagogique" type="textarea" />
                        </div>

                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="hidden" name="actif" value="0">
                                <input type="checkbox" name="actif" value="1" class="form-check-input" checked>
                                <label class="form-check-label">Formation active</label>
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
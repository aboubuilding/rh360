<div class="modal fade" id="modal-poste" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-poste" action="{{ route('organisation.postes.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-poste">Nouveau poste</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <x-field name="structure_id" label="Structure de rattachement" type="select" required
                                     :options="$structures->pluck('nom', 'id')->all()" />
                        </div>
                        <div class="col-md-4"><x-field name="code" label="Code" required /></div>
                        <div class="col-md-8"><x-field name="intitule" label="Intitulé du poste" required /></div>
                        <div class="col-md-6"><x-field name="categorie" label="Catégorie" help="Ex. A, B, C..." /></div>
                        <div class="col-md-6"><x-field name="effectif_cible" label="Effectif cible" type="number" /></div>
                        <div class="col-md-6"><x-field name="categorie_classification_id" label="Catégorie de classification" type="select"
                                 :options="[null => '—'] + $categories" help="Sert au contrôle des intérims (CCIT, art. 13)." /></div>
                        <div class="col-md-6"><x-field name="travaux_dangereux" label="Travaux particulièrement dangereux (intérim d'agence interdit)" type="checkbox" /></div>
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
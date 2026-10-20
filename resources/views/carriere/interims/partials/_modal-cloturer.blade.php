<div class="modal fade" id="modal-cloturer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-cloturer" action="{{ route('carriere.interims.cloturer', $interim) }}" enctype="multipart/form-data">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">Clôturer l'intérim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Le salarié conserve son poste permanent et sa situation d'origine.
                        Après le {{ $interim->date_limite->format('d/m/Y') }}, une décision de reclassement ou de retour est obligatoire.
                    </div>
                    <x-field name="cause_fin" label="Cause de fin" type="select" required :options="[null => '—'] + $causes" />
                    <x-field name="date_fin_reelle" label="Date de fin réelle" type="date" required
                             :value="now()->format('Y-m-d')" />
                    <x-field name="piece" label="Pièce jointe" type="file" />
                    <x-field name="commentaire" label="Commentaire" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-dark"><i class="fas fa-flag-checkered"></i> Clôturer</button>
                </div>
            </div>
        </form>
    </div>
</div>

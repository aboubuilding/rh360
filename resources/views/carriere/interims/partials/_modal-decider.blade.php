<div class="modal fade" id="modal-decider" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-decider" action="{{ route('carriere.interims.decider', $interim) }}" enctype="multipart/form-data">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Décision de fin d'intérim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="decision" label="Décision" type="select" required :options="[null => '—'] + $decisions" />
                    <x-field name="date_effet" label="Date d'effet" type="date" required :value="now()->format('Y-m-d')" />

                    <div id="bloc-reclassement">
                        <x-field name="position_cible_id" label="Nouvelle position ({{ $interim->categorieOccupee?->libelle }})" type="select"
                                 :options="[null => '—'] + $positionsCible->mapWithKeys(fn($p) => [$p->id => $p->code.($p->salaire_minimum ? ' — min. '.number_format($p->salaire_minimum, 0, ',', ' ').' FCFA' : '')])->all()" />
                        <x-field name="nouveau_salaire_base" label="Nouveau salaire de base (FCFA)" type="number" />
                    </div>

                    <x-field name="piece" label="Pièce justificative" type="file" required />
                    <x-field name="commentaire" label="Commentaire" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-gavel"></i> Enregistrer la décision</button>
                </div>
            </div>
        </form>
    </div>
</div>

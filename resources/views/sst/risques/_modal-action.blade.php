<div class="modal fade" id="modal-action" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-action">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle action de prévention</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="intitule" label="Intitulé de l'action" required />
                    <x-field name="type_mesure" label="Type de mesure" type="select" required
                             :options="\App\Domain\Sst\Enums\TypeMesurePrevention::options()" />
                    <x-field name="responsable_salarie_id" label="Responsable" type="select" required
                             :options="\App\Domain\Personnel\Models\Salarie::where('actif', true)->orderBy('nom')->get()->mapWithKeys(fn($s) => [$s->id => $s->nom_complet])->all()" />
                    <x-field name="date_echeance" label="Échéance" type="date" required
                             :value="now()->addDays(30)->format('Y-m-d')" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                </div>
            </div>
        </form>
    </div>
</div>
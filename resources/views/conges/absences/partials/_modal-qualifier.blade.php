<div class="modal fade" id="modal-qualifier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-qualifier">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-qualifier">Qualifier l'absence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="qualification" label="Qualification" type="select" required
                             :options="\App\Domain\Conges\Enums\QualificationAbsence::options()" />
                    <x-field name="decision" label="Note (optionnel)" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
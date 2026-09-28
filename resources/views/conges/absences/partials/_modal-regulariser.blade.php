<div class="modal fade" id="modal-regulariser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-regulariser">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Régulariser l'absence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Le constat initial sera conservé ; la décision motivée s'y ajoute.
                    </div>
                    <x-field name="qualification" label="Qualification" type="select" required
                             :options="\App\Domain\Conges\Enums\QualificationAbsence::options()" />
                    <x-field name="decision" label="Décision motivée" type="textarea" required
                             help="Obligatoire. Minimum 5 caractères." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-balance-scale"></i> Régulariser
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
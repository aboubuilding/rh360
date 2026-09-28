<div class="modal fade" id="modal-transmettre" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-transmettre" action="{{ route('conges.absences.transmettre-paie') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transmettre à la paie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Les absences sélectionnées seront marquées comme transmises à la paie.</p>
                    <x-field name="periode_paie" label="Période de paie" required
                             :value="now()->format('Y-m')"
                             help="Format YYYY-MM" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Transmettre
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
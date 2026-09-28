<div class="modal fade" id="modal-programmer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-programmer">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Programmer le mouvement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Choisissez la date à laquelle le mouvement sera appliqué automatiquement.</p>
                    <x-field name="date_effet" label="Date d'effet" type="date" required
                             :value="$mouvement->date_effet?->format('Y-m-d') ?? now()->addDays(7)->format('Y-m-d')"
                             help="Date future obligatoire." />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-alt"></i> Programmer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
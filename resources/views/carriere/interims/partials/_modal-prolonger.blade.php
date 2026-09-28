<div class="modal fade" id="modal-prolonger" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-prolonger">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prolonger l'intérim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Fin prévue actuelle : <strong>{{ $interim->date_fin_prevue?->format('d/m/Y') ?? '—' }}</strong></p>
                    <x-field name="date_fin_prevue" label="Nouvelle date de fin prévue" type="date" required
                             :value="$interim->date_fin_prevue?->addMonth()->format('Y-m-d')" />
                    <x-field name="motif" label="Motif (optionnel)" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-clock"></i> Prolonger
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
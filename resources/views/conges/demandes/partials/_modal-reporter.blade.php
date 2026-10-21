<div class="modal fade" id="modal-reporter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-reporter">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Décaler le départ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Une date de départ fixée ne peut être avancée ni retardée de plus de 3 mois, sauf force majeure.
                        @if($demande->date_debut_fixee)
                            Première date fixée : {{ $demande->date_debut_fixee->format('d/m/Y') }}.
                        @endif
                    </p>
                    <x-field name="date_debut" label="Nouvelle date de départ" type="date" required :value="$demande->date_debut?->format('Y-m-d')" />
                    <x-field name="date_reprise" label="Nouvelle date de reprise" type="date" required :value="$demande->date_reprise?->format('Y-m-d')" />
                    <x-field name="force_majeure" label="Cas de force majeure" type="checkbox" />
                    <x-field name="motif" label="Motif" type="textarea" required />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-alt"></i> Décaler</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-prolonger" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-prolonger" action="{{ route('carriere.interims.prolonger', $interim) }}" enctype="multipart/form-data">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Prolonger l'intérim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Fin prévue actuelle : <strong>{{ $interim->date_fin_prevue->format('d/m/Y') }}</strong><br>
                        Motif : <strong>{{ $interim->motif->libelle() }}</strong> — fin au plus tard le <strong>{{ $interim->date_limite->format('d/m/Y') }}</strong>
                    </p>
                    <x-field name="date_fin_prevue" label="Nouvelle date de fin prévue" type="date" required
                             :value="$interim->date_fin_prevue->copy()->addMonth()->format('Y-m-d')" />
                    <x-field name="date_fin_motif" label="Fin du congé / date de consolidation" type="date"
                             :value="$interim->date_fin_motif" />
                    <x-field name="piece" label="Pièce justificative" type="file" required />
                    <x-field name="commentaire" label="Commentaire" type="textarea" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-clock"></i> Prolonger</button>
                </div>
            </div>
        </form>
    </div>
</div>

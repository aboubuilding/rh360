<div class="modal fade" id="modal-requalifier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-requalifier">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Requalifier la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Le justificatif n'a pas été remis conforme. L'absence sera traitée selon les paramètres
                        des demandes (absence non justifiée, ou jours déduits du congé payé pour une permission).
                    </p>
                    <x-field name="motif" label="Motif" type="textarea" required />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-dark"><i class="fas fa-exchange-alt"></i> Requalifier</button>
                </div>
            </div>
        </form>
    </div>
</div>

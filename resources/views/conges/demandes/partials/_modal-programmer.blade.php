<div class="modal fade" id="modal-programmer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-programmer">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Programmer la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>La demande passera au statut « Programmée ».</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Programmer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
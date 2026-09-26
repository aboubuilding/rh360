<div class="card">
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            <i class="fas fa-lock"></i> Données bancaires protégées.
        </div>

        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Banque</dt>
                    <dd class="col-sm-7">{{ $salarie->banque ?? '—' }}</dd>

                    <dt class="col-sm-5">Compte bancaire</dt>
                    <dd class="col-sm-7">{{ $salarie->compte_bancaire ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Mode de paiement</dt>
                    <dd class="col-sm-7">{{ $salarie->mode_paiement ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
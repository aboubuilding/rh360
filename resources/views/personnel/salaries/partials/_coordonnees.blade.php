<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Téléphone principal</dt>
                    <dd class="col-sm-7">{{ $salarie->telephone_principal ?? '—' }}</dd>

                    <dt class="col-sm-5">Téléphone secondaire</dt>
                    <dd class="col-sm-7">{{ $salarie->telephone_secondaire ?? '—' }}</dd>

                    <dt class="col-sm-5">Email personnel</dt>
                    <dd class="col-sm-7">{{ $salarie->email_personnel ?? '—' }}</dd>

                    <dt class="col-sm-5">Email professionnel</dt>
                    <dd class="col-sm-7">{{ $salarie->email_professionnel ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Adresse</dt>
                    <dd class="col-sm-7">{{ $salarie->adresse ?? '—' }}</dd>

                    <dt class="col-sm-5">Ville</dt>
                    <dd class="col-sm-7">{{ $salarie->ville ?? '—' }}</dd>

                    <dt class="col-sm-5">Pays de résidence</dt>
                    <dd class="col-sm-7">{{ $salarie->pays_residence ?? '—' }}</dd>

                    @can('permission', 'sensitive.gps')
                        <dt class="col-sm-5">Coordonnées GPS</dt>
                        <dd class="col-sm-7">
                            @if($salarie->gps_latitude && $salarie->gps_longitude)
                                {{ $salarie->gps_latitude }}, {{ $salarie->gps_longitude }}
                            @else
                                —
                            @endif
                        </dd>
                    @endcan
                </dl>
            </div>
        </div>
    </div>
</div>
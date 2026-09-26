<div class="card">
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            <i class="fas fa-lock"></i> Données protégées. Accès restreint aux utilisateurs habilités.
        </div>

        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-6">N° CNSS</dt>
                    <dd class="col-sm-6">{{ $salarie->numero_cnss ?? '—' }}</dd>

                    <dt class="col-sm-6">Date d'immatriculation CNSS</dt>
                    <dd class="col-sm-6">{{ $salarie->date_immatriculation_cnss?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-6">N° AMU</dt>
                    <dd class="col-sm-6">{{ $salarie->numero_amu ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Organisme d'assurance</dt>
                    <dd class="col-sm-6">{{ $salarie->organisme_assurance ?? '—' }}</dd>

                    <dt class="col-sm-6">Situation matrimoniale</dt>
                    <dd class="col-sm-6">{{ $salarie->situation_matrimoniale ?? '—' }}</dd>

                    <dt class="col-sm-6">Contact d'urgence</dt>
                    <dd class="col-sm-6">
                        @if($salarie->contact_urgence_nom)
                            {{ $salarie->contact_urgence_nom }}
                            @if($salarie->contact_urgence_lien)
                                ({{ $salarie->contact_urgence_lien }})
                            @endif
                            @if($salarie->contact_urgence_telephone)
                                — {{ $salarie->contact_urgence_telephone }}
                            @endif
                        @else
                            —
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
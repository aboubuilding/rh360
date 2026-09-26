<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nom</dt>
                    <dd class="col-sm-7">{{ $salarie->nom }}</dd>

                    <dt class="col-sm-5">Prénoms</dt>
                    <dd class="col-sm-7">{{ $salarie->prenoms }}</dd>

                    <dt class="col-sm-5">Sexe</dt>
                    <dd class="col-sm-7">{{ $salarie->sexe === 'M' ? 'Masculin' : ($salarie->sexe === 'F' ? 'Féminin' : '—') }}</dd>

                    <dt class="col-sm-5">Date de naissance</dt>
                    <dd class="col-sm-7">{{ $salarie->date_naissance?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">Lieu de naissance</dt>
                    <dd class="col-sm-7">{{ $salarie->lieu_naissance ?? '—' }}</dd>

                    <dt class="col-sm-5">Nationalité</dt>
                    <dd class="col-sm-7">{{ $salarie->nationalite ?? '—' }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Type de pièce</dt>
                    <dd class="col-sm-7">{{ $salarie->type_piece ?? '—' }}</dd>

                    <dt class="col-sm-5">N° de pièce</dt>
                    <dd class="col-sm-7">{{ $salarie->numero_piece ?? '—' }}</dd>

                    <dt class="col-sm-5">Expiration pièce</dt>
                    <dd class="col-sm-7">{{ $salarie->date_expiration_piece?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-sm-5">N° enregistrement</dt>
                    <dd class="col-sm-7">{{ $salarie->numero_enregistrement ?? '—' }}</dd>

                    <dt class="col-sm-5">Matricule</dt>
                    <dd class="col-sm-7"><code>{{ $salarie->matricule }}</code></dd>

                    <dt class="col-sm-5">Origine carrière</dt>
                    <dd class="col-sm-7">{{ $salarie->origine_carriere ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
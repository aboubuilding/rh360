@php
    $mouvement = $mouvement ?? null;
    $salarie = $salarie ?? null;
@endphp

<div class="card mb-3">
    <div class="card-header"><strong>Salarié & type</strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                @if($salarie)
                    <div class="alert alert-info py-2">
                        Salarié sélectionné : <strong>{{ $salarie->nom_complet }}</strong>
                        ({{ $salarie->matricule }})
                        <input type="hidden" name="salarie_id" value="{{ $salarie->id }}">
                    </div>
                @else
                    <x-field name="salarie_id" label="Salarié" type="select" required
                             :value="$mouvement?->salarie_id"
                             :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                @endif
            </div>
            <div class="col-md-6">
                <x-field name="type_mouvement" label="Type de mouvement" type="select" required
                         :value="$mouvement?->type_mouvement?->value"
                         :options="$types" />
            </div>
            <div class="col-md-6">
                <x-field name="sous_type_mouvement" label="Sous-type (optionnel)"
                         :value="$mouvement?->sous_type_mouvement" />
            </div>
            <div class="col-md-6">
                <x-field name="date_eligibilite" label="Date d'éligibilité" type="date"
                         :value="$mouvement?->date_eligibilite?->format('Y-m-d')" />
            </div>
            <div class="col-md-12">
                <x-field name="motif" label="Motif" type="textarea"
                         :value="$mouvement?->motif" />
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Situation de départ</strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <x-field name="structure_depart_id" label="Structure" type="select"
                         :value="$mouvement?->structure_depart_id"
                         :options="[null => '—'] + $structures->pluck('nom', 'id')->all()" />
            </div>
            <div class="col-md-6">
                <x-field name="poste_depart_id" label="Poste" type="select"
                         :value="$mouvement?->poste_depart_id"
                         :options="[null => '—'] + $postes->pluck('intitule', 'id')->all()" />
            </div>
            <div class="col-md-6">
                <x-field name="position_classification_depart_id" label="Position de classification" type="select"
                         :value="$mouvement?->position_classification_depart_id"
                         :options="[null => '—'] + $positions->mapWithKeys(fn($p) => [$p->id => $p->code.' — '.$p->libelleComplet()])->all()" />
            </div>
            <div class="col-md-6">
                <x-field name="lieu_affectation_depart" label="Lieu d'affectation"
                         :value="$mouvement?->lieu_affectation_depart" />
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Situation cible</strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <x-field name="structure_cible_id" label="Structure" type="select"
                         :value="$mouvement?->structure_cible_id"
                         :options="[null => '—'] + $structures->pluck('nom', 'id')->all()" />
            </div>
            <div class="col-md-6">
                <x-field name="poste_cible_id" label="Poste" type="select"
                         :value="$mouvement?->poste_cible_id"
                         :options="[null => '—'] + $postes->pluck('intitule', 'id')->all()" />
            </div>
            <div class="col-md-6">
                <x-field name="position_classification_cible_id" label="Position de classification" type="select"
                         :value="$mouvement?->position_classification_cible_id"
                         :options="[null => '—'] + $positions->mapWithKeys(fn($p) => [$p->id => $p->code.' — '.$p->libelleComplet()])->all()" />
            </div>
            <div class="col-md-6">
                <x-field name="lieu_affectation_cible" label="Lieu d'affectation"
                         :value="$mouvement?->lieu_affectation_cible" />
            </div>
            <div class="col-md-6">
                <x-field name="date_effet" label="Date d'effet prévue" type="date"
                         :value="$mouvement?->date_effet?->format('Y-m-d')" />
            </div>
            <div class="col-md-6">
                <x-field name="date_fin_prevue" label="Date de fin prévue (si temporaire)" type="date"
                         :value="$mouvement?->date_fin_prevue?->format('Y-m-d')" />
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Références</strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <x-field name="reference_acte" label="Référence de l'acte"
                         :value="$mouvement?->reference_acte" />
            </div>
            <div class="col-md-6">
                <x-field name="date_acte" label="Date de l'acte" type="date"
                         :value="$mouvement?->date_acte?->format('Y-m-d')" />
            </div>
        </div>
    </div>
</div>
@php $regle = $regleEvolution ?? null; @endphp

<div class="row">
    <div class="col-md-6">
        <x-field name="referentiel_id" label="Référentiel" type="select" required
                 :value="$regle?->referentiel_id"
                 :options="$referentiels->pluck('nom', 'id')->all()" />
    </div>
    <div class="col-md-6">
        <x-field name="code" label="Code" required :value="$regle?->code" />
    </div>

    <div class="col-md-4">
        <x-field name="type_evolution" label="Type d'évolution" required
                 :value="$regle?->type_evolution"
                 help="echelon, classe, categorie" />
    </div>
    <div class="col-md-4">
        <x-field name="niveau_source" label="Niveau source" required
                 :value="$regle?->niveau_source ?? 'interne'" />
    </div>
    <div class="col-md-4">
        <x-field name="priorite" label="Priorité" type="number"
                 :value="$regle?->priorite ?? 100" />
    </div>

    <div class="col-md-6">
        <x-field name="intitule_source" label="Intitulé source"
                 :value="$regle?->intitule_source" />
    </div>
    <div class="col-md-6">
        <x-field name="reference_source" label="Référence source"
                 :value="$regle?->reference_source" />
    </div>

    <div class="col-md-12">
        <x-field name="portee" label="Portée" type="textarea"
                 :value="$regle?->portee" />
    </div>

    <div class="col-md-6">
        <x-field name="mois_min" label="Délai minimum (mois)" type="number"
                 :value="$regle?->mois_min" />
    </div>
    <div class="col-md-6">
        <x-field name="mois_max" label="Délai maximum (mois)" type="number"
                 :value="$regle?->mois_max" />
    </div>

    <div class="col-md-6">
        <x-field name="anticipation_autorisee" label="Anticipation autorisée" type="checkbox"
                 :value="$regle?->anticipation_autorisee ?? false" />
    </div>
    <div class="col-md-6">
        <x-field name="validation_requise" label="Validation requise" type="checkbox"
                 :value="$regle?->validation_requise ?? true" />
    </div>

    <div class="col-md-12">
        <x-field name="condition_anticipation" label="Condition d'anticipation" type="textarea"
                 :value="$regle?->condition_anticipation" />
    </div>

    <div class="col-md-6">
        <x-field name="mode_reinitialisation_anticipation" label="Mode réinitialisation anticipation" required
                 :value="$regle?->mode_reinitialisation_anticipation ?? 'new_step_effective_date'" />
    </div>
    <div class="col-md-6">
        <x-field name="statut" label="Statut" :value="$regle?->statut ?? 'active'" />
    </div>

    <div class="col-md-12">
        <x-field name="regle_transitoire" label="Règle transitoire" type="textarea"
                 :value="$regle?->regle_transitoire" />
    </div>

    <div class="col-md-6">
        <x-field name="debut_effet" label="Début d'effet" type="date"
                 :value="$regle?->debut_effet?->format('Y-m-d')" />
    </div>
    <div class="col-md-6">
        <x-field name="fin_effet" label="Fin d'effet" type="date"
                 :value="$regle?->fin_effet?->format('Y-m-d')" />
    </div>

    <div class="col-md-12">
        <x-field name="actif" label="Actif" type="checkbox"
                 :value="$regle?->actif ?? true" />
    </div>
</div>
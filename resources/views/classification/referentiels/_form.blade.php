{{-- Partial réutilisé par create + edit --}}
@php
    $referentiel = $referentiel ?? null;
@endphp

<div class="row">
    <div class="col-md-4">
        <x-field name="code" label="Code" required :value="$referentiel?->code" />
    </div>
    <div class="col-md-8">
        <x-field name="nom" label="Nom" required :value="$referentiel?->nom" />
    </div>

    <div class="col-md-4">
        <x-field name="type_referentiel" label="Type de référentiel" required
                 :value="$referentiel?->type_referentiel ?? 'enterprise'"
                 help="enterprise, conformity, sector..." />
    </div>
    <div class="col-md-4">
        <x-field name="niveau_source" label="Niveau source" required
                 :value="$referentiel?->niveau_source ?? 'interne'"
                 help="interne, externe, conventionnel" />
    </div>
    <div class="col-md-4">
        <x-field name="priorite" label="Priorité" type="number"
                 :value="$referentiel?->priorite ?? 100" />
    </div>

    <div class="col-md-6">
        <x-field name="intitule_source" label="Intitulé de la source"
                 :value="$referentiel?->intitule_source" />
    </div>
    <div class="col-md-6">
        <x-field name="reference_source" label="Référence de la source"
                 :value="$referentiel?->reference_source" />
    </div>

    <div class="col-md-12">
        <x-field name="portee" label="Portée" type="textarea"
                 :value="$referentiel?->portee" />
    </div>

    <div class="col-md-6">
        <x-field name="debut_effet" label="Début d'effet" type="date"
                 :value="$referentiel?->debut_effet?->format('Y-m-d')" />
    </div>
    <div class="col-md-6">
        <x-field name="fin_effet" label="Fin d'effet" type="date"
                 :value="$referentiel?->fin_effet?->format('Y-m-d')" />
    </div>

    <div class="col-md-12">
        <x-field name="actif" label="Actif" type="checkbox"
                 :value="$referentiel?->actif ?? true" />
    </div>
</div>
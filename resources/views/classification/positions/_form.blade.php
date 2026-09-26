@php $position = $position ?? null; @endphp

<div class="row">
    <div class="col-md-6">
        <x-field name="referentiel_id" label="Référentiel" type="select" required
                 :value="$position?->referentiel_id"
                 :options="$referentiels->pluck('nom', 'id')->all()" />
    </div>
    <div class="col-md-6">
        <x-field name="code" label="Code position" required :value="$position?->code" />
    </div>

    <div class="col-md-4">
        <x-field name="categorie_id" label="Catégorie" type="select" required
                 :value="$position?->categorie_id"
                 :options="$categories->pluck('libelle', 'id')->all()" />
    </div>
    <div class="col-md-4">
        <x-field name="classe_id" label="Classe" type="select"
                 :value="$position?->classe_id"
                 :options="[null => '—'] + $classes->pluck('libelle', 'id')->all()" />
    </div>
    <div class="col-md-4">
        <x-field name="echelon_id" label="Échelon" type="select"
                 :value="$position?->echelon_id"
                 :options="[null => '—'] + $echelons->pluck('libelle', 'id')->all()" />
    </div>

    <div class="col-md-6">
        <x-field name="montant_salaire" label="Salaire (FCFA)" type="number"
                 :value="$position?->montant_salaire" />
    </div>
    <div class="col-md-6">
        <x-field name="salaire_minimum" label="Salaire minimum (FCFA)" type="number"
                 :value="$position?->salaire_minimum" />
    </div>

    <div class="col-md-6">
        <x-field name="position_conformite_id" label="Position de conformité" type="select"
                 :value="$position?->position_conformite_id"
                 :options="[null => '—'] + $positions->pluck('code', 'id')->all()" />
    </div>
    <div class="col-md-6">
        <x-field name="position_suivante_id" label="Position suivante" type="select"
                 :value="$position?->position_suivante_id"
                 :options="[null => '—'] + $positions->pluck('code', 'id')->all()" />
    </div>

    <div class="col-md-4">
        <x-field name="ordre" label="Ordre" type="number" :value="$position?->ordre ?? 100" />
    </div>
    <div class="col-md-4">
        <x-field name="debut_effet" label="Début d'effet" type="date"
                 :value="$position?->debut_effet?->format('Y-m-d')" />
    </div>
    <div class="col-md-4">
        <x-field name="fin_effet" label="Fin d'effet" type="date"
                 :value="$position?->fin_effet?->format('Y-m-d')" />
    </div>

    <div class="col-md-6">
        <x-field name="statut" label="Statut" :value="$position?->statut ?? 'active'"
                 help="active, inactive, archive" />
    </div>
    <div class="col-md-6">
        <x-field name="actif" label="Actif" type="checkbox"
                 :value="$position?->actif ?? true" />
    </div>
</div>
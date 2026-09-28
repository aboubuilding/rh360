@php
    $contrat = $contrat ?? null;
    $salarie = $salarie ?? null;
@endphp

<div class="row">
    <div class="col-md-8">
        <x-field name="reference" label="Référence du contrat" required
                 :value="$contrat?->reference ?? 'CTR-'.now()->format('Y').'-'" />
    </div>
    <div class="col-md-4">
        <x-field name="type_contrat" label="Type de contrat" type="select" required
                 :value="$contrat?->type_contrat"
                 :options="$types" />
    </div>

    <div class="col-md-6">
        <x-field name="date_debut" label="Date de début" type="date" required
                 :value="$contrat?->date_debut?->format('Y-m-d') ?? now()->format('Y-m-d')" />
    </div>
    <div class="col-md-6">
        <x-field name="date_fin" label="Date de fin (si CDD)" type="date"
                 :value="$contrat?->date_fin?->format('Y-m-d')"
                 help="Laisser vide pour un CDI." />
    </div>

    <div class="col-md-6">
        <x-field name="poste_id" label="Poste" type="select" required
                 :value="$contrat?->poste_id"
                 :options="$postes->pluck('intitule', 'id')->all()" />
    </div>
    <div class="col-md-6">
        <x-field name="position_classification_id" label="Position de classification" type="select" required
                 :value="$contrat?->position_classification_id"
                 :options="$positions->mapWithKeys(fn($p) => [$p->id => $p->code.' — '.$p->libelleComplet()])->all()" />
    </div>
</div>
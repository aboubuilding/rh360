@php $risque = $risque ?? null; @endphp

<div class="row">
    <div class="col-md-8">
        <x-field name="intitule" label="Intitulé du risque" required :value="$risque?->intitule" />
    </div>
    <div class="col-md-4">
        <x-field name="famille" label="Famille" type="select" required
                 :value="$risque?->famille?->value"
                 :options="$familles" />
    </div>

    <div class="col-md-6">
        <x-field name="site" label="Site" required :value="$risque?->site" />
    </div>
    <div class="col-md-6">
        <x-field name="poste_id" label="Poste concerné" type="select"
                 :value="$risque?->poste_id"
                 :options="[null => '—'] + $postes->pluck('intitule', 'id')->all()" />
    </div>

    <div class="col-md-12">
        <x-field name="activite" label="Activité concernée" required :value="$risque?->activite" />
    </div>

    <div class="col-md-12">
        <x-field name="danger" label="Description du danger" type="textarea" required
                 :value="$risque?->danger" />
    </div>

    <div class="col-md-12">
        <x-field name="consequences" label="Conséquences potentielles" type="textarea" required
                 :value="$risque?->consequences" />
    </div>

    <div class="col-md-6">
        <x-field name="date_identification" label="Date d'identification" type="date"
                 :value="$risque?->date_identification?->format('Y-m-d') ?? now()->format('Y-m-d')" />
    </div>
    <div class="col-md-6">
        <x-field name="responsable_salarie_id" label="Responsable du risque" type="select" required
                 :value="$risque?->responsable_salarie_id"
                 :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
    </div>

    <div class="col-md-6">
        <x-field name="date_echeance_revue" label="Prochaine revue" type="date"
                 :value="$risque?->date_echeance_revue?->format('Y-m-d')" />
    </div>
</div>
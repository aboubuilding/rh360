@php
    $demande = $demande ?? null;
    $salarie = $salarie ?? null;
@endphp

<div class="row">
    <div class="col-md-6">
        @if($salarie)
            <div class="alert alert-info py-2">
                Salarié : <strong>{{ $salarie->nom_complet }}</strong>
                <input type="hidden" name="salarie_id" value="{{ $salarie->id }}">
            </div>
        @else
            <x-field name="salarie_id" label="Salarié" type="select" required
                     :value="$demande?->salarie_id"
                     :options="$salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
        @endif
    </div>
    <div class="col-md-6">
        <x-field name="type_conge_id" label="Type de congé" type="select" required
                 :value="$demande?->type_conge_id"
                 :options="$types->pluck('nom', 'id')->all()" />
    </div>

    <div class="col-md-6">
        <x-field name="date_debut" label="Date de début" type="date" required
                 :value="$demande?->date_debut?->format('Y-m-d')" />
    </div>
    <div class="col-md-6">
        <x-field name="date_reprise" label="Date de reprise" type="date"
                 :value="$demande?->date_reprise?->format('Y-m-d')"
                 help="Si vide, calculée automatiquement." />
    </div>

    <div class="col-md-4">
        <x-field name="duree_jours" label="Durée (jours)" type="number" step="0.5"
                 :value="$demande?->duree_jours" />
    </div>
    <div class="col-md-4">
        <x-field name="remplacant" label="Remplaçant" :value="$demande?->remplacant" />
    </div>
    <div class="col-md-4">
        <x-field name="date_demande" label="Date de la demande" type="date"
                 :value="$demande?->date_demande?->format('Y-m-d') ?? now()->format('Y-m-d')" />
    </div>

    <div class="col-md-12">
        <x-field name="motif" label="Motif" type="textarea"
                 :value="$demande?->motif" />
    </div>

    @if($demande)
        <div class="col-md-6">
            <x-field name="reference_acte" label="Référence de l'acte" :value="$demande->reference_acte" />
        </div>
        <div class="col-md-6">
            <x-field name="date_acte" label="Date de l'acte" type="date"
                     :value="$demande->date_acte?->format('Y-m-d')" />
        </div>
    @endif
</div>
@php
    $demande = $demande ?? null;
    $salarie = $salarie ?? null;
    $evenements = $evenements ?? collect();
    // Types exprimés en heures, et types dont le plafond s'apprécie par enfant.
    $typesEnHeures = $types->filter(fn ($t) => $t->unite === \App\Domain\Conges\Enums\UniteConge::HEURE)->pluck('id')->values();
    $typesParEnfant = $types->filter(fn ($t) => $t->plafond_base === \App\Domain\Conges\Enums\BasePlafond::ENFANT)->pluck('id')->values();
    $membreFoyer = $demande?->membreFoyer;
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

    {{-- Événement : permissions exceptionnelles uniquement (affiché selon le type choisi). --}}
    <div class="col-md-6 js-champ-evenement" data-types="{{ $evenements->pluck('type_conge_id')->unique()->values()->toJson() }}">
        <x-field name="evenement_permission_id" label="Événement" type="select"
                 :value="$demande?->evenement_permission_id"
                 :options="[null => '—'] + $evenements->mapWithKeys(fn ($e) => [$e->id => $e->libelle.' ('.(float) $e->jours.' j)'])->all()" />
    </div>
    <div class="col-md-6 js-champ-evenement" data-types="{{ $evenements->pluck('type_conge_id')->unique()->values()->toJson() }}">
        <x-field name="date_evenement" label="Date de l'événement" type="date"
                 :value="$demande?->date_evenement?->format('Y-m-d')" />
    </div>

    {{-- Enfant concerné : chargé depuis le foyer du salarié. --}}
    <div class="col-md-6 js-champ-enfant" data-types="{{ $typesParEnfant->toJson() }}">
        <label class="form-label" for="membre_foyer_id">Enfant concerné</label>
        <select name="membre_foyer_id" id="membre_foyer_id" class="form-select @error('membre_foyer_id') is-invalid @enderror"
                data-url="{{ route('conges.demandes.membres-foyer', ['salarie' => '__ID__']) }}"
                data-valeur="{{ old('membre_foyer_id', $demande?->membre_foyer_id) }}">
            <option value="">—</option>
            @if($membreFoyer)
                <option value="{{ $membreFoyer->id }}" selected>{{ $membreFoyer->prenoms }} {{ $membreFoyer->nom }}</option>
            @endif
        </select>
        @error('membre_foyer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 js-champ-heures" data-types="{{ $typesEnHeures->toJson() }}">
        <x-field name="duree_heures" label="Durée (heures)" type="number" step="0.25"
                 :value="$demande?->duree_heures" />
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

    <div class="col-md-12">
        <x-field name="force_majeure" label="Absence déjà commencée ou imprévue (force majeure)" type="checkbox"
                 :value="$demande?->force_majeure" />
    </div>
    <div class="col-md-12 js-champ-force-majeure">
        <x-field name="motif_force_majeure" label="Cas de force majeure" type="textarea"
                 :value="$demande?->motif_force_majeure" />
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

@push('js')
<script>
$(function () {
    // Champs de l'événement : seulement pour les types qui ont des événements (permissions exceptionnelles).
    function majChampsEvenement() {
        const type = parseInt($('[name="type_conge_id"]').val(), 10);
        $('.js-champ-evenement').each(function () {
            $(this).toggle(($(this).data('types') || []).includes(type));
        });
    }
    function majChampsParType(selecteur) {
        const type = parseInt($('[name="type_conge_id"]').val(), 10);
        $(selecteur).each(function () {
            $(this).toggle(($(this).data('types') || []).includes(type));
        });
    }
    // Enfants déclarés du salarié choisi.
    function chargerEnfants() {
        const $select = $('#membre_foyer_id');
        const salarie = $('[name="salarie_id"]').val();
        if (! $select.length || ! salarie) return;
        $.getJSON($select.data('url').replace('__ID__', salarie), function (enfants) {
            const valeur = String($select.data('valeur') || '');
            $select.find('option:not(:first)').remove();
            enfants.forEach(function (e) {
                $select.append($('<option>', { value: e.id, text: e.libelle, selected: String(e.id) === valeur }));
            });
        });
    }
    function majForceMajeure() {
        $('.js-champ-force-majeure').toggle($('input[type="checkbox"][name="force_majeure"]').is(':checked'));
    }
    $(document).on('change', '[name="type_conge_id"]', majChampsEvenement);
    $(document).on('change', '[name="type_conge_id"]', () => { majChampsParType('.js-champ-enfant'); majChampsParType('.js-champ-heures'); });
    $(document).on('change', '[name="salarie_id"]', chargerEnfants);
    $(document).on('change', 'input[type="checkbox"][name="force_majeure"]', majForceMajeure);
    majChampsEvenement();
    majChampsParType('.js-champ-enfant');
    majChampsParType('.js-champ-heures');
    chargerEnfants();
    majForceMajeure();
});
</script>
@endpush

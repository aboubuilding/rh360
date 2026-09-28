@php $evenement = $evenement ?? null; @endphp

<div class="row">
    <div class="col-md-6">
        <x-field name="type_evenement" label="Type d'événement" type="select" required
                 :value="$evenement?->type_evenement?->value"
                 :options="$types" />
    </div>
    <div class="col-md-3">
        <x-field name="date_survenance" label="Date de survenance" type="date" required
                 :value="$evenement?->date_survenance?->format('Y-m-d') ?? now()->format('Y-m-d')" />
    </div>
    <div class="col-md-3">
        <x-field name="heure_survenance" label="Heure (HH:MM)" :value="$evenement?->heure_survenance" />
    </div>

    <div class="col-md-12">
        <x-field name="intitule" label="Intitulé" required :value="$evenement?->intitule" />
    </div>

    <div class="col-md-6">
        <x-field name="localisation" label="Localisation" required :value="$evenement?->localisation" />
    </div>
    <div class="col-md-6">
        <x-field name="priorite" label="Priorité" type="select"
                 :value="$evenement?->priorite ?? 'normal'"
                 :options="['low' => 'Faible', 'normal' => 'Normale', 'high' => 'Haute', 'critical' => 'Critique']" />
    </div>

    <div class="col-md-12">
        <x-field name="description" label="Description détaillée" type="textarea" required
                 :value="$evenement?->description" />
    </div>

    <div class="col-md-12">
        <x-field name="mesures_immediates" label="Mesures immédiates prises" type="textarea"
                 :value="$evenement?->mesures_immediates" />
    </div>
</div>

@if(! $evenement)
    <hr>
    <h6 class="text-muted mb-3">Participants (salariés concernés)</h6>
    <div class="row">
        <div class="col-md-12">
            <select name="participants[]" id="participants" class="form-select" multiple size="8">
                @foreach($salaries as $s)
                    <option value="{{ $s->id }}">{{ $s->nom_complet }} ({{ $s->matricule }})</option>
                @endforeach
            </select>
            <small class="text-muted">Maintenez Ctrl pour sélectionner plusieurs salariés.</small>
        </div>
    </div>
@endif
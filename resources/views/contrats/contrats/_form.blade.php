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

{{-- RG-REM-01 à 05 — CDD de remplacement (Code du travail, art. 48, 49 ; CCIT, art. 16) --}}
<fieldset class="border rounded p-3 mb-3">
    <legend class="float-none w-auto px-2 fs-6 text-muted">CDD : motif de recours et terme</legend>
    <div class="row">
        <div class="col-md-6">
            <x-field name="motif_recours" label="Motif de recours" type="select"
                     :value="$contrat?->motif_recours"
                     :options="[null => '—'] + \App\Domain\Contrats\Enums\MotifRecoursCdd::options()" />
        </div>
        <div class="col-md-6">
            <x-field name="titulaire_remplace_id" label="Salarié remplacé (obligatoire pour un remplacement)" type="select"
                     :value="$contrat?->titulaire_remplace_id"
                     :options="[null => '—'] + \App\Domain\Personnel\Models\Salarie::where('actif', true)->orderBy('nom')->get()->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
        </div>
        <div class="col-md-6">
            <x-field name="terme_evenement" label="Terme fixé par un événement (au lieu d'une date)" type="select"
                     :value="$contrat?->terme_evenement"
                     :options="[null => '—'] + \App\Domain\Contrats\Enums\TermeEvenementCdd::options()" />
        </div>
        <div class="col-md-6">
            <x-field name="journalier_paye_fin_journee" label="Travailleur journalier payé en fin de journée (contrat écrit non obligatoire)"
                     type="checkbox" :value="$contrat?->journalier_paye_fin_journee" />
        </div>
        <div class="col-md-12">
            <x-field name="mention_caractere_provisoire" label="Mention du caractère provisoire de l'emploi (lettre d'engagement)" type="textarea"
                     :value="$contrat?->mention_caractere_provisoire"
                     help="Obligatoire en cas de remplacement d'un salarié malade (CCIT, art. 16)." />
        </div>
    </div>
</fieldset>
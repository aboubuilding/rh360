@extends('layouts.app')

@section('title', 'Nouvelle mission d\'intérim')
@section('page_title', 'Nouvelle mission d\'intérim')
@section('page_icon', 'fa-user-tie')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.missions.index') }}">Missions d'intérim</a></li>
    <li>Nouvelle</li>
@endsection

@section('contenu')
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    Interdits : le remplacement d'un salarié en grève et les travaux particulièrement dangereux (Code du travail, art. 62).
    Sanction encourue (art. 356) : amende de 500 000 à 2 000 000 FCFA ; en récidive, de 1 000 000 à 5 000 000 FCFA
    et/ou emprisonnement de 1 à 6 mois.
</div>

<div class="card">
    <div class="card-body">
        <form id="form-mission" method="POST" action="{{ route('carriere.interims.missions.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <x-field name="entreprise_interim_id" label="Entreprise de travail temporaire" type="select" required
                             :options="[null => '—'] + $agences->mapWithKeys(fn($a) => [$a->id => $a->raison_sociale.($a->numero_agrement ? ' — agrément '.$a->numero_agrement : ' — sans agrément')])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="poste_id" label="Poste" type="select" required
                             :options="[null => '—'] + $postes->mapWithKeys(fn($p) => [$p->id => $p->intitule.($p->travaux_dangereux ? ' (travaux dangereux)' : '')])->all()" />
                </div>
                <div class="col-md-4"><x-field name="interimaire_nom" label="Nom de l'intérimaire" required /></div>
                <div class="col-md-4"><x-field name="interimaire_prenoms" label="Prénoms" required /></div>
                <div class="col-md-4"><x-field name="interimaire_piece" label="Pièce d'identité" /></div>
                <div class="col-md-6"><x-field name="qualification_convenue" label="Qualification convenue" required /></div>
                <div class="col-md-6"><x-field name="salaire_convenu" label="Salaire convenu avec l'agence (FCFA)" type="number" /></div>
                <div class="col-md-12"><x-field name="travail_determine" label="Travail déterminé confié" type="textarea" required /></div>
                <div class="col-md-6"><x-field name="type_motif" label="Nature du motif" type="select" required :options="[null => '—'] + $typesMotif" /></div>
                <div class="col-md-6">
                    <x-field name="titulaire_remplace_id" label="Salarié remplacé" type="select"
                             :options="[null => '—'] + $salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-12"><x-field name="motif" label="Motif de recours" type="textarea" required /></div>
                <div class="col-md-6"><x-field name="date_debut" label="Date de début" type="date" required /></div>
                <div class="col-md-6"><x-field name="date_fin" label="Date de fin" type="date" required /></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer la mission</button>
                <a href="{{ route('carriere.interims.missions.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
@include('carriere.interims.partials._script-formulaire')
<script>
$(function () {
    soumettreFormulaireInterim('#form-mission', function (r) {
        if (r.alerte) { window.showToast(r.alerte, 'warning'); }
        window.showToast(r.message || 'Mission enregistrée.');
        setTimeout(() => { window.location.href = r.redirect; }, r.alerte ? 2500 : 600);
    });
});
</script>
@endpush

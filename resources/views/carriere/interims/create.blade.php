@extends('layouts.app')

@section('title', 'Nouvel intérim')
@section('page_title', 'Nouvel intérim interne')
@section('page_icon', 'fa-user-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.index') }}">Intérims</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Intérim d'un poste de catégorie supérieure (CCIT, art. 13) : 2 mois au plus, sauf maladie ou accident,
    congé annuel ou de maternité, détention préventive, accident du travail ou maladie professionnelle du titulaire.
    La paie reste celle de la catégorie d'origine pendant les 2 premiers mois.
</div>

<div class="card">
    <div class="card-body">
        <form id="form-interim" method="POST" action="{{ route('carriere.interims.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <x-field name="salarie_id" label="Salarié en intérim" type="select" required
                             :options="[null => '—'] + $salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="poste_occupe_id" label="Poste occupé" type="select" required
                             :options="[null => '—'] + $postes->mapWithKeys(fn($p) => [$p->id => $p->intitule.' — '.($p->categorieClassification?->libelle ?? 'catégorie non renseignée')])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="titulaire_id" label="Titulaire remplacé" type="select"
                             :options="[null => '—'] + $salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="absence_titulaire_id" label="Absence du titulaire" type="select"
                             :options="[null => '—'] + $absences->mapWithKeys(fn($a) => [$a->id => $a->salarie?->nom_complet.' — '.$a->typeConge?->nom.' du '.$a->debut_le?->format('d/m/Y')])->all()" />
                </div>
                <div class="col-md-6">
                    <x-field name="motif" label="Motif" type="select" required :options="[null => '—'] + $motifs" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_fin_motif" label="Fin du congé / date de consolidation" type="date" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_debut" label="Date de début" type="date" required
                             :value="now()->format('Y-m-d')" />
                </div>
                <div class="col-md-6">
                    <x-field name="date_fin_prevue" label="Date de fin prévue" type="date" required
                             :value="now()->addMonthsNoOverflow($dureeMax)->subDay()->format('Y-m-d')" />
                </div>
                <div class="col-md-12">
                    <x-field name="precision_motif" label="Précision sur le motif" type="textarea" />
                </div>
                <div class="col-md-6">
                    <x-field name="piece" label="Pièce jointe (acte de désignation)" type="file" />
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Créer l'intérim
                </button>
                <a href="{{ route('carriere.interims.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
@include('carriere.interims.partials._script-formulaire')
<script>
$(function () {
    soumettreFormulaireInterim('#form-interim', function (r) {
        window.showToast(r.message || 'Intérim créé.');
        setTimeout(() => { window.location.href = r.redirect; }, 600);
    });
});
</script>
@endpush

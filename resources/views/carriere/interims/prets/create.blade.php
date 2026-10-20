@extends('layouts.app')

@section('title', 'Nouveau prêt de main-d\'œuvre')
@section('page_title', 'Nouveau prêt de main-d\'œuvre')
@section('page_icon', 'fa-handshake')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.prets.index') }}">Prêts de main-d'œuvre</a></li>
    <li>Nouveau</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Avant le début du prêt, trois pièces sont obligatoires : l'accord écrit du salarié, la convention de mise à disposition
    et l'avenant au contrat signé. Le salarié peut refuser sans aucune conséquence.
</div>

<div class="card">
    <div class="card-body">
        <form id="form-pret" method="POST" action="{{ route('carriere.interims.prets.store') }}">
            @csrf
            <h6 class="text-muted border-bottom pb-1">Salarié et entreprise utilisatrice</h6>
            <div class="row">
                <div class="col-md-6">
                    <x-field name="salarie_id" label="Salarié prêté" type="select" required
                             :options="[null => '—'] + $salaries->mapWithKeys(fn($s) => [$s->id => $s->nom_complet.' ('.$s->matricule.')'])->all()" />
                </div>
                <div class="col-md-6"><x-field name="utilisatrice_raison_sociale" label="Entreprise utilisatrice" required /></div>
                <div class="col-md-6"><x-field name="utilisatrice_adresse" label="Adresse" /></div>
                <div class="col-md-6"><x-field name="utilisatrice_contact" label="Contact" /></div>
            </div>

            <h6 class="text-muted border-bottom pb-1 mt-2">Convention de mise à disposition</h6>
            <div class="row">
                <div class="col-md-4"><x-field name="date_debut" label="Date de début" type="date" required /></div>
                <div class="col-md-4"><x-field name="date_fin" label="Date de fin" type="date" required /></div>
                <div class="col-md-4"><x-field name="qualification" label="Qualification du salarié" required /></div>
                <div class="col-md-12"><x-field name="mode_calcul_refacturation" label="Mode de calcul de la refacturation" type="textarea" required
                                                 help="Salaires + charges sociales + frais professionnels remboursés, sans marge." /></div>
            </div>

            <h6 class="text-muted border-bottom pb-1 mt-2">Avenant au contrat</h6>
            <div class="row">
                <div class="col-md-12"><x-field name="travail_confie" label="Travail confié" type="textarea" required /></div>
                <div class="col-md-6"><x-field name="horaires" label="Horaires" required /></div>
                <div class="col-md-6"><x-field name="lieu" label="Lieu d'exécution" required /></div>
                <div class="col-md-12"><x-field name="caracteristiques_poste" label="Caractéristiques du poste" type="textarea" required /></div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer le projet de prêt</button>
                <a href="{{ route('carriere.interims.prets.index') }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
@include('carriere.interims.partials._script-formulaire')
<script>
$(function () {
    soumettreFormulaireInterim('#form-pret', function (r) {
        window.showToast(r.message || 'Prêt enregistré.');
        setTimeout(() => { window.location.href = r.redirect; }, 600);
    });
});
</script>
@endpush

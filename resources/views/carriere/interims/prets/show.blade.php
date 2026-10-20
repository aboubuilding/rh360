@extends('layouts.app')

@section('title', 'Prêt ' . $pret->reference)
@section('page_title', 'Prêt ' . $pret->reference . ' — ' . $pret->salarie?->nom_complet)
@section('page_icon', 'fa-handshake')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.prets.index') }}">Prêts de main-d'œuvre</a></li>
    <li>{{ $pret->reference }}</li>
@endsection

@php($statut = $pret->statut)
@section('page_actions')
    @can('gerer', $pret)
        @if($statut === \App\Domain\Interim\Enums\StatutPret::PROJET)
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-piece"><i class="fas fa-file-signature"></i> Ajouter une pièce</button>
            <form method="POST" action="{{ route('carriere.interims.prets.demarrer', $pret) }}" id="form-demarrer" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary"><i class="fas fa-play"></i> Démarrer</button>
            </form>
            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modal-refus"><i class="fas fa-user-times"></i> Refus du salarié</button>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-annuler"><i class="fas fa-ban"></i> Annuler</button>
        @endif
        @if($statut === \App\Domain\Interim\Enums\StatutPret::EN_COURS)
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modal-refacturation"><i class="fas fa-file-invoice"></i> Refacturation</button>
            <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modal-retour"><i class="fas fa-undo"></i> Retour du salarié</button>
        @endif
    @endcan
@endsection

@section('contenu')
@foreach($pret->alertes as $a)
    <div class="alert alert-{{ $a->niveau }}"><i class="fas fa-bell"></i> <strong>{{ $a->intitule }}</strong> — {{ $a->message }}</div>
@endforeach
@if($statut === \App\Domain\Interim\Enums\StatutPret::REFUSE_SALARIE)
    <div class="alert alert-warning">
        Le salarié a refusé ce prêt le {{ $pret->date_reponse_salarie?->format('d/m/Y') }}. Ce refus ne peut entraîner aucune sanction
        ni mesure défavorable.
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">Prêt <span class="badge bg-{{ $statut->couleur() }} ms-1">{{ $statut->libelle() }}</span></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Entreprise utilisatrice</dt><dd class="col-sm-7">{{ $pret->utilisatrice_raison_sociale }}<br><small class="text-muted">{{ $pret->utilisatrice_adresse }} {{ $pret->utilisatrice_contact }}</small></dd>
                    <dt class="col-sm-5">Période</dt><dd class="col-sm-7">{{ $pret->date_debut->format('d/m/Y') }} → {{ $pret->date_fin->format('d/m/Y') }}</dd>
                    <dt class="col-sm-5">Qualification</dt><dd class="col-sm-7">{{ $pret->qualification }}</dd>
                    <dt class="col-sm-5">Refacturation</dt><dd class="col-sm-7">{{ $pret->mode_calcul_refacturation }}</dd>
                    <dt class="col-sm-5">Travail confié</dt><dd class="col-sm-7">{{ $pret->travail_confie }}</dd>
                    <dt class="col-sm-5">Horaires / lieu</dt><dd class="col-sm-7">{{ $pret->horaires }} — {{ $pret->lieu }}</dd>
                    <dt class="col-sm-5">Caractéristiques</dt><dd class="col-sm-7">{{ $pret->caracteristiques_poste }}</dd>
                    <dt class="col-sm-5">Réponse du salarié</dt><dd class="col-sm-7">{{ $pret->reponse_salarie->libelle() }} {{ $pret->date_reponse_salarie?->format('d/m/Y') }}</dd>
                </dl>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Situation garantie au retour</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Poste d'origine</dt><dd class="col-sm-7">{{ $pret->posteOrigine?->intitule ?? '—' }} <small class="text-muted">{{ $pret->posteOrigine?->categorieClassification?->libelle }}</small></dd>
                    <dt class="col-sm-5">Position</dt><dd class="col-sm-7">{{ $pret->positionOrigine?->code ?? '—' }}</dd>
                    <dt class="col-sm-5">Salaire de base</dt><dd class="col-sm-7">{{ $pret->salaire_base_origine !== null ? number_format($pret->salaire_base_origine, 0, ',', ' ').' FCFA' : '—' }}</dd>
                    @if($pret->date_retour)
                        <dt class="col-sm-5">Retour</dt><dd class="col-sm-7">{{ $pret->date_retour->format('d/m/Y') }} — {{ $pret->posteRetour?->intitule }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header">Pièces obligatoires avant le début</div>
            <ul class="list-group list-group-flush">
                @foreach(\App\Domain\Interim\Enums\TypePiecePret::cases() as $type)
                    @php($piece = $pret->pieces->firstWhere('type_piece', $type))
                    <li class="list-group-item">
                        @if($piece)
                            <i class="fas fa-check text-success"></i> {{ $type->libelle() }}
                            <br><small class="text-muted">signée le {{ $piece->date_signature->format('d/m/Y') }}</small>
                        @else
                            <i class="fas fa-times text-danger"></i> {{ $type->libelle() }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card mb-3">
            <div class="card-header">Refacturations</div>
            <ul class="list-group list-group-flush">
                @forelse($pret->refacturations as $r)
                    <li class="list-group-item">
                        {{ $r->periode_debut->format('d/m/Y') }} → {{ $r->periode_fin->format('d/m/Y') }} :
                        <strong>{{ number_format($r->montant_refacture, 0, ',', ' ') }} FCFA</strong>
                        (base {{ number_format($r->baseRefacturable(), 0, ',', ' ') }} FCFA)
                        @if($r->alerte_marge)<div class="text-danger small">{{ $r->alerte_marge }}</div>@endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucune refacturation.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Historique</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th>Décision</th><th>Par</th><th>Commentaire</th></tr></thead>
            <tbody>
                @foreach($pret->decisions as $d)
                    <tr>
                        <td>{{ $d->decide_le->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->type_decision->libelle() }}</td>
                        <td>{{ $d->decidePar?->nom_complet ?? $d->decidePar?->identifiant ?? 'Système' }}</td>
                        <td><small>{{ $d->commentaire }}</small></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@can('gerer', $pret)
    @foreach([
        'piece' => ['Pièce obligatoire', 'carriere.interims.prets.piece', [
            ['type_piece', 'Type de pièce', 'select', true, $typesPieces], ['date_signature', 'Date de signature', 'date', true, null],
            ['fichier', 'Document signé', 'file', true, null]]],
        'refus' => ['Refus du salarié', 'carriere.interims.prets.refus', [
            ['date_reponse_salarie', 'Date du refus', 'date', true, null], ['commentaire', 'Commentaire', 'textarea', false, null],
            ['fichier', 'Écrit du salarié (facultatif)', 'file', false, null]]],
        'refacturation' => ['Refacturation (sans marge)', 'carriere.interims.prets.refacturation', [
            ['periode_debut', 'Période du', 'date', true, null], ['periode_fin', 'au', 'date', true, null],
            ['salaires', 'Salaires (FCFA)', 'number', true, null], ['charges_sociales', 'Charges sociales (FCFA)', 'number', true, null],
            ['frais_professionnels', 'Frais professionnels remboursés (FCFA)', 'number', false, null],
            ['montant_refacture', 'Montant refacturé (FCFA)', 'number', true, null]]],
        'retour' => ['Retour du salarié', 'carriere.interims.prets.retour', [
            ['date_retour', 'Date de retour', 'date', true, null],
            ['poste_retour_id', 'Poste de retour (vide = poste d\'origine)', 'select', false, $postes->pluck('intitule', 'id')->all()],
            ['commentaire', 'Commentaire', 'textarea', false, null]]],
        'annuler' => ['Annuler le prêt', 'carriere.interims.prets.annuler', [
            ['motif_annulation', 'Motif d\'annulation', 'textarea', true, null]]],
    ] as $cle => [$titre, $route, $champs])
        <div class="modal fade" id="modal-{{ $cle }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" id="form-{{ $cle }}" action="{{ route($route, $pret) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ $titre }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @if($cle === 'refus')
                                <div class="alert alert-info small">Le refus du salarié ne peut entraîner aucune sanction ni mesure défavorable.</div>
                            @endif
                            @foreach($champs as [$nom, $libelle, $type, $requis, $options])
                                <x-field :name="$nom" :label="$libelle" :type="$type" :required="$requis" :options="$options ?? []" />
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endcan
@endsection

@push('js')
@include('carriere.interims.partials._script-formulaire')
<script>
$(function () {
    ['#form-piece', '#form-refus', '#form-refacturation', '#form-retour', '#form-annuler', '#form-demarrer'].forEach(function (selecteur) {
        soumettreFormulaireInterim(selecteur, function (r) {
            const modale = $(selecteur).closest('.modal')[0];
            if (modale) { bootstrap.Modal.getInstance(modale)?.hide(); }
            if (r.alerte) { window.showToast(r.alerte, 'warning'); }
            window.showToastThenReload(r.message || 'Enregistré.');
        });
    });
});
</script>
@endpush

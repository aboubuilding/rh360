@extends('layouts.app')

@section('title', 'Prêts de main-d\'œuvre')
@section('page_title', 'Prêts de main-d\'œuvre à but non lucratif')
@section('page_icon', 'fa-handshake')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Prêts de main-d'œuvre</li>
@endsection

@section('page_actions')
    @can('create', \App\Domain\Interim\Models\PretMainOeuvre::class)
        <a href="{{ route('carriere.interims.prets.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau prêt</a>
    @endcan
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Pendant le prêt, le contrat n'est ni rompu ni suspendu : le salarié reste dans l'effectif et la paie de l'entreprise,
    avec ses avantages conventionnels et son ancienneté. Aucune marge n'est permise sur la refacturation (Code du travail, art. 8).
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Réf.</th><th>Salarié</th><th>Entreprise utilisatrice</th><th>Période</th><th>Statut</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                @forelse($prets as $p)
                    <tr>
                        <td><code>{{ $p->reference }}</code></td>
                        <td>{{ $p->salarie?->nom_complet }}</td>
                        <td>{{ $p->utilisatrice_raison_sociale }}</td>
                        <td>{{ $p->date_debut->format('d/m/Y') }} → {{ ($p->date_retour ?? $p->date_fin)->format('d/m/Y') }}</td>
                        <td><span class="badge bg-{{ $p->statut->couleur() }}">{{ $p->statut->libelle() }}</span></td>
                        <td class="text-end"><a href="{{ route('carriere.interims.prets.show', $p) }}" class="btn btn-sm btn-action"><i class="fas fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun prêt.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $prets->links() }}</div>
</div>
@endsection

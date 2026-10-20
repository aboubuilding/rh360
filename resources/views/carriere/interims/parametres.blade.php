@extends('layouts.app')

@section('title', 'Paramètres des intérims')
@section('page_title', 'Paramètres des intérims')
@section('page_icon', 'fa-sliders-h')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.interims.index') }}">Intérims</a></li>
    <li>Paramètres</li>
@endsection

@section('page_actions')
    <form method="POST" action="{{ route('carriere.interims.parametres.reinitialiser') }}"
          onsubmit="return confirm('Rétablir toutes les valeurs prévues par les textes ?');">
        @csrf
        <button class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Revenir aux valeurs des textes</button>
    </form>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Les valeurs par défaut sont celles du Code du travail (loi n°2021-012) et de la CCIT 2026. Les lignes marquées
    « Non tranché par les textes » sont des choix de gestion. Le barème des salaires minimums se saisit dans
    <a href="{{ route('classification.referentiels.index') }}">Classification › Référentiels</a> (positions et salaires minimums).
</div>

<form method="POST" action="{{ route('carriere.interims.parametres.update') }}">
    @csrf
    @method('PUT')

    @foreach($groupes as $groupe => $lignes)
        <div class="card mb-3">
            <div class="card-header fw-bold">{{ $groupe }}</div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 34%">Paramètre</th>
                            <th style="width: 30%">Valeur appliquée</th>
                            <th>Valeur des textes</th>
                            <th>Référence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lignes as $l)
                            @php
                                $libelleValeur = function ($v) use ($l, $referentiels) {
                                    return match ($l['type']) {
                                        'booleen' => $v ? 'Oui' : 'Non',
                                        'choix' => $l['options'][$v] ?? $v,
                                        'referentiel' => $v ? ($referentiels->firstWhere('id', $v)?->nom ?? '#'.$v) : 'Niveau source « ccit »',
                                        'referentiels' => $v ? $referentiels->whereIn('id', $v)->pluck('nom')->join(', ') : 'Niveau source « convention_sectorielle »',
                                        default => $v === null || $v === '' ? 'Non limité' : $v,
                                    };
                                };
                            @endphp
                            <tr>
                                <td>
                                    {{ $l['libelle'] }}
                                    @if($l['modifie'])<span class="badge bg-warning text-dark ms-1">modifié</span>@endif
                                </td>
                                <td>
                                    @switch($l['type'])
                                        @case('booleen')
                                            <select name="{{ $l['cle'] }}" class="form-select">
                                                <option value="1" @selected($l['valeur'])>Oui</option>
                                                <option value="0" @selected(! $l['valeur'])>Non</option>
                                            </select>
                                            @break
                                        @case('choix')
                                            <select name="{{ $l['cle'] }}" class="form-select">
                                                @foreach($l['options'] as $v => $texte)
                                                    <option value="{{ $v }}" @selected($l['valeur'] === $v)>{{ $texte }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('referentiel')
                                            <select name="{{ $l['cle'] }}" class="form-select">
                                                <option value="">Niveau source « ccit »</option>
                                                @foreach($referentiels as $r)
                                                    <option value="{{ $r->id }}" @selected((int) $l['valeur'] === $r->id)>{{ $r->nom }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('referentiels')
                                            <select name="{{ $l['cle'] }}[]" class="form-select" multiple>
                                                @foreach($referentiels as $r)
                                                    <option value="{{ $r->id }}" @selected(in_array($r->id, (array) $l['valeur'], true))>{{ $r->nom }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('entier')
                                        @case('entier_facultatif')
                                            <input type="number" name="{{ $l['cle'] }}" value="{{ $l['valeur'] }}"
                                                   min="{{ $l['min'] ?? 0 }}" class="form-control @error($l['cle']) is-invalid @enderror">
                                            @break
                                        @default
                                            <input type="text" name="{{ $l['cle'] }}" value="{{ $l['valeur'] }}"
                                                   class="form-control @error($l['cle']) is-invalid @enderror">
                                    @endswitch
                                    @error($l['cle'])<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </td>
                                <td class="text-muted">{{ $libelleValeur($l['defaut']) }}</td>
                                <td><small class="text-muted">{{ $l['reference'] }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
</form>
@endsection

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
          onsubmit="return confirm('Rétablir toutes les valeurs par défaut ?');">
        @csrf
        <button class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Rétablir les valeurs par défaut</button>
    </form>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Chaque paramètre affiche sa valeur par défaut. Le barème des salaires minimums se saisit dans
    <a href="{{ route('classification.referentiels.index') }}">Classification › Référentiels</a> (positions et salaires minimums).
</div>

<form method="POST" action="{{ route('carriere.interims.parametres.update') }}">
    @csrf
    @method('PUT')

    @foreach($groupes as $groupe => $lignes)
        <div class="card mb-3">
            <div class="card-header fw-bold">{{ $groupe }}</div>
            <ul class="list-group list-group-flush">
                @foreach($lignes as $l)
                    @php
                        $libelleValeur = function ($v) use ($l, $referentiels, $typesConges) {
                            return match ($l['type']) {
                                'booleen' => $v ? 'Oui' : 'Non',
                                'choix' => $l['options'][$v] ?? $v,
                                'referentiel' => $v ? ($referentiels->firstWhere('id', $v)?->nom ?? '#'.$v) : 'Détection automatique du barème',
                                'referentiels' => $v ? $referentiels->whereIn('id', $v)->pluck('nom')->join(', ') : 'Détection automatique des conventions',
                                'types_conges' => $typesConges->whereIn('code', (array) $v)->pluck('nom')->join(', ') ?: implode(', ', (array) $v),
                                default => $v === null || $v === '' ? 'Non limité' : $v,
                            };
                        };
                    @endphp
                    <li class="list-group-item">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-lg-6">
                                <label class="form-label mb-0" for="parametre-{{ $l['cle'] }}">{{ $l['libelle'] }}</label>
                                @if($l['modifie'])<span class="badge bg-warning text-dark ms-1">modifié</span>@endif
                                <div class="small text-muted">Par défaut : {{ $libelleValeur($l['defaut']) }}</div>
                            </div>
                            <div class="col-12 col-lg-6">
                                @switch($l['type'])
                                    @case('booleen')
                                        <select name="{{ $l['cle'] }}" id="parametre-{{ $l['cle'] }}" class="form-select">
                                            <option value="1" @selected($l['valeur'])>Oui</option>
                                            <option value="0" @selected(! $l['valeur'])>Non</option>
                                        </select>
                                        @break
                                    @case('choix')
                                        <select name="{{ $l['cle'] }}" id="parametre-{{ $l['cle'] }}" class="form-select">
                                            @foreach($l['options'] as $v => $texte)
                                                <option value="{{ $v }}" @selected($l['valeur'] === $v)>{{ $texte }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('referentiel')
                                        <select name="{{ $l['cle'] }}" id="parametre-{{ $l['cle'] }}" class="form-select">
                                            <option value="">Détection automatique du barème</option>
                                            @foreach($referentiels as $r)
                                                <option value="{{ $r->id }}" @selected((int) $l['valeur'] === $r->id)>{{ $r->nom }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('referentiels')
                                        <select name="{{ $l['cle'] }}[]" id="parametre-{{ $l['cle'] }}" class="form-select" multiple>
                                            @foreach($referentiels as $r)
                                                <option value="{{ $r->id }}" @selected(in_array($r->id, (array) $l['valeur'], true))>{{ $r->nom }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('types_conges')
                                        <select name="{{ $l['cle'] }}[]" id="parametre-{{ $l['cle'] }}" class="form-select" multiple>
                                            @foreach($typesConges as $t)
                                                <option value="{{ $t->code }}" @selected(in_array($t->code, (array) $l['valeur'], true))>{{ $t->nom }}</option>
                                            @endforeach
                                        </select>
                                        @break
                                    @case('entier')
                                    @case('entier_facultatif')
                                        <input type="number" name="{{ $l['cle'] }}" id="parametre-{{ $l['cle'] }}" value="{{ $l['valeur'] }}"
                                               min="{{ $l['min'] ?? 0 }}" class="form-control @error($l['cle']) is-invalid @enderror">
                                        @break
                                    @default
                                        <input type="text" name="{{ $l['cle'] }}" id="parametre-{{ $l['cle'] }}" value="{{ $l['valeur'] }}"
                                               class="form-control @error($l['cle']) is-invalid @enderror">
                                @endswitch
                                @error($l['cle'])<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
</form>
@endsection

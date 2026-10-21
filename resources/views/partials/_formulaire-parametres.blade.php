{{-- Formulaire générique des paramètres d'un module, par groupes.
     Variables : $groupes, $referentiels, $typesConges, $routeEnregistrer --}}
<form method="POST" action="{{ $routeEnregistrer }}">
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
                                    @case('decimal')
                                        <input type="number" step="0.5" name="{{ $l['cle'] }}" id="parametre-{{ $l['cle'] }}" value="{{ $l['valeur'] }}"
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

@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'options' => [],
    'required' => false,
    'help' => null,
    'id' => null,
])

@php
    // Nom « à points » pour old() et les erreurs : lignes[0][montant] → lignes.0.montant
    $cle = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    // Identifiant unique par rendu : plusieurs modales d'une même page peuvent porter
    // un champ de même nom (ex. « motif ») ; le libellé doit pointer vers le bon champ.
    $id = $id ?? 'champ-'.str_replace('.', '-', $cle).'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));

    $normaliser = function ($v) use ($type) {
        if ($v instanceof \BackedEnum) {
            return $v->value;
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format($type === 'datetime-local' ? 'Y-m-d\TH:i' : 'Y-m-d');
        }
        return $v;
    };

    $valeur = $type === 'password' || $type === 'file'
        ? null
        : $normaliser(old($cle, $value));

    $enErreur = $errors->has($cle);
@endphp

<div class="mb-3">
    @if ($type === 'checkbox')
        <div class="form-check">
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="1"
                   {{ $attributes->class(['form-check-input', 'is-invalid' => $enErreur]) }}
                   @checked(filter_var($valeur, FILTER_VALIDATE_BOOLEAN))>
            <label class="form-check-label" for="{{ $id }}">
                {{ $label }} @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
            </label>
            @error($cle)<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @else
        @if ($label)
            <label class="form-label" for="{{ $id }}">
                {{ $label }} @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
            </label>
        @endif

        @if ($type === 'select')
            <select name="{{ $name }}" id="{{ $id }}" @required($required)
                    {{ $attributes->class(['form-select', 'is-invalid' => $enErreur]) }}>
                {{-- Une seule option vide : celle fournie par la vue (clé '' ou null) sert de libellé --}}
                <option value="">{{ $options[''] ?? '— Choisir —' }}</option>
                @foreach ($options as $cleOption => $libelle)
                    @continue((string) $cleOption === '')
                    <option value="{{ $cleOption }}" @selected((string) $valeur === (string) $cleOption)>{{ $libelle }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea name="{{ $name }}" id="{{ $id }}" @required($required)
                      {{ $attributes->merge(['rows' => 3])->class(['form-control', 'is-invalid' => $enErreur]) }}>{{ $valeur }}</textarea>
        @else
            <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" @required($required)
                   @if (! is_null($valeur)) value="{{ $valeur }}" @endif
                   {{ $attributes->class(['form-control', 'is-invalid' => $enErreur]) }}>
        @endif

        @error($cle)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @endif

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>

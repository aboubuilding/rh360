@php $v = fn($k, $d = null) => old($k, $donnees[$k] ?? $d); @endphp

<div class="row">
    <div class="col-md-6">
        <x-field name="nom" label="Nom" required :value="$v('nom')" />
    </div>
    <div class="col-md-6">
        <x-field name="prenoms" label="Prénoms" required :value="$v('prenoms')" />
    </div>
    <div class="col-md-4">
        <x-field name="sexe" label="Sexe" type="select" :value="$v('sexe')"
                 :options="['' => '—', 'M' => 'Masculin', 'F' => 'Féminin']" />
    </div>
    <div class="col-md-4">
        <x-field name="date_naissance" label="Date de naissance" type="date"
                 :value="$v('date_naissance')" />
    </div>
    <div class="col-md-4">
        <x-field name="lieu_naissance" label="Lieu de naissance" :value="$v('lieu_naissance')" />
    </div>

    <div class="col-md-6">
        <x-field name="nationalite" label="Nationalité" :value="$v('nationalite', 'Togolaise')" />
    </div>
    <div class="col-md-6">
        <x-field name="photo" label="Photo" type="file" help="PNG/JPG — 2 Mo max." />
    </div>

    <div class="col-md-4">
        <x-field name="type_piece" label="Type de pièce" type="select" :value="$v('type_piece')"
                 :options="['' => '—'] + \App\Domain\Personnel\Enums\TypePieceIdentite::options()" />
    </div>
    <div class="col-md-4">
        <x-field name="numero_piece" label="Numéro de pièce" :value="$v('numero_piece')" />
    </div>
    <div class="col-md-4">
        <x-field name="date_expiration_piece" label="Expiration pièce" type="date"
                 :value="$v('date_expiration_piece')" />
    </div>
</div>
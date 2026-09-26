@php $v = fn($k, $d = null) => old($k, $donnees[$k] ?? $d); @endphp

<div class="row">
    <div class="col-md-6">
        <x-field name="telephone_principal" label="Téléphone principal"
                 :value="$v('telephone_principal')" />
    </div>
    <div class="col-md-6">
        <x-field name="telephone_secondaire" label="Téléphone secondaire"
                 :value="$v('telephone_secondaire')" />
    </div>

    <div class="col-md-6">
        <x-field name="email_personnel" label="Email personnel" type="email"
                 :value="$v('email_personnel')" />
    </div>
    <div class="col-md-6">
        <x-field name="email_professionnel" label="Email professionnel" type="email"
                 :value="$v('email_professionnel')" />
    </div>

    <div class="col-md-12">
        <x-field name="adresse" label="Adresse complète" type="textarea"
                 :value="$v('adresse')" />
    </div>

    <div class="col-md-6">
        <x-field name="ville" label="Ville" :value="$v('ville')" />
    </div>
    <div class="col-md-6">
        <x-field name="pays_residence" label="Pays de résidence"
                 :value="$v('pays_residence', 'Togo')" />
    </div>

    @can('permission', 'sensitive.gps')
        <div class="col-md-6">
            <x-field name="gps_latitude" label="Latitude GPS" type="number"
                     :value="$v('gps_latitude')" help="Entre -90 et 90" />
        </div>
        <div class="col-md-6">
            <x-field name="gps_longitude" label="Longitude GPS" type="number"
                     :value="$v('gps_longitude')" help="Entre -180 et 180" />
        </div>
    @endcan
</div>
@php $v = fn($k, $d = null) => old($k, $donnees[$k] ?? $d); @endphp

<div class="alert alert-info small mb-3">
    <i class="fas fa-lock"></i>
    Ces informations sont confidentielles et ne seront visibles qu'aux utilisateurs habilités.
</div>

@can('permission', 'sensitive.social_health')
<h6 class="text-muted mb-3"><i class="fas fa-shield-alt"></i> Protection sociale</h6>
<div class="row">
    <div class="col-md-4">
        <x-field name="numero_cnss" label="Numéro CNSS" :value="$v('numero_cnss')" />
    </div>
    <div class="col-md-4">
        <x-field name="date_immatriculation_cnss" label="Date d'immatriculation CNSS" type="date"
                 :value="$v('date_immatriculation_cnss')" />
    </div>
    <div class="col-md-4">
        <x-field name="numero_amu" label="Numéro AMU" :value="$v('numero_amu')" />
    </div>
    <div class="col-md-12">
        <x-field name="organisme_assurance" label="Organisme d'assurance"
                 :value="$v('organisme_assurance')" />
    </div>
</div>
@endcan

@can('permission', 'sensitive.banking')
<hr class="my-4">

<h6 class="text-muted mb-3"><i class="fas fa-university"></i> Coordonnées bancaires</h6>
<div class="row">
    <div class="col-md-6">
        <x-field name="banque" label="Banque" :value="$v('banque')" />
    </div>
    <div class="col-md-6">
        <x-field name="compte_bancaire" label="Compte bancaire (IBAN/RIB)"
                 :value="$v('compte_bancaire')" />
    </div>
    <div class="col-md-6">
        <x-field name="mode_paiement" label="Mode de paiement" type="select"
                 :value="$v('mode_paiement')"
                 :options="[
                     '' => '—',
                     'Virement' => 'Virement bancaire',
                     'Chèque' => 'Chèque',
                     'Espèces' => 'Espèces',
                     'Mobile Money' => 'Mobile Money',
                 ]" />
    </div>
</div>
@endcan

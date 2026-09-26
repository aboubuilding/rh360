@php $v = fn($k, $d = null) => old($k, $donnees[$k] ?? $d); @endphp

<div class="row">
    <div class="col-md-6">
        <x-field name="situation_matrimoniale" label="Situation matrimoniale" type="select"
                 :value="$v('situation_matrimoniale')"
                 :options="[
                     '' => '—',
                     'Célibataire' => 'Célibataire',
                     'Marié(e)' => 'Marié(e)',
                     'Divorcé(e)' => 'Divorcé(e)',
                     'Veuf(ve)' => 'Veuf(ve)',
                 ]" />
    </div>
</div>

<hr class="my-4">
<h6 class="text-muted mb-3"><i class="fas fa-phone-alt"></i> Contact d'urgence</h6>

<div class="row">
    <div class="col-md-4">
        <x-field name="contact_urgence_nom" label="Nom du contact"
                 :value="$v('contact_urgence_nom')" />
    </div>
    <div class="col-md-4">
        <x-field name="contact_urgence_lien" label="Lien (ex. Époux, Père...)"
                 :value="$v('contact_urgence_lien')" />
    </div>
    <div class="col-md-4">
        <x-field name="contact_urgence_telephone" label="Téléphone"
                 :value="$v('contact_urgence_telephone')" />
    </div>
</div>

<div class="alert alert-info small mt-3">
    <i class="fas fa-info-circle"></i>
    Les membres du foyer (conjoint, enfants) peuvent être ajoutés après création du salarié,
    depuis la fiche salarié, onglet <strong>Famille</strong>.
</div>
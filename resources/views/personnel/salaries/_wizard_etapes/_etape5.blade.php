@php $v = fn($k, $d = null) => old($k, $donnees[$k] ?? $d); @endphp

<h6 class="text-muted mb-3"><i class="fas fa-briefcase"></i> Emploi</h6>
<div class="row">
    <div class="col-md-6">
        <x-field name="date_embauche" label="Date d'embauche" type="date" required
                 :value="$v('date_embauche')" />
    </div>
    <div class="col-md-6">
        <x-field name="date_prise_service" label="Date de prise de service" type="date"
                 :value="$v('date_prise_service')" help="Postérieure ou égale à l'embauche" />
    </div>

    <div class="col-md-6">
        <x-field name="structure_id" label="Structure d'affectation" type="select"
                 :value="$v('structure_id')"
                 :options="['' => '—'] + $structures->pluck('nom', 'id')->all()" />
    </div>
    <div class="col-md-6">
        <x-field name="poste_id" label="Poste" type="select"
                 :value="$v('poste_id')"
                 :options="['' => '—'] + $postes->pluck('intitule', 'id')->all()" />
    </div>

    <div class="col-md-6">
        <x-field name="position_classification_id" label="Classification (catégorie – classe – échelon)" type="select"
                 :value="$v('position_classification_id')"
                 :options="['' => '—'] + $positions->mapWithKeys(fn ($p) => [
                     $p->id => $p->code . ' — ' . collect([$p->categorie?->libelle, $p->classe?->libelle, $p->echelon?->libelle])->filter()->implode(' – '),
                 ])->all()" />
    </div>
    <div class="col-md-6">
        <x-field name="date_effet_echelon" label="Date d'effet de l'échelon" type="date"
                 :value="$v('date_effet_echelon')" help="Date de référence pour le prochain avancement (défaut : prise de service)" />
    </div>

    <div class="col-md-6">
        <x-field name="lieu_affectation" label="Lieu d'affectation"
                 :value="$v('lieu_affectation')" />
    </div>
    <div class="col-md-6">
        <x-field name="statut_emploi" label="Statut d'emploi" type="select"
                 :value="$v('statut_emploi', 'Actif')"
                 :options="\App\Domain\Personnel\Enums\StatutEmploi::options()" />
    </div>
</div>

<hr class="my-4">

<h6 class="text-muted mb-3"><i class="fas fa-file-signature"></i> Contrat initial</h6>
<div class="alert alert-warning small mb-3">
    <i class="fas fa-info-circle"></i>
    Pour enregistrer un contrat complet avec avenants et circuit de validation, utilisez
    le module <strong>Contrats & avenants</strong> après création du salarié.
</div>

<div class="row">
    <div class="col-md-6">
        <x-field name="type_contrat" label="Type de contrat" type="select"
                 :value="$v('type_contrat')"
                 :options="[
                     '' => '—',
                     'CDI' => 'CDI',
                     'CDD' => 'CDD',
                     'Stage' => 'Stage',
                     'Intérim' => 'Intérim',
                     'Apprentissage' => 'Apprentissage',
                 ]" />
    </div>
    <div class="col-md-6">
        <x-field name="reference_contrat" label="Référence du contrat"
                 :value="$v('reference_contrat')" />
    </div>
    <div class="col-md-6">
        <x-field name="date_contrat" label="Date du contrat" type="date"
                 :value="$v('date_contrat')" />
    </div>
    <div class="col-md-6">
        <x-field name="date_fin_contrat" label="Date de fin (si CDD)" type="date"
                 :value="$v('date_fin_contrat')" />
    </div>
</div>
@extends('layouts.app')

@section('title', 'Agences d\'intérim')
@section('page_title', 'Entreprises de travail temporaire')
@section('page_icon', 'fa-building')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Agences d'intérim</li>
@endsection

@section('page_actions')
    @can('create', \App\Domain\Interim\Models\EntrepriseInterim::class)
        <button type="button" class="btn btn-primary js-agence" data-action="{{ route('carriere.interims.agences.store') }}" data-methode="POST">
            <i class="fas fa-plus"></i> Nouvelle agence
        </button>
    @endcan
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Une mission ne peut être confiée qu'à une entreprise de travail temporaire agréée par le ministre chargé du travail
. L'agence est le seul employeur de l'intérimaire : aucune paie ni cotisation n'est
    calculée, seules ses factures sont enregistrées.
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Raison sociale</th>
                    <th>Agrément</th>
                    <th>Statut</th>
                    <th class="text-end">Missions</th>
                    <th class="text-end">Factures</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agences as $a)
                    <tr>
                        <td><strong>{{ $a->raison_sociale }}</strong><br><small class="text-muted">{{ $a->contact }} {{ $a->telephone }}</small></td>
                        <td>
                            {{ $a->numero_agrement ?? '—' }}
                            @if($a->date_agrement)<br><small class="text-muted">du {{ $a->date_agrement->format('d/m/Y') }}{{ $a->date_fin_validite_agrement ? ' au '.$a->date_fin_validite_agrement->format('d/m/Y') : '' }}</small>@endif
                        </td>
                        <td>
                            @if($a->agrementValideSur(today(), today()))
                                <span class="badge bg-success">Agrément valide</span>
                            @else
                                <span class="badge bg-danger">{{ $a->numero_agrement ? $a->statut_agrement->libelle() : 'Sans agrément' }}</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $a->missions_count }}</td>
                        <td class="text-end">{{ number_format($a->factures->sum('montant'), 0, ',', ' ') }} FCFA</td>
                        <td class="text-end">
                            @can('gerer', $a)
                                <button type="button" class="btn btn-sm btn-action js-agence" title="Modifier"
                                        data-action="{{ route('carriere.interims.agences.update', $a) }}" data-methode="PUT"
                                        data-donnees="{{ json_encode([
                                            'raison_sociale' => $a->raison_sociale, 'numero_agrement' => $a->numero_agrement,
                                            'date_agrement' => $a->date_agrement?->format('Y-m-d'),
                                            'date_fin_validite_agrement' => $a->date_fin_validite_agrement?->format('Y-m-d'),
                                            'statut_agrement' => $a->statut_agrement->value, 'adresse' => $a->adresse,
                                            'contact' => $a->contact, 'telephone' => $a->telephone, 'email' => $a->email,
                                        ]) }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-action js-facture" title="Enregistrer une facture"
                                        data-action="{{ route('carriere.interims.agences.factures.store', $a) }}">
                                    <i class="fas fa-file-invoice"></i>
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune agence.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modal-agence" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-agence">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Entreprise de travail temporaire</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12"><x-field name="raison_sociale" label="Raison sociale" required /></div>
                        <div class="col-md-4"><x-field name="numero_agrement" label="N° d'agrément" /></div>
                        <div class="col-md-4"><x-field name="date_agrement" label="Date d'agrément" type="date" /></div>
                        <div class="col-md-4"><x-field name="date_fin_validite_agrement" label="Fin de validité" type="date" /></div>
                        <div class="col-md-4"><x-field name="statut_agrement" label="Statut de l'agrément" type="select" required :options="$statuts" value="valide" /></div>
                        <div class="col-md-8"><x-field name="adresse" label="Adresse" /></div>
                        <div class="col-md-4"><x-field name="contact" label="Contact" /></div>
                        <div class="col-md-4"><x-field name="telephone" label="Téléphone" /></div>
                        <div class="col-md-4"><x-field name="email" label="E-mail" type="email" /></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-facture" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="form-facture" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Facture de l'agence</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-field name="numero_facture" label="N° de facture" required />
                    <x-field name="date_facture" label="Date de facture" type="date" required :value="now()->format('Y-m-d')" />
                    <div class="row">
                        <div class="col-md-6"><x-field name="periode_debut" label="Période du" type="date" /></div>
                        <div class="col-md-6"><x-field name="periode_fin" label="au" type="date" /></div>
                    </div>
                    <x-field name="montant" label="Montant (FCFA)" type="number" required />
                    <x-field name="piece" label="Facture (PDF, image)" type="file" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
@include('carriere.interims.partials._script-formulaire')
<script>
$(function () {
    $(document).on('click', '.js-agence', function () {
        const $form = $('#form-agence');
        $form[0].reset();
        $form.attr('action', $(this).data('action'));
        $form.find('[name="_method"]').val($(this).data('methode'));
        $.each($(this).data('donnees') || {}, (champ, valeur) => $form.find('[name="' + champ + '"]').val(valeur ?? ''));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-agence')).show();
    });
    $(document).on('click', '.js-facture', function () {
        const $form = $('#form-facture');
        $form[0].reset();
        $form.attr('action', $(this).data('action'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-facture')).show();
    });
    ['#form-agence', '#form-facture'].forEach(function (selecteur) {
        soumettreFormulaireInterim(selecteur, function (r) {
            bootstrap.Modal.getInstance($(selecteur).closest('.modal')[0])?.hide();
            window.showToastThenReload(r.message || 'Enregistré.');
        });
    });
});
</script>
@endpush

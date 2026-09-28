@extends('layouts.app')

@section('title', 'Acte HS ' . $acte->reference)
@section('page_title', 'Acte d\'heures supplémentaires — ' . $acte->reference)
@section('page_icon', 'fa-clock')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.heures-supp.index') }}">Heures supplémentaires</a></li>
    <li>{{ $acte->reference }}</li>
@endsection

@section('contenu')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong>Informations</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Référence</dt>
                    <dd class="col-sm-6"><code>{{ $acte->reference }}</code></dd>

                    <dt class="col-sm-6">Salarié</dt>
                    <dd class="col-sm-6">{{ $acte->salarie?->nom_complet }}</dd>

                    <dt class="col-sm-6">Période de travail</dt>
                    <dd class="col-sm-6">
                        {{ $acte->debut_travail?->format('d/m/Y') }} → {{ $acte->fin_travail?->format('d/m/Y') }}
                    </dd>

                    <dt class="col-sm-6">Période de paiement</dt>
                    <dd class="col-sm-6">{{ $acte->periodePaiement?->libelle }}</dd>

                    <dt class="col-sm-6">Salaire de base figé</dt>
                    <dd class="col-sm-6">{{ number_format($acte->salaire_base_fige, 0, ',', ' ') }} FCFA</dd>

                    <dt class="col-sm-6">Taux horaire</dt>
                    <dd class="col-sm-6">{{ number_format($acte->taux_horaire, 2) }} FCFA</dd>

                    <dt class="col-sm-6">Total heures</dt>
                    <dd class="col-sm-6 fw-bold">{{ number_format($acte->totalHeures(), 2) }} h</dd>

                    <dt class="col-sm-6">Montant total</dt>
                    <dd class="col-sm-6 fw-bold text-success fs-5">
                        {{ number_format($acte->montantTotal(), 0, ',', ' ') }} FCFA
                    </dd>

                    @if($acte->motif)
                        <dt class="col-sm-12 mt-3">Motif</dt>
                        <dd class="col-sm-12">{{ $acte->motif }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Détail par taux</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Libellé</th>
                            <th class="text-end">Heures</th>
                            <th class="text-end">Coefficient</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detail as $d)
                            @if($d['heures'] > 0)
                                <tr>
                                    <td>{{ $d['libelle'] }}</td>
                                    <td class="text-end">{{ number_format($d['heures'], 2) }}</td>
                                    <td class="text-end">
                                        @switch($d['libelle'])
                                            @case('HS 20 %') 1.20 @break
                                            @case('HS 40 %') 1.40 @break
                                            @case('HS 65 % jour') 1.65 @break
                                            @case('HS 65 % nuit') 1.65 @break
                                            @case('HS 100 %') 2.00 @break
                                        @endswitch
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($d['montant'], 0, ',', ' ') }}</td>
                                </tr>
                            @endif
                        @endforeach
                        <tr class="table-success">
                            <th colspan="3" class="text-end">TOTAL</th>
                            <th class="text-end">{{ number_format($acte->montantTotal(), 0, ',', ' ') }} FCFA</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
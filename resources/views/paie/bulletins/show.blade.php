@extends('layouts.app')

@section('title', 'Bulletin de paie')
@section('page_title', 'Bulletin de paie — ' . $bulletin->salarie->nom_complet)
@section('page_icon', 'fa-file-invoice-dollar')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('paie.periodes.index') }}">Périodes</a></li>
    <li><a href="{{ route('paie.periodes.show', $bulletin->periode) }}">{{ $bulletin->periode->libelle }}</a></li>
    <li>Bulletin</li>
@endsection

@section('page_actions')
    <a href="{{ route('paie.bulletins.pdf', $bulletin) }}" class="btn btn-primary">
        <i class="fas fa-file-pdf"></i> Télécharger PDF
    </a>
    <a href="{{ route('paie.periodes.show', $bulletin->periode) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
@endsection

@section('contenu')
<div class="row">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><strong>Identité du salarié</strong></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nom complet</dt>
                    <dd class="col-sm-7">{{ $bulletin->salarie?->nom_complet }}</dd>

                    <dt class="col-sm-5">Matricule</dt>
                    <dd class="col-sm-7"><code>{{ $bulletin->salarie?->matricule }}</code></dd>

                    <dt class="col-sm-5">N° CNSS</dt>
                    <dd class="col-sm-7">{{ $bulletin->salarie?->numero_cnss ?? '—' }}</dd>

                    <dt class="col-sm-5">Poste</dt>
                    <dd class="col-sm-7">
                        {{ $bulletin->salarie?->affectationCourante?->poste?->intitule ?? '—' }}
                    </dd>

                    <dt class="col-sm-5">Période</dt>
                    <dd class="col-sm-7"><strong>{{ $bulletin->periode?->libelle }}</strong></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><strong>Détail du bulletin</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Libellé</th>
                            <th>Nature</th>
                            <th class="text-end">Base</th>
                            <th class="text-end">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bulletin->lignes->where('nature', 'gain') as $ligne)
                            <tr>
                                <td><code>{{ $ligne->code }}</code></td>
                                <td>{{ $ligne->libelle }}</td>
                                <td><span class="badge bg-success">Gain</span></td>
                                <td class="text-end">—</td>
                                <td class="text-end">{{ number_format($ligne->montant, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach

                        <tr class="table-light">
                            <td colspan="4" class="text-end"><strong>Total brut</strong></td>
                            <td class="text-end fw-bold">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }}</td>
                        </tr>

                        @foreach($bulletin->lignes->where('nature', 'cotisation') as $ligne)
                            <tr>
                                <td><code>{{ $ligne->code }}</code></td>
                                <td>{{ $ligne->libelle }}</td>
                                <td><span class="badge bg-warning text-dark">Cotisation</span></td>
                                <td class="text-end">—</td>
                                <td class="text-end text-danger">-{{ number_format($ligne->montant, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach

                        @foreach($bulletin->lignes->where('nature', 'retenue') as $ligne)
                            <tr>
                                <td><code>{{ $ligne->code }}</code></td>
                                <td>{{ $ligne->libelle }}</td>
                                <td><span class="badge bg-danger">Retenue</span></td>
                                <td class="text-end">—</td>
                                <td class="text-end text-danger">-{{ number_format($ligne->montant, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach

                        <tr class="table-light">
                            <td colspan="4" class="text-end"><strong>Total retenues</strong></td>
                            <td class="text-end fw-bold text-danger">
                                -{{ number_format($bulletin->montant_retenues, 0, ',', ' ') }}
                            </td>
                        </tr>

                        <tr class="table-success">
                            <td colspan="4" class="text-end fs-5"><strong>NET À PAYER</strong></td>
                            <td class="text-end fw-bold fs-5">
                                {{ number_format($bulletin->montant_net, 0, ',', ' ') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><strong>Détail du calcul fiscal</strong></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-6">Brut imposable</dt>
                    <dd class="col-sm-6 text-end">{{ number_format($bulletin->brut_imposable, 0, ',', ' ') }} FCFA</dd>

                    <dt class="col-sm-6">Cotisations déductibles (CNSS + AMU)</dt>
                    <dd class="col-sm-6 text-end">{{ number_format($bulletin->retenues_sociales_deductibles, 0, ',', ' ') }} FCFA</dd>

                    <dt class="col-sm-6">Abattement professionnel</dt>
                    <dd class="col-sm-6 text-end">{{ number_format($bulletin->abattement_professionnel, 0, ',', ' ') }} FCFA</dd>

                    <dt class="col-sm-6">Déduction charges de famille</dt>
                    <dd class="col-sm-6 text-end">{{ number_format($bulletin->deduction_charges_famille, 0, ',', ' ') }} FCFA</dd>

                    <dt class="col-sm-6 fw-bold">Base imposable</dt>
                    <dd class="col-sm-6 text-end fw-bold">{{ number_format($bulletin->base_imposable, 0, ',', ' ') }} FCFA</dd>

                    <dt class="col-sm-6 fw-bold text-danger">IRPP</dt>
                    <dd class="col-sm-6 text-end fw-bold text-danger">
                        {{ number_format($bulletin->montant_irpp, 0, ',', ' ') }} FCFA
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
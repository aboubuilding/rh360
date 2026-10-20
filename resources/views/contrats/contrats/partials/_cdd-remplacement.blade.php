{{-- RG-REM-01 à 05 — CDD : motif de recours, terme, caractère provisoire, indemnité de fin de contrat --}}
@if($contrat->type_contrat === \App\Domain\Contrats\Enums\TypeContrat::CDD->value)
    @php($finCdd = app(\App\Domain\Contrats\Services\IndemniteFinCddCalculator::class)->evaluer($contrat))
    <div class="card mb-3">
        <div class="card-header">CDD — motif de recours et terme</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Motif de recours</dt><dd class="col-sm-8">{{ $contrat->motif_recours?->libelle() ?? '—' }}</dd>
                <dt class="col-sm-4">Salarié remplacé</dt><dd class="col-sm-8">{{ $contrat->titulaireRemplace?->nom_complet ?? '—' }}</dd>
                <dt class="col-sm-4">Terme</dt>
                <dd class="col-sm-8">{{ $contrat->date_fin?->format('d/m/Y') ?? '' }}{{ $contrat->terme_evenement ? ($contrat->date_fin ? ' ou ' : '').mb_strtolower($contrat->terme_evenement->libelle()) : '' }}</dd>
                @if($contrat->journalier_paye_fin_journee)
                    <dt class="col-sm-4">Journalier</dt><dd class="col-sm-8">Payé en fin de journée (contrat écrit non obligatoire)</dd>
                @endif
                @if($contrat->mention_caractere_provisoire)
                    <dt class="col-sm-4">Caractère provisoire</dt><dd class="col-sm-8">{{ $contrat->mention_caractere_provisoire }}</dd>
                @endif
                <dt class="col-sm-4">Indemnité de fin de contrat</dt>
                <dd class="col-sm-8">
                    <span class="badge bg-{{ $finCdd['due'] ? 'warning text-dark' : 'secondary' }}">{{ $finCdd['due'] ? 'Due' : 'Non due' }}</span>
                    <small class="text-muted">{{ $finCdd['raison'] }}</small>
                </dd>
            </dl>

            @if($contrat->estSigne())
                @can('permission', 'contrats.manage')
                    <form method="POST" action="{{ route('contrats.contrats.fin-cdd', $contrat) }}" class="row g-2 mt-2 border-top pt-2">
                        @csrf
                        <div class="col-md-5">
                            <x-field name="motif_fin" label="Motif de fin" type="select" :value="$contrat->motif_fin"
                                     :options="[null => '—'] + \App\Domain\Contrats\Enums\MotifFinContrat::options()" />
                        </div>
                        <div class="col-md-5">
                            <x-field name="refus_cdi_equivalent_le" label="Refus d'un CDI au moins équivalent le" type="date"
                                     :value="$contrat->refus_cdi_equivalent_le?->format('Y-m-d')" />
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-outline-primary w-100 mb-3">Enregistrer</button>
                        </div>
                    </form>
                @endcan
            @endif
        </div>
    </div>
@endif

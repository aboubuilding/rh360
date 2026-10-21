{{-- Congé payé : droits par lot, allocation de congé et rappels pendant le congé.
     Variables : $lots, $allocation, $demande --}}
<div class="card mt-3">
    <div class="card-header d-flex flex-wrap justify-content-between gap-2">
        <strong>Droits par année d'acquisition</strong>
        <small class="text-muted">au {{ $demande->date_debut->format('d/m/Y') }}, date de départ — les jours sont pris sur l'année la plus ancienne d'abord</small>
    </div>
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Année</th>
                <th>Acquis</th>
                <th>Pris ou réservés</th>
                <th>Reste</th>
                <th>Utilisable</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lots as $l)
                <tr>
                    <td><strong>{{ $l['lot'] }}</strong></td>
                    <td>{{ number_format($l['acquis'], 2, ',', ' ') }}</td>
                    <td>{{ number_format($l['pris'], 2, ',', ' ') }}</td>
                    <td>{{ number_format($l['reste'], 2, ',', ' ') }}</td>
                    <td>
                        @if($l['expire'])
                            <span class="badge bg-danger">Au-delà du report depuis le {{ $l['expire_le']->format('d/m/Y') }}</span>
                        @elseif($l['ouvert'])
                            <span class="badge bg-success">Oui</span> <small class="text-muted">jusqu'au {{ $l['expire_le']->copy()->subDay()->format('d/m/Y') }}</small>
                        @else
                            <span class="badge bg-secondary">À partir du {{ $l['ouvert_le']?->format('d/m/Y') }}</span>
                            <small class="text-muted">(par anticipation, avec accord)</small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">Aucun droit enregistré.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card mt-3">
    <div class="card-header"><strong>Allocation de congé</strong></div>
    <div class="card-body">
        @if($allocation)
            <dl class="row mb-0">
                <dt class="col-sm-6">Rémunération des {{ $allocation['mois'] }} dernier(s) mois</dt>
                <dd class="col-sm-6">{{ number_format($allocation['remuneration'], 0, ',', ' ') }} FCFA</dd>
                <dt class="col-sm-6">Allocation par jour</dt>
                <dd class="col-sm-6">{{ number_format($allocation['par_jour'], 2, ',', ' ') }} FCFA</dd>
                <dt class="col-sm-6">Allocation pour {{ (float) $allocation['jours'] }} jour(s)</dt>
                <dd class="col-sm-6"><strong>{{ number_format($allocation['montant'], 0, ',', ' ') }} FCFA</strong></dd>
            </dl>
            @if($allocation['exclues'])
                <div class="small text-muted mt-2">Rubriques exclues de la base : {{ implode(', ', $allocation['exclues']) }}.</div>
            @endif
        @else
            <span class="text-muted">Aucun bulletin sur les 12 mois précédant le départ : l'allocation sera calculée sur les bulletins disponibles.</span>
        @endif
    </div>
</div>

@if($demande->rappels->isNotEmpty())
    <div class="card mt-3">
        <div class="card-header"><strong>Rappels pendant le congé</strong></div>
        <ul class="list-group list-group-flush">
            @foreach($demande->rappels as $r)
                <li class="list-group-item">
                    Du {{ $r->du->format('d/m/Y') }} au {{ $r->au->format('d/m/Y') }} ({{ (float) $r->jours }} jour(s)) —
                    reprise repoussée du {{ $r->reprise_avant->format('d/m/Y') }} au {{ $r->reprise_apres->format('d/m/Y') }}.
                    <div class="small text-muted">{{ $r->motif }}</div>
                </li>
            @endforeach
        </ul>
    </div>
@endif

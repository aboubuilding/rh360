{{-- Anomalies du dernier calcul de la période. Variables : $anomalies --}}
@if($anomalies->isNotEmpty())
    @php $bloquantes = $anomalies->where('gravite', \App\Domain\Paie\Models\AnomaliePaie::BLOQUANTE)->count(); @endphp
    <div class="card mb-3 border-{{ $bloquantes ? 'danger' : 'warning' }}">
        <div class="card-header d-flex flex-wrap justify-content-between gap-2">
            <strong><i class="fas fa-exclamation-triangle"></i> Anomalies du calcul</strong>
            @if($bloquantes)
                <span class="text-danger">{{ $bloquantes }} bloquante(s) : la période ne peut pas être validée.</span>
            @endif
        </div>
        <ul class="list-group list-group-flush">
            @foreach($anomalies as $a)
                <li class="list-group-item d-flex flex-wrap gap-2">
                    <span class="badge bg-{{ $a->estBloquante() ? 'danger' : 'warning text-dark' }}">{{ $a->estBloquante() ? 'Bloquante' : 'Alerte' }}</span>
                    @if($a->salarie)
                        <strong>{{ $a->salarie->nom_complet }}</strong>
                    @endif
                    <span>{{ $a->message }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

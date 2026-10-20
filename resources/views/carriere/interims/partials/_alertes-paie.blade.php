{{-- Intérims internes : blocage de la validation (RG-INT-09) et anomalies de calcul (RG-INT-06), pour une période non figée. --}}
@if(! $periode->estFigee())
    @php
        $bloquantsInterim = app(\App\Domain\Interim\Services\ControleClotureInterims::class)->interimsBloquants($periode);
        $anomaliesInterim = app(\App\Domain\Interim\Services\PreparateurPaieInterims::class)->calculer($periode)->anomalies;
    @endphp

    @if($bloquantsInterim->isNotEmpty())
        <div class="alert alert-danger">
            <i class="fas fa-lock"></i>
            <strong>Validation bloquée :</strong> {{ $bloquantsInterim->count() }} intérim(s) à régulariser sans décision
            de reclassement ou de retour.
            <ul class="mb-0 mt-1">
                @foreach($bloquantsInterim as $i)
                    <li>
                        <a href="{{ route('carriere.interims.show', $i) }}">{{ $i->salarie?->nom_complet }}</a>
                        — {{ $i->posteOccupe?->intitule }}, délai expiré le {{ $i->date_limite->format('d/m/Y') }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($anomaliesInterim !== [])
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> <strong>Intérims :</strong>
            <ul class="mb-0 mt-1">
                @foreach($anomaliesInterim as $a)
                    <li><a href="{{ route('carriere.interims.show', $a['interim_id']) }}">Intérim #{{ $a['interim_id'] }}</a> — {{ $a['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">État AVANT</h6>
                @if($mouvement->instantane->avant)
                    <dl class="row mb-0 small">
                        @foreach($mouvement->instantane->avant as $section => $valeurs)
                            <dt class="col-sm-4">{{ $section }}</dt>
                            <dd class="col-sm-8">
                                <pre class="mb-0 bg-light p-2 rounded" style="font-size: 0.75rem;">{{ json_encode($valeurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </dd>
                        @endforeach
                    </dl>
                @else
                    <p class="text-muted mb-0">Aucun état avant capturé.</p>
                @endif
            </div>
            <div class="col-md-6">
                <h6 class="text-muted">État APRÈS</h6>
                @if($mouvement->instantane->apres)
                    <dl class="row mb-0 small">
                        @foreach($mouvement->instantane->apres as $section => $valeurs)
                            <dt class="col-sm-4">{{ $section }}</dt>
                            <dd class="col-sm-8">
                                <pre class="mb-0 bg-light p-2 rounded" style="font-size: 0.75rem;">{{ json_encode($valeurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </dd>
                        @endforeach
                    </dl>
                @else
                    <p class="text-muted mb-0">Aucun état après capturé.</p>
                @endif
            </div>
        </div>
        <hr>
        <small class="text-muted">
            Capturé le {{ $mouvement->instantane->created_at->format('d/m/Y H:i') }} —
            date d'effet : {{ $mouvement->instantane->date_effet?->format('d/m/Y') }}
        </small>
    </div>
</div>
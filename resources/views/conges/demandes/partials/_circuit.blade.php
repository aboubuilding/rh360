<div class="card">
    <div class="card-body">
        @php
            $etapes = [
                ['statut' => 'submitted',   'libelle' => 'Soumise'],
                ['statut' => 'authorized',  'libelle' => 'Autorisée'],
                ['statut' => 'scheduled',   'libelle' => 'Programmée'],
                ['statut' => 'in_progress', 'libelle' => 'En cours'],
                ['statut' => 'resumed',     'libelle' => 'Reprise confirmée'],
            ];
            $statutActuel = $demande->statut->value;
            $indexActuel = array_search($statutActuel, array_column($etapes, 'statut'));
        @endphp

        @if(in_array($statutActuel, ['refused', 'cancelled']))
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i>
                Demande <strong>{{ $demande->statut->libelle() }}</strong>.
            </div>
        @endif

        <div class="d-flex justify-content-between position-relative" style="margin: 20px 0;">
            @foreach($etapes as $i => $e)
                @php
                    $done = $indexActuel !== false && $i <= $indexActuel;
                    $current = $statutActuel === $e['statut'];
                @endphp
                <div class="text-center flex-fill">
                    <div class="rounded-circle bg-{{ $done ? 'success' : 'secondary' }} text-white mx-auto d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px; {{ $current ? 'box-shadow: 0 0 0 4px rgba(25,135,84,0.25);' : '' }}">
                        @if($done && ! $current)
                            <i class="fas fa-check"></i>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="small fw-bold mt-2 {{ $current ? 'text-success' : 'text-muted' }}">
                        {{ $e['libelle'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
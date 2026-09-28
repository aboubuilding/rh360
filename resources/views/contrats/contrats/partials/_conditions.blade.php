<div class="card">
    <div class="card-body">
        @if(!empty($contrat->conditions))
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Champ</th>
                        <th>Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contrat->conditions as $cle => $valeur)
                        <tr>
                            <td><strong>{{ $cle }}</strong></td>
                            <td>
                                @if(is_array($valeur))
                                    <pre class="mb-0">{{ json_encode($valeur, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                @else
                                    {{ $valeur }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted mb-0">Aucune condition particulière enregistrée.</p>
        @endif

        @if($contrat->regle_figee)
            <hr>
            <h6 class="text-muted">Règle figée à la création</h6>
            <pre class="mb-0 bg-light p-3 rounded">{{ json_encode($contrat->regle_figee, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        @endif
    </div>
</div>
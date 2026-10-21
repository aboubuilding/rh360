{{-- Paramètres externes datés : SMIG (arrêté) et quotité saisissable (décret). Variables : $externes --}}
<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-header"><strong>Versions en vigueur et historique</strong></div>
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Paramètre</th>
                        <th>Valeur</th>
                        <th>À partir du</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($externes as $p)
                        <tr>
                            <td>{{ \App\Domain\Paie\Models\ParametreLegalPaie::LIBELLES[$p->cle] ?? $p->cle }}
                                @if($p->commentaire)<div class="small text-muted">{{ $p->commentaire }}</div>@endif
                            </td>
                            <td>
                                @if($p->valeur_json)
                                    @foreach($p->valeur_json as [$plafond, $fraction])
                                        <div class="small">{{ $plafond === null ? 'au-delà' : 'jusqu\'à '.number_format($plafond, 0, ',', ' ') }} : {{ rtrim(rtrim(number_format($fraction * 100, 2, ',', ''), '0'), ',') }} %</div>
                                    @endforeach
                                @else
                                    {{ number_format($p->valeur, 0, ',', ' ') }} FCFA
                                @endif
                            </td>
                            <td>{{ $p->debut_effet->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">Aucun paramètre saisi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><strong>Nouveau SMIG</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('paie.parametres.externes.store') }}">
                    @csrf
                    <input type="hidden" name="cle" value="smig">
                    <x-field name="valeur" label="SMIG mensuel (FCFA)" type="number" required />
                    <x-field name="debut_effet" label="À partir du" type="date" required />
                    <x-field name="commentaire" label="Référence de l'arrêté" />
                    <button class="btn btn-primary w-100"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Nouveau barème de quotité saisissable et cessible</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('paie.parametres.externes.store') }}">
                    @csrf
                    <input type="hidden" name="cle" value="quotite_saisissable">
                    <x-field name="tranches" label="Tranches (une par ligne : plafond ; pourcentage saisissable)" type="textarea" required
                             help="Exemple de format : « 30000 ; 10 », puis « 75000 ; 20 », et pour la dernière tranche « ; 33,33 ». Saisissez les valeurs du décret en vigueur." />
                    <x-field name="debut_effet" label="À partir du" type="date" required />
                    <x-field name="commentaire" label="Référence du décret" />
                    <button class="btn btn-primary w-100"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Contenu d'un bulletin de paie. Variables : $bulletin, $entreprise ; $apercu (facultatif). --}}
@if(! empty($apercu))
    <div class="apercu">APERÇU — bulletin non enregistré, sans valeur de paiement</div>
@endif
    <div class="entete">
        <div class="bloc">
            <h3>{{ $entreprise->nom ?? 'Entreprise' }}</h3>
            <div>{{ $entreprise->adresse ?? '' }}</div>
            <div>{{ $entreprise->ville ?? '' }} — {{ $entreprise->pays ?? '' }}</div>
            <div>NIF : {{ $entreprise->nif ?? '—' }}</div>
            <div>N° CNSS : {{ $entreprise->numero_employeur_cnss ?? '—' }}</div>
        </div>
        <div class="bloc">
            <h3>{{ $bulletin->salarie->nom_complet }}</h3>
            <div>Matricule : {{ $bulletin->salarie->matricule }}</div>
            <div>N° CNSS : {{ $bulletin->salarie->numero_cnss ?? '—' }}</div>
            <div>Emploi : {{ $bulletin->emploi_libelle ?? $bulletin->salarie->affectationCourante?->poste?->intitule ?? '—' }}</div>
            <div>Catégorie : {{ $bulletin->categorie_libelle ?? '—' }}@if($bulletin->echelon_libelle) — échelon {{ $bulletin->echelon_libelle }}@endif</div>
            <div>Embauche : {{ $bulletin->salarie->date_embauche?->format('d/m/Y') ?? '—' }}</div>
        </div>
    </div>

    <h1>BULLETIN DE PAIE</h1>
    <div class="periode">{{ $bulletin->periode->libelle }}@if($bulletin->periode->date_paiement) — payé le {{ $bulletin->periode->date_paiement->format('d/m/Y') }}@endif</div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Libellé</th>
                <th class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bulletin->lignes->where('nature', 'gain')->reject(fn ($l) => $l->rubrique?->est_frais) as $l)
                <tr>
                    <td>{{ $l->code }}</td>
                    <td>{{ $l->libelle }}</td>
                    <td class="text-end">{{ number_format($l->montant, 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            <tr class="totaux">
                <td colspan="2">TOTAL BRUT</td>
                <td class="text-end">{{ number_format($bulletin->montant_brut, 0, ',', ' ') }}</td>
            </tr>

            @foreach($bulletin->lignes->whereIn('nature', ['cotisation', 'retenue']) as $l)
                <tr>
                    <td>{{ $l->code }}</td>
                    <td>{{ $l->libelle }}</td>
                    <td class="text-end">-{{ number_format($l->montant, 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            <tr class="totaux">
                <td colspan="2">TOTAL RETENUES</td>
                <td class="text-end">-{{ number_format($bulletin->montant_retenues, 0, ',', ' ') }}</td>
            </tr>

            @foreach($bulletin->lignes->where('nature', 'gain')->filter(fn ($l) => $l->rubrique?->est_frais) as $l)
                <tr>
                    <td>{{ $l->code }}</td>
                    <td>{{ $l->libelle }} (remboursement de frais)</td>
                    <td class="text-end">+{{ number_format($l->montant, 0, ',', ' ') }}</td>
                </tr>
            @endforeach

            <tr class="net">
                <td colspan="2">NET À PAYER</td>
                <td class="text-end">{{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tbody>
    </table>

    @if($bulletin->lignes->where('nature', 'cotisation_patronale')->isNotEmpty())
        <table style="margin-top: 10px;">
            <thead>
                <tr><th colspan="2">Cotisations patronales (à la charge de l'employeur, non déduites)</th></tr>
            </thead>
            <tbody>
                @foreach($bulletin->lignes->where('nature', 'cotisation_patronale') as $l)
                    <tr>
                        <td>{{ $l->libelle }}</td>
                        <td class="text-end">{{ number_format($l->montant, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="margin-top: 30px; padding: 10px; border: 1px solid #ddd; background: #fafafa;">
        <strong>Détail du calcul fiscal :</strong><br>
        Brut imposable : {{ number_format($bulletin->brut_imposable, 0, ',', ' ') }} FCFA —
        Cotisations déductibles : {{ number_format($bulletin->retenues_sociales_deductibles, 0, ',', ' ') }} FCFA —
        Abattement pro. : {{ number_format($bulletin->abattement_professionnel, 0, ',', ' ') }} FCFA —
        Charges famille : {{ number_format($bulletin->deduction_charges_famille, 0, ',', ' ') }} FCFA<br>
        <strong>Base imposable : {{ number_format($bulletin->base_imposable, 0, ',', ' ') }} FCFA — IRPP : {{ number_format($bulletin->montant_irpp, 0, ',', ' ') }} FCFA</strong>
    </div>

    <div class="footer">
        Document généré automatiquement par EXPERT RH 360 le {{ now()->format('d/m/Y à H:i') }}.
    </div>

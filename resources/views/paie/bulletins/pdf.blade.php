<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de paie — {{ $bulletin->salarie->nom_complet }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #222; margin: 30px; }
        h1 { font-size: 14pt; text-align: center; margin-bottom: 5px; }
        .periode { text-align: center; font-size: 11pt; color: #666; margin-bottom: 20px; }
        .entete { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .entete .bloc { width: 48%; border: 1px solid #ccc; padding: 10px; }
        .entete h3 { font-size: 10pt; margin: 0 0 6px 0; color: #1B4965; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; text-align: left; font-size: 9pt; }
        .text-end { text-align: right; }
        .totaux { background: #e8f4f8; font-weight: bold; }
        .net { background: #d4edda; font-weight: bold; font-size: 11pt; }
        .footer { margin-top: 40px; font-size: 8pt; color: #999; text-align: center; }
    </style>
</head>
<body>
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
            <div>Poste : {{ $bulletin->salarie->affectationCourante?->poste?->intitule ?? '—' }}</div>
            <div>Embauche : {{ $bulletin->salarie->date_embauche?->format('d/m/Y') ?? '—' }}</div>
        </div>
    </div>

    <h1>BULLETIN DE PAIE</h1>
    <div class="periode">{{ $bulletin->periode->libelle }}</div>

    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Libellé</th>
                <th class="text-end">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bulletin->lignes->where('nature', 'gain') as $l)
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

            <tr class="net">
                <td colspan="2">NET À PAYER</td>
                <td class="text-end">{{ number_format($bulletin->montant_net, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tbody>
    </table>

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
</body>
</html>
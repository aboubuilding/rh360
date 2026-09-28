<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Acte d'autorisation de congé — {{ $demande->numero_demande }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #222; margin: 40px; }
        h1 { font-size: 16pt; text-align: center; margin-bottom: 30px; }
        .entete { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1B4965; padding-bottom: 10px; }
        .entete .nom { font-weight: bold; font-size: 14pt; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td { padding: 6px 8px; vertical-align: top; }
        td:first-child { width: 35%; font-weight: bold; }
        .signature { margin-top: 60px; text-align: right; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="nom">{{ $demande->entreprise?->nom ?? 'Entreprise' }}</div>
        <div>{{ $demande->entreprise?->adresse ?? '' }}</div>
    </div>

    <h1>Acte d'autorisation de congé n° {{ $demande->numero_demande }}</h1>

    <p>Le Directeur des Ressources Humaines autorise la demande de congé suivante :</p>

    <table>
        <tr><td>Salarié</td><td>{{ $demande->salarie?->nom_complet }} ({{ $demande->salarie?->matricule }})</td></tr>
        <tr><td>Type de congé</td><td>{{ $demande->typeConge?->nom }}</td></tr>
        <tr><td>Date de début</td><td>{{ $demande->date_debut?->format('d/m/Y') }}</td></tr>
        <tr><td>Date de reprise</td><td>{{ $demande->date_reprise?->format('d/m/Y') }}</td></tr>
        <tr><td>Durée</td><td>{{ number_format($demande->duree_jours, 2) }} jour(s)</td></tr>
        <tr><td>Remplaçant</td><td>{{ $demande->remplacant ?? 'Non désigné' }}</td></tr>
        @if($demande->motif)
            <tr><td>Motif</td><td>{{ $demande->motif }}</td></tr>
        @endif
    </table>

    <p>Le salarié est tenu de reprendre son service à la date indiquée ci-dessus.</p>

    <div class="signature">
        Fait à {{ $demande->entreprise?->lieu_signature ?? '—' }}, le {{ $demande->date_acte?->format('d/m/Y') ?? now()->format('d/m/Y') }}<br><br>
        <strong>{{ $demande->entreprise?->nom_signataire ?? 'Le DRH' }}</strong><br>
        {{ $demande->entreprise?->fonction_signataire ?? 'Directeur des Ressources Humaines' }}
    </div>
</body>
</html>
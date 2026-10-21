<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de paie — {{ $bulletin->salarie->nom_complet }}</title>
    <style>
@include('paie.bulletins.partials._pdf-style')
    </style>
</head>
<body>
@include('paie.bulletins.partials._pdf-contenu')
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletins de paie — {{ $periode->libelle }}</title>
    <style>
@include('paie.bulletins.partials._pdf-style')
    </style>
</head>
<body>
@foreach($bulletins as $bulletin)
    <div class="{{ $loop->last ? '' : 'saut-page' }}">
        @include('paie.bulletins.partials._pdf-contenu', ['bulletin' => $bulletin])
    </div>
@endforeach
</body>
</html>

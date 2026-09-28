@extends('layouts.app')

@section('title', 'Aperçu import planning')
@section('page_title', 'Aperçu avant import')
@section('page_icon', 'fa-clipboard-list')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.planning.index') }}">Planning</a></li>
    <li>Aperçu</li>
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Vérifiez les {{ $corps->count() }} premières lignes ci-dessous.
    Les lignes dont le matricule ou le code type est introuvable seront ignorées.
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    @foreach($entetes as $entete)
                        <th>{{ $entete }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($corps as $ligne)
                    <tr>
                        @foreach($entetes as $i => $entete)
                            <td>{{ $ligne[$i] ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<form method="POST" action="{{ route('conges.planning.importer') }}" id="form-import">
    @csrf
    <input type="hidden" name="fichier_temp" value="{{ $fichierTemp }}">

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary" id="btn-importer">
            <i class="fas fa-check"></i> Confirmer l'import
        </button>
        <a href="{{ route('conges.planning.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Choisir un autre fichier
        </a>
    </div>
</form>
@endsection

@push('js')
<script>
$(function () {
    $('#form-import').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#btn-importer');
        const texte = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Import...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            window.showToast(r.message || 'Import terminé.');
            setTimeout(() => { window.location.href = "{{ route('conges.planning.index') }}"; }, 800);
        })
        .fail(function () {
            window.showToast('Erreur d\'import.', 'error');
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
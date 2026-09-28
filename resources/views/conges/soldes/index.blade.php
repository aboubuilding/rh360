@extends('layouts.app')

@section('title', 'Soldes de congés')
@section('page_title', 'Soldes de congés')
@section('page_icon', 'fa-balance-scale')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Congés & Absences</li>
    <li>Soldes</li>
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, matricule...">
            </div>
            <div class="col-md-2">
                <input type="number" name="annee" value="{{ $annee }}" class="form-control" placeholder="Année">
            </div>
            <div class="col-md-3">
                <select name="type_conge_id" class="form-select">
                    <option value="">Tous types</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}" @selected(request('type_conge_id') == $t->id)>{{ $t->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Type</th>
                    <th>Année</th>
                    <th>Ouverture</th>
                    <th>Acquis</th>
                    <th>Ajustement</th>
                    <th>Réservé</th>
                    <th>Consommé</th>
                    <th>Disponible</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($soldes as $s)
                    <tr>
                        <td>
                            <strong>{{ $s->salarie?->nom_complet }}</strong>
                            <br><small class="text-muted">{{ $s->salarie?->matricule }}</small>
                        </td>
                        <td>{{ $s->typeConge?->nom }}</td>
                        <td>{{ $s->annee }}</td>
                        <td>{{ number_format($s->solde_ouverture, 2) }}</td>
                        <td>{{ number_format($s->acquis, 2) }}</td>
                        <td>{{ number_format($s->ajustement, 2) }}</td>
                        <td class="text-warning">{{ number_format($s->reserve, 2) }}</td>
                        <td class="text-secondary">{{ number_format($s->consomme, 2) }}</td>
                        <td class="fw-bold text-success">{{ number_format($s->disponible, 2) }}</td>
                        <td class="text-end">
                            @can('permission', 'conges.soldes.manage')
                                <button type="button" class="btn btn-sm btn-action js-ajuster-solde"
                                        data-id="{{ $s->id }}"
                                        data-nom="{{ $s->salarie?->nom_complet }} — {{ $s->typeConge?->nom }} ({{ $s->annee }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Aucun solde.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $soldes->links() }}</div>
</div>

@include('conges.soldes.partials._modal-ajuster')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-ajuster-solde', function () {
        const id = $(this).data('id');
        $('#titre-modal-ajuster').text('Ajuster : ' + $(this).data('nom'));
        $('#form-ajuster').attr('action', "{{ url('/conges/soldes/__ID__/ajuster') }}".replace('__ID__', id));
        $('#form-ajuster')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-ajuster')).show();
    });

    $('#form-ajuster').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-ajuster')).hide();
            window.showToastThenReload(r.message || 'Ajusté.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
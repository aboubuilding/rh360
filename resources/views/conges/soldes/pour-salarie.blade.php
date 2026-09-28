@extends('layouts.app')

@section('title', 'Soldes de ' . $salarie->nom_complet)
@section('page_title', 'Soldes de congés — ' . $salarie->nom_complet)
@section('page_icon', 'fa-balance-scale')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('conges.soldes.index') }}">Soldes</a></li>
    <li>{{ $salarie->nom_complet }}</li>
@endsection

@section('page_actions')
    <form method="GET" class="d-inline">
        <select name="annee" class="form-select d-inline-block" style="width: auto;" onchange="this.form.submit()">
            @for($a = now()->year + 1; $a >= now()->year - 3; $a--)
                <option value="{{ $a }}" @selected($annee === $a)>{{ $a }}</option>
            @endfor
        </select>
    </form>
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Matricule</div>
                <div class="fw-bold">{{ $salarie->matricule }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Date d'embauche</div>
                <div class="fw-bold">{{ $salarie->date_embauche?->format('d/m/Y') ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Année</div>
                <div class="fw-bold">{{ $annee }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small">Types suivis</div>
                <div class="fw-bold">{{ $soldes->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Type de congé</th>
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
                        <td><strong>{{ $s->typeConge?->nom }}</strong></td>
                        <td>{{ number_format($s->solde_ouverture, 2) }}</td>
                        <td>{{ number_format($s->acquis, 2) }}</td>
                        <td>{{ number_format($s->ajustement, 2) }}</td>
                        <td class="text-warning">{{ number_format($s->reserve, 2) }}</td>
                        <td class="text-secondary">{{ number_format($s->consomme, 2) }}</td>
                        <td class="fw-bold text-success fs-5">{{ number_format($s->disponible, 2) }}</td>
                        <td class="text-end">
                            @can('permission', 'conges.soldes.manage')
                                <button type="button" class="btn btn-sm btn-action js-ajuster-solde"
                                        data-id="{{ $s->id }}"
                                        data-nom="{{ $salarie->nom_complet }} — {{ $s->typeConge?->nom }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST"
                                      action="{{ route('conges.soldes.resynchroniser', $s) }}"
                                      class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-action" title="Resynchroniser">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        Aucun solde pour cette année.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
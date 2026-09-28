@extends('layouts.app')

@section('title', 'Objectifs individuels')
@section('page_title', 'Objectifs individuels')
@section('page_icon', 'fa-bullseye')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Performance</li>
    <li>Objectifs</li>
@endsection

@section('page_actions')
    @can('permission', 'performance.manage')
        <button type="button" class="btn btn-primary js-nouvel-objectif">
            <i class="fas fa-plus"></i> Nouvel objectif
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="campagne_id" class="form-select">
                    <option value="">Toutes les campagnes</option>
                    @foreach($campagnes as $c)
                        <option value="{{ $c->id }}" @selected(request('campagne_id') == $c->id)>{{ $c->intitule }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="salarie_id" class="form-select">
                    <option value="">Tous les salariés</option>
                    @foreach($salaries as $s)
                        <option value="{{ $s->id }}" @selected(request('salarie_id') == $s->id)>{{ $s->nom_complet }}</option>
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
                    <th>Campagne</th>
                    <th>Intitulé</th>
                    <th>Cible</th>
                    <th>Pondération</th>
                    <th>Échéance</th>
                    <th>Statut</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($objectifs as $o)
                    <tr>
                        <td>{{ $o->salarie?->nom_complet }}</td>
                        <td>{{ $o->campagne?->intitule }}</td>
                        <td><strong>{{ $o->intitule }}</strong></td>
                        <td>{{ $o->cible ?? '—' }}</td>
                        <td>{{ number_format($o->ponderation, 2) }}</td>
                        <td>{{ $o->date_echeance?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $o->statut?->couleur() }}">
                                {{ $o->statut?->libelle() }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('permission', 'performance.manage')
                                <form method="POST" action="{{ route('performance.objectifs.destroy', $o) }}"
                                      class="d-inline form-confirm-delete">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-action text-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun objectif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $objectifs->links() }}</div>
</div>

@include('performance.objectifs._modal')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-nouvel-objectif', () => {
        $('#form-objectif')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-objectif')).show();
    });

    $('#form-objectif').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-objectif')).hide();
            window.showToastThenReload(r.message || 'Enregistré.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else { window.showToast('Erreur.', 'error'); }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
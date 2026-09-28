@extends('layouts.app')

@section('title', 'Périodes de paie')
@section('page_title', 'Périodes de paie')
@section('page_icon', 'fa-money-check-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Périodes</li>
@endsection

@section('page_actions')
    @can('permission', 'paie.manage')
        <a href="{{ route('paie.periodes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ouvrir une période
        </a>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    @foreach($statuts as $val => $lib)
                        <option value="{{ $val }}" @selected(request('statut') === $val)>{{ $lib }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    @forelse($periodes as $p)
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ $p->libelle }}</strong>
                    <span class="badge bg-{{ $p->statut->couleur() }}">
                        {{ $p->statut->libelle() }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-muted small">Bulletins</div>
                            <div class="fw-bold fs-5">{{ $p->bulletins()->count() }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Brut total</div>
                            <div class="fw-bold fs-5">
                                {{ number_format($p->bulletins()->sum('montant_brut'), 0, ',', ' ') }} FCFA
                            </div>
                        </div>
                    </div>
                    @if($p->valide_le)
                        <hr>
                        <div class="small text-muted">
                            Validée le {{ $p->valide_le->format('d/m/Y H:i') }}
                            @if($p->validePar)
                                par {{ $p->validePar->nom_complet }}
                            @endif
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <a href="{{ route('paie.periodes.show', $p) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> Consulter
                    </a>
                    @if(! $p->estFigee())
                        @can('permission', 'paie.calculer')
                            <form method="POST" action="{{ route('paie.periodes.calculer', $p) }}"
                                  class="d-inline js-form-calculer">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fas fa-calculator"></i> Calculer
                                </button>
                            </form>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-2x mb-3"></i>
                <p class="mb-0">Aucune période de paie. Commencez par ouvrir une nouvelle période.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-3">{{ $periodes->links() }}</div>
@endsection

@push('js')
<script>
$(function () {
    $(document).on('submit', '.js-form-calculer', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();

        Swal.fire({
            title: 'Calculer la paie ?',
            text: 'Les bulletins de cette période seront générés ou recalculés.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            confirmButtonText: 'Oui, calculer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Calcul...');

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (resp) {
                window.showToastThenReload(resp.message || 'Calcul effectué.');
            })
            .fail(function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur de calcul.', 'error');
            })
            .always(function () { $btn.prop('disabled', false).html(texte); });
        });
    });
});
</script>
@endpush
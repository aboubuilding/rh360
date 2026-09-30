@extends('layouts.app')

@section('title', 'Rappels d\'avancement')
@section('page_title', 'Rappels d\'avancement rétroactifs')
@section('page_icon', 'fa-history')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Rappels</li>
@endsection

@section('page_actions')
    @can('permission', 'paie.manage')
        <button type="button" class="btn btn-primary js-generer-rappels">
            <i class="fas fa-magic"></i> Générer les rappels
        </button>
    @endcan
@endsection

@section('contenu')
<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Les rappels sont générés automatiquement à partir des mouvements d'avancement appliqués
    avec une date d'effet rétroactive. Ils sont intégrés au calcul de la période choisie.
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Nom, matricule...">
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
                    <th>Mouvement source</th>
                    <th>Période de génération</th>
                    <th class="text-end">Rappel base</th>
                    <th class="text-end">Rappel ancienneté</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rappels as $r)
                    <tr>
                        <td>
                            <strong>{{ $r->salarie?->nom_complet ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $r->salarie?->matricule }}</small>
                        </td>
                        <td>
                            <code>{{ $r->mouvement?->numero_mouvement }}</code>
                            <br><small class="text-muted">{{ $r->mouvement?->type_mouvement?->libelle() }}</small>
                        </td>
                        <td>{{ $r->periodeGeneration?->libelle }}</td>
                        <td class="text-end">{{ number_format($r->montant_rappel_base, 0, ',', ' ') }}</td>
                        <td class="text-end">{{ number_format($r->montant_rappel_anciennete, 0, ',', ' ') }}</td>
                        <td class="text-end fw-bold text-success">
                            {{ number_format($r->montantTotal(), 0, ',', ' ') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun rappel.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $rappels->links() }}</div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-generer-rappels', function () {
        Swal.fire({
            title: 'Générer les rappels ?',
            text: 'Tous les mouvements d\'avancement effectifs non encore rappelés seront traités.',
            input: 'select',
            inputOptions: @json(\App\Domain\Paie\Models\PeriodePaie::recentes()->get()->pluck('libelle', 'id')),
            inputPlaceholder: 'Sélectionner la période de génération',
            inputValidator: (value) => {
                if (!value) return 'Vous devez choisir une période.';
            },
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            confirmButtonText: 'Oui, générer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;

            $.ajax({
                url: "{{ route('paie.rappels.generer') }}",
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    periode_id: r.value,
                },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (resp) { window.showToastThenReload(resp.message || 'Rappels générés.'); })
            .fail(function (xhr) { window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error'); });
        });
    });
});
</script>
@endpush
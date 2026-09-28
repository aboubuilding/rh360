@extends('layouts.app')

@section('title', 'Avancements')
@section('page_title', 'Avancements prévisionnels')
@section('page_icon', 'fa-arrow-up')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Carrière & Mobilité</li>
    <li>Avancements</li>
@endsection

@section('page_actions')
    @can('permission', 'carriere.manage')
        <form method="POST" action="{{ route('carriere.avancements.preparer') }}" class="d-inline js-form-preparer">
            @csrf
            <input type="hidden" name="jours" value="{{ $jours }}">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-magic"></i> Préparer les propositions
            </button>
        </form>
    @endcan
@endsection

@section('contenu')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">Échéance sous</label>
                <select name="jours" class="form-select" onchange="this.form.submit()">
                    <option value="30" @selected($jours == 30)>30 jours</option>
                    <option value="60" @selected($jours == 60)>60 jours</option>
                    <option value="90" @selected($jours == 90)>90 jours</option>
                    <option value="180" @selected($jours == 180)>180 jours</option>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    {{ count($echeances) }} salarié(s) dont l'avancement est échu ou à échéance sous {{ $jours }} jours.
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Échéances d'avancement</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Salarié</th>
                    <th>Position actuelle</th>
                    <th>Position suivante</th>
                    <th>Date de référence</th>
                    <th>Délai</th>
                    <th>Éligibilité</th>
                    <th>État</th>
                </tr>
            </thead>
            <tbody>
                @forelse($echeances as $e)
                    <tr>
                        <td>
                            <strong>{{ \App\Domain\Personnel\Models\Salarie::find($e['salarie_id'])?->nom_complet ?? '—' }}</strong>
                        </td>
                        <td>{{ $e['position_actuelle_libelle'] }}</td>
                        <td>
                            @if($e['position_suivante_id'])
                                {{ \App\Domain\Classification\Models\PositionClassification::find($e['position_suivante_id'])?->libelleComplet() }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($e['date_reference'])->format('d/m/Y') }}</td>
                        <td>{{ $e['delai_mois'] }} mois</td>
                        <td>{{ \Carbon\Carbon::parse($e['date_eligibilite'])->format('d/m/Y') }}</td>
                        <td>
                            @if($e['est_eligible'])
                                <span class="badge bg-danger">Échu ({{ abs($e['jours_restants']) }} j)</span>
                            @elseif($e['echeance_proche'])
                                <span class="badge bg-warning text-dark">J-{{ $e['jours_restants'] }}</span>
                            @endif
                            @if($e['anticipation_autorisee'])
                                <span class="badge bg-info text-dark">Anticipation OK</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune échéance dans cette période.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(count($sansDate) > 0)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        {{ count($sansDate) }} salarié(s) sans date calculable (position sans règle d'évolution ou sans position suivante).
        <a href="#" onclick="return false;" data-bs-toggle="tooltip" title="Vérifiez leurs situations de carrière">
            Détails
        </a>
    </div>
@endif
@endsection

@push('js')
<script>
$(function () {
    $(document).on('submit', '.js-form-preparer', function (e) {
        e.preventDefault();
        const $form = $(this);

        Swal.fire({
            title: 'Préparer les propositions ?',
            text: 'Des propositions d\'avancement seront créées pour tous les salariés éligibles.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1B4965',
            confirmButtonText: 'Oui, préparer',
            cancelButtonText: 'Annuler'
        }).then(function (r) {
            if (!r.isConfirmed) return;

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .done(function (r) { window.showToastThenReload(r.message || 'Propositions préparées.'); })
            .fail(function () { window.showToast('Erreur.', 'error'); });
        });
    });
});
</script>
@endpush
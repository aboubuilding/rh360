@extends('layouts.app')

@section('title', 'Paramètres légaux de paie')
@section('page_title', 'Paramètres légaux de paie')
@section('page_icon', 'fa-cog')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Paie</li>
    <li>Paramètres légaux</li>
@endsection

@section('contenu')
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-cotisations">
        <i class="fas fa-percentage"></i> Cotisations sociales</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-anciennete">
        <i class="fas fa-hourglass-half"></i> Prime d'ancienneté</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-irpp">
        <i class="fas fa-file-invoice"></i> Barème IRPP</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-cotisations">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Règles de cotisations</strong>
                @can('permission', 'paie.manage')
                    <button type="button" class="btn btn-sm btn-primary js-nouvelle-cotisation">
                        <i class="fas fa-plus"></i> Nouvelle règle
                    </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Nom</th>
                            <th class="text-end">Taux salarial</th>
                            <th class="text-end">Taux patronal</th>
                            <th>Début d'effet</th>
                            <th>Fin d'effet</th>
                            <th>État</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cotisations as $r)
                            <tr>
                                <td><code>{{ $r->code }}</code></td>
                                <td>{{ $r->nom }}</td>
                                <td class="text-end">{{ number_format($r->taux_salarial, 2) }} %</td>
                                <td class="text-end">{{ number_format($r->taux_patronal, 2) }} %</td>
                                <td>{{ $r->debut_effet?->format('d/m/Y') }}</td>
                                <td>{{ $r->fin_effet?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    @if($r->actif)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('permission', 'paie.manage')
                                        <button type="button" class="btn btn-sm btn-action js-edit-cotisation"
                                                data-id="{{ $r->id }}"
                                                data-donnees="{{ json_encode([
                                                    'code' => $r->code,
                                                    'nom' => $r->nom,
                                                    'taux_salarial' => $r->taux_salarial,
                                                    'taux_patronal' => $r->taux_patronal,
                                                    'debut_effet' => $r->debut_effet?->format('Y-m-d'),
                                                    'fin_effet' => $r->fin_effet?->format('Y-m-d'),
                                                    'reference_legale' => $r->reference_legale,
                                                    'actif' => $r->actif,
                                                ]) }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('paie.parametres.cotisations.destroy', $r) }}"
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
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucune règle.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-anciennete">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Règle d'ancienneté</strong>
                @can('permission', 'paie.manage')
                    <button type="button" class="btn btn-sm btn-primary js-nouvelle-anciennete">
                        <i class="fas fa-plus"></i> Nouvelle règle
                    </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Années min.</th>
                            <th class="text-end">Taux initial</th>
                            <th class="text-end">Incrément annuel</th>
                            <th class="text-end">Taux max</th>
                            <th>Base</th>
                            <th>Début effet</th>
                            <th>État</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($anciennete as $r)
                            <tr>
                                <td>{{ $r->annees_min }}</td>
                                <td class="text-end">{{ number_format($r->taux_initial, 2) }} %</td>
                                <td class="text-end">{{ number_format($r->increment_annuel, 2) }}</td>
                                <td class="text-end">{{ number_format($r->taux_max, 2) }} %</td>
                                <td>{{ $r->mode_base }}</td>
                                <td>{{ $r->debut_effet?->format('d/m/Y') }}</td>
                                <td>
                                    @if($r->actif)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('permission', 'paie.manage')
                                        <button type="button" class="btn btn-sm btn-action js-edit-anciennete"
                                                data-id="{{ $r->id }}"
                                                data-donnees="{{ json_encode([
                                                    'annees_min' => $r->annees_min,
                                                    'taux_initial' => $r->taux_initial,
                                                    'increment_annuel' => $r->increment_annuel,
                                                    'taux_max' => $r->taux_max,
                                                    'mode_base' => $r->mode_base,
                                                    'debut_effet' => $r->debut_effet?->format('Y-m-d'),
                                                    'fin_effet' => $r->fin_effet?->format('Y-m-d'),
                                                    'reference_legale' => $r->reference_legale,
                                                    'actif' => $r->actif,
                                                ]) }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucune règle.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-irpp">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Barème IRPP progressif</strong>
                @can('permission', 'paie.manage')
                    <button type="button" class="btn btn-sm btn-primary js-nouvelle-irpp">
                        <i class="fas fa-plus"></i> Nouveau barème
                    </button>
                @endcan
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Début effet</th>
                            <th class="text-end">Abattement</th>
                            <th class="text-end">Plafond abattement</th>
                            <th class="text-end">Déduction / charge</th>
                            <th class="text-end">Max charges</th>
                            <th>État</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($irpp as $r)
                            <tr>
                                <td>{{ $r->debut_effet?->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format($r->taux_abattement_professionnel, 2) }} %</td>
                                <td class="text-end">{{ number_format($r->plafond_abattement_professionnel, 0, ',', ' ') }}</td>
                                <td class="text-end">{{ number_format($r->deduction_mensuelle_par_charge, 0, ',', ' ') }}</td>
                                <td class="text-end">{{ $r->nombre_max_charges }}</td>
                                <td>
                                    @if($r->actif)
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-secondary">Inactif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('permission', 'paie.manage')
                                        <button type="button" class="btn btn-sm btn-action js-edit-irpp"
                                                data-id="{{ $r->id }}"
                                                data-donnees="{{ json_encode([
                                                    'debut_effet' => $r->debut_effet?->format('Y-m-d'),
                                                    'fin_effet' => $r->fin_effet?->format('Y-m-d'),
                                                    'reference_legale' => $r->reference_legale,
                                                    'taux_abattement_professionnel' => $r->taux_abattement_professionnel,
                                                    'plafond_abattement_professionnel' => $r->plafond_abattement_professionnel,
                                                    'deduction_mensuelle_par_charge' => $r->deduction_mensuelle_par_charge,
                                                    'nombre_max_charges' => $r->nombre_max_charges,
                                                    'tranches' => $r->tranches,
                                                    'taux_tranches' => $r->taux_tranches,
                                                    'actif' => $r->actif,
                                                ]) }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucun barème.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('paie.parametres._modal-cotisation')
@include('paie.parametres._modal-anciennete')
@include('paie.parametres._modal-irpp')
@endsection

@push('js')
<script>
$(function () {
    // --- Cotisations ---
    const URL_COT_STORE  = "{{ route('paie.parametres.cotisations.store') }}";
    const URL_COT_UPDATE = "{{ route('paie.parametres.cotisations.update', ['regle' => '__ID__']) }}";

    function ouvrirCotisation(id = null, donnees = null) {
        const $form = $('#form-cotisation');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        if (id) {
            $('#titre-modal-cotisation').text('Modifier la règle');
            $form.attr('action', URL_COT_UPDATE.replace('__ID__', id));
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-cotisation').text('Nouvelle règle de cotisation');
            $form.attr('action', URL_COT_STORE);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-cotisation')).show();
    }

    $(document).on('click', '.js-nouvelle-cotisation', () => ouvrirCotisation());
    $(document).on('click', '.js-edit-cotisation', function () {
        ouvrirCotisation($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-cotisation').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-cotisation')).hide();
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

    // --- Ancienneté ---
    const URL_ANC_STORE  = "{{ route('paie.parametres.anciennete.store') }}";
    const URL_ANC_UPDATE = "{{ route('paie.parametres.anciennete.update', ['regle' => '__ID__']) }}";

    function ouvrirAnciennete(id = null, donnees = null) {
        const $form = $('#form-anciennete');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');

        if (id) {
            $('#titre-modal-anciennete').text('Modifier la règle');
            $form.attr('action', URL_ANC_UPDATE.replace('__ID__', id));
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-anciennete').text('Nouvelle règle d\'ancienneté');
            $form.attr('action', URL_ANC_STORE);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-anciennete')).show();
    }

    $(document).on('click', '.js-nouvelle-anciennete', () => ouvrirAnciennete());
    $(document).on('click', '.js-edit-anciennete', function () {
        ouvrirAnciennete($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-anciennete').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-anciennete')).hide();
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

    // --- IRPP ---
    const URL_IRPP_STORE  = "{{ route('paie.parametres.irpp.store') }}";
    const URL_IRPP_UPDATE = "{{ route('paie.parametres.irpp.update', ['regle' => '__ID__']) }}";

    function ouvrirIrpp(id = null, donnees = null) {
        const $form = $('#form-irpp');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');

        if (id) {
            $('#titre-modal-irpp').text('Modifier le barème');
            $form.attr('action', URL_IRPP_UPDATE.replace('__ID__', id));
            $.each(donnees, function (k, v) {
                if (k === 'tranches' || k === 'taux_tranches') {
                    if (Array.isArray(v)) {
                        v.forEach((val, i) => {
                            $form.find('[name="' + k + '[' + i + ']"]').val(val);
                        });
                    }
                } else {
                    const $el = $form.find('[name="' + k + '"]');
                    if (! $el.length) return;
                    if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                    else $el.val(v ?? '');
                }
            });
        } else {
            $('#titre-modal-irpp').text('Nouveau barème IRPP');
            $form.attr('action', URL_IRPP_STORE);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-irpp')).show();
    }

    $(document).on('click', '.js-nouvelle-irpp', () => ouvrirIrpp());
    $(document).on('click', '.js-edit-irpp', function () {
        ouvrirIrpp($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-irpp').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const texte = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-irpp')).hide();
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
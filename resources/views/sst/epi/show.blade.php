@extends('layouts.app')

@section('title', 'Dotation EPI')
@section('page_title', 'Dotation — ' . $dotation->intitule)
@section('page_icon', 'fa-shield-alt')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('sst.epi.index') }}">EPI</a></li>
    <li>Détail</li>
@endsection

@section('page_actions')
    @if(in_array($dotation->statut?->value, ['issued', 'in_use', 'to_replace']))
        @can('permission', 'ppe.manage')
            <button type="button" class="btn btn-primary js-nouvelle-operation">
                <i class="fas fa-plus"></i> Nouvelle opération
            </button>
        @endcan
    @endif
@endsection

@section('contenu')
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Statut</div>
                <span class="badge bg-{{ $dotation->statut->couleur() }} fs-6">
                    {{ $dotation->statut->libelle() }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Quantité initiale</div>
                <div class="fw-bold fs-4">{{ $dotation->quantite }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Quantité restante</div>
                <div class="fw-bold fs-4">{{ $dotation->quantiteRestante() }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Opérations</div>
                <div class="fw-bold fs-4">{{ $dotation->operations->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Salarié</dt>
            <dd class="col-sm-9">{{ $dotation->salarie?->nom_complet }}</dd>

            <dt class="col-sm-3">Catégorie</dt>
            <dd class="col-sm-9">{{ $dotation->categorie?->libelle() }}</dd>

            <dt class="col-sm-3">Équipement</dt>
            <dd class="col-sm-9">{{ $dotation->intitule }}</dd>

            <dt class="col-sm-3">Risque couvert</dt>
            <dd class="col-sm-9">{{ $dotation->risque?->intitule ?? '—' }}</dd>

            <dt class="col-sm-3">Numéro de série</dt>
            <dd class="col-sm-9">{{ $dotation->numero_serie ?? '—' }}</dd>

            <dt class="col-sm-3">Taille</dt>
            <dd class="col-sm-9">{{ $dotation->taille ?? '—' }}</dd>

            <dt class="col-sm-3">Date de remise</dt>
            <dd class="col-sm-9">{{ $dotation->date_remise?->format('d/m/Y') }}</dd>

            <dt class="col-sm-3">Expiration</dt>
            <dd class="col-sm-9">{{ $dotation->date_expiration?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-3">Prochaine vérification</dt>
            <dd class="col-sm-9">{{ $dotation->date_verification?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-3">Émetteur</dt>
            <dd class="col-sm-9">{{ $dotation->emetteur }}</dd>

            <dt class="col-sm-3">Référence reçu</dt>
            <dd class="col-sm-9">{{ $dotation->reference_recu ?? '—' }}</dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Opérations</strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Nature</th>
                    <th>Quantité</th>
                    <th>Intervenant</th>
                    <th>Résultat</th>
                    <th>Motif annulation</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dotation->operations as $op)
                    <tr class="{{ $op->etat === -1 ? 'text-muted text-decoration-line-through' : '' }}">
                        <td>{{ $op->date_evenement?->format('d/m/Y') }}</td>
                        <td>{{ $op->nature?->libelle() }}</td>
                        <td>{{ $op->quantite ?? '—' }}</td>
                        <td>{{ $op->intervenant }}</td>
                        <td>{{ Str::limit($op->resultat, 40) }}</td>
                        <td>{{ $op->motif_annulation ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Aucune opération.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('sst.epi.partials._modal-operation')
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.js-nouvelle-operation', () => {
        $('#form-operation')[0].reset();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-operation')).show();
    });

    $('#form-operation').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-operation')).hide();
            window.showToastThenReload(r.message || 'Opération enregistrée.');
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
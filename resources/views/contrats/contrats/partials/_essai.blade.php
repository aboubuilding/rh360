<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fas fa-hourglass-half"></i> Période d'essai</strong>
        @if($contrat->estSigne() && $calculEssai['date_fin_ajustee'])
            @can('permission', 'contrats.manage')
                <a href="{{ route('contrats.contrats.evenements-essai.create', $contrat) }}"
                   class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Déclarer un événement
                </a>
            @endcan
        @endif
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="text-muted small">Début d'essai</div>
                <div class="fw-bold">{{ $calculEssai['date_debut'] ? \Carbon\Carbon::parse($calculEssai['date_debut'])->format('d/m/Y') : '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Durée initiale</div>
                <div class="fw-bold">{{ $calculEssai['duree_initiale_jours'] ? $calculEssai['duree_initiale_jours'].' jours' : '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Fin théorique</div>
                <div class="fw-bold">{{ $calculEssai['date_fin_theorique'] ? \Carbon\Carbon::parse($calculEssai['date_fin_theorique'])->format('d/m/Y') : '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Fin ajustée</div>
                <div class="fw-bold text-primary">
                    {{ $calculEssai['date_fin_ajustee'] ? \Carbon\Carbon::parse($calculEssai['date_fin_ajustee'])->format('d/m/Y') : '—' }}
                </div>
            </div>
        </div>

        @if(($calculEssai['jours_suspendus'] ?? 0) > 0)
            <div class="alert alert-info py-2">
                <i class="fas fa-info-circle"></i>
                {{ $calculEssai['jours_suspendus'] }} jour(s) de suspension pris en compte.
            </div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <div class="text-muted small">Renouvellements</div>
                <div class="fw-bold">
                    {{ $calculEssai['nombre_renouvellements_utilises'] }} /
                    {{ $calculEssai['nombre_renouvellements_autorises'] }}
                    @if($calculEssai['peut_renouveler'])
                        <span class="badge bg-success ms-2">Renouvelable</span>
                    @else
                        <span class="badge bg-secondary ms-2">Plafond atteint</span>
                    @endif
                </div>
            </div>
        </div>

        @if($contrat->evenementsEssai->count() > 0)
            <hr>
            <h6>Événements déclarés</h6>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Nature</th>
                        <th>Période</th>
                        <th>Durée</th>
                        <th>Statut</th>
                        <th>Décision</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contrat->evenementsEssai as $e)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $e->nature->couleur() }}">
                                    {{ $e->nature->libelle() }}
                                </span>
                            </td>
                            <td>
                                {{ $e->details['date_debut'] ?? '—' }}
                                @if($e->details['date_fin'] ?? null) → {{ $e->details['date_fin'] }} @endif
                            </td>
                            <td>{{ $e->details['duree_jours'] ?? '—' }} j</td>
                            <td>
                                <span class="badge bg-{{ $e->statut->couleur() }}">
                                    {{ $e->statut->libelle() }}
                                </span>
                            </td>
                            <td>
                                @if($e->statut === \App\Domain\Contrats\Enums\StatutEvenementEssai::EN_ATTENTE)
                                    @can('permission', 'contrats.validate')
                                        <button type="button" class="btn btn-sm btn-success js-valider-essai"
                                                data-id="{{ $e->id }}"
                                                data-nature="{{ $e->nature->libelle() }}">
                                            Valider
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger js-refuser-essai"
                                                data-id="{{ $e->id }}"
                                                data-nature="{{ $e->nature->libelle() }}">
                                            Refuser
                                        </button>
                                    @endcan
                                @else
                                    <small>{{ $e->decidePar?->nom_complet }} — {{ $e->decide_le?->format('d/m/Y') }}</small>
                                    @if($e->note_decision)
                                        <div class="text-muted small fst-italic">{{ Str::limit($e->note_decision, 80) }}</div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@include('contrats.evenements-essai._modal-decider')

@push('js')
<script>
$(function () {
    function ouvrirModalDecision(id, nature, action) {
        const $modal = $('#modal-decider-essai');
        const $form = $('#form-decider-essai');
        const estValidation = action === 'valider';

        $('#titre-modal-decider').text((estValidation ? 'Valider' : 'Refuser') + ' — ' + nature);
        $form.attr('action', "{{ url('/contrats/evenements-essai/__ID__/__ACTION__') }}"
            .replace('__ID__', id).replace('__ACTION__', action));
        $('#note-decision-label').text(estValidation ? 'Note de validation' : 'Motif de refus');
        $('#btn-decider').removeClass('btn-success btn-danger')
            .addClass(estValidation ? 'btn-success' : 'btn-danger')
            .html('<i class="fas fa-' + (estValidation ? 'check' : 'times') + '"></i> ' + (estValidation ? 'Valider' : 'Refuser'));

        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    }

    $(document).on('click', '.js-valider-essai', function () {
        ouvrirModalDecision($(this).data('id'), $(this).data('nature'), 'valider');
    });

    $(document).on('click', '.js-refuser-essai', function () {
        ouvrirModalDecision($(this).data('id'), $(this).data('nature'), 'refuser');
    });

    $('#form-decider-essai').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $('#btn-decider');
        const texte = $btn.html();

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .done(function (r) {
            bootstrap.Modal.getInstance(document.getElementById('modal-decider-essai')).hide();
            window.showToastThenReload(r.message || 'Décision enregistrée.');
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $el = $form.find('[name="' + champ + '"]');
                    $el.addClass('is-invalid');
                    $el.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
                window.showToast('Veuillez corriger les erreurs.', 'error');
            } else {
                window.showToast('Erreur.', 'error');
            }
        })
        .always(function () { $btn.prop('disabled', false).html(texte); });
    });
});
</script>
@endpush
<div class="modal fade" id="modal-irpp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-irpp">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-irpp">Barème IRPP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="debut_effet" label="Début d'effet" type="date" required :value="now()->startOfYear()->format('Y-m-d')" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="fin_effet" label="Fin d'effet" type="date" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="taux_abattement_professionnel" label="Abattement pro. (%)" type="number" step="0.01" required :value="28" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="plafond_abattement_professionnel" label="Plafond abattement" type="number" step="0.01" required :value="10000000" />
                        </div>
                        <div class="col-md-4">
                            <x-field name="deduction_mensuelle_par_charge" label="Déduction / charge (FCFA)" type="number" step="0.01" required :value="10000" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="nombre_max_charges" label="Nombre max de charges" type="number" required :value="6" />
                        </div>
                        <div class="col-md-6">
                            <x-field name="reference_legale" label="Référence légale" />
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-3">Tranches du barème progressif</h6>

                    <div id="tranches-wrapper">
                        @php
                            $tranchesDefaut = [600000, 1200000, 2400000, 3600000, 5000000, 10000000];
                            $tauxDefaut = [0, 5, 10, 15, 20, 25];
                        @endphp
                        @foreach($tranchesDefaut as $i => $tr)
                            <div class="row align-items-end mb-2 tranche-ligne">
                                <div class="col-md-5">
                                    <label class="form-label small">Plafond cumulé (FCFA)</label>
                                    <input type="number" name="tranches[{{ $i }}]" class="form-control" value="{{ $tr }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Taux (%)</label>
                                    <input type="number" name="taux_tranches[{{ $i }}]" class="form-control" step="0.01" value="{{ $tauxDefaut[$i] }}">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-outline-danger js-supprimer-tranche w-100">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-ajouter-tranche">
                        <i class="fas fa-plus"></i> Ajouter une tranche
                    </button>

                    <div class="mt-3">
                        <div class="form-check">
                            <input type="hidden" name="actif" value="0">
                            <input type="checkbox" name="actif" id="irpp-actif" value="1" class="form-check-input" checked>
                            <label class="form-check-label" for="irpp-actif">Barème actif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
$(function () {
    let indexTranche = {{ count($tranchesDefaut ?? []) }};

    $('#btn-ajouter-tranche').on('click', function () {
        const html = `
            <div class="row align-items-end mb-2 tranche-ligne">
                <div class="col-md-5">
                    <label class="form-label small">Plafond cumulé (FCFA)</label>
                    <input type="number" name="tranches[${indexTranche}]" class="form-control" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Taux (%)</label>
                    <input type="number" name="taux_tranches[${indexTranche}]" class="form-control" step="0.01" value="0">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-danger js-supprimer-tranche w-100">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#tranches-wrapper').append(html);
        indexTranche++;
    });

    $(document).on('click', '.js-supprimer-tranche', function () {
        if ($('.tranche-ligne').length > 1) {
            $(this).closest('.tranche-ligne').remove();
        } else {
            window.showToast('Au moins une tranche est requise.', 'error');
        }
    });
});
</script>
@endpush
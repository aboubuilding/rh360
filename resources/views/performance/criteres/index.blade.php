@extends('layouts.app')

@section('title', 'Critères d\'évaluation')
@section('page_title', 'Critères d\'évaluation')
@section('page_icon', 'fa-list-check')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Performance</li>
    <li>Critères</li>
@endsection

@section('page_actions')
    @can('permission', 'performance.manage')
        <button type="button" class="btn btn-primary js-nouveau-critere">
            <i class="fas fa-plus"></i> Nouveau critère
        </button>
    @endcan
@endsection

@section('contenu')
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Libellé</th>
                    <th>Famille</th>
                    <th class="text-end">Pondération</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($criteres as $c)
                    <tr>
                        <td><code>{{ $c->code }}</code></td>
                        <td>{{ $c->libelle }}</td>
                        <td>{{ $c->famille?->libelle() }}</td>
                        <td class="text-end">{{ number_format($c->ponderation, 2) }}</td>
                        <td>
                            @if($c->estActif())
                                <span class="badge bg-success">Actif</span>
                            @else
                                <span class="badge bg-secondary">Inactif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('permission', 'performance.manage')
                                <button type="button" class="btn btn-sm btn-action js-edit-critere"
                                        data-id="{{ $c->id }}"
                                        data-donnees="{{ json_encode([
                                            'code' => $c->code,
                                            'libelle' => $c->libelle,
                                            'famille' => $c->famille?->value,
                                            'ponderation' => $c->ponderation,
                                            'actif' => $c->actif,
                                        ]) }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('performance.criteres.destroy', $c) }}"
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
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun critère.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $criteres->links() }}</div>
</div>

@include('performance.criteres._modal', ['familles' => $familles])
@endsection

@push('js')
<script>
$(function () {
    const URL_STORE  = "{{ route('performance.criteres.store') }}";
    const URL_UPDATE = "{{ route('performance.criteres.update', ['critere' => '__ID__']) }}";

    function ouvrir(id = null, donnees = null) {
        const $form = $('#form-critere');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');

        if (id) {
            $('#titre-modal-critere').text('Modifier le critère');
            $form.attr('action', URL_UPDATE.replace('__ID__', id));
            $form.find('input[name="_method"]').val('PUT');
            $.each(donnees, function (k, v) {
                const $el = $form.find('[name="' + k + '"]');
                if (! $el.length) return;
                if ($el.attr('type') === 'checkbox') $el.prop('checked', !!v);
                else $el.val(v ?? '');
            });
        } else {
            $('#titre-modal-critere').text('Nouveau critère');
            $form.attr('action', URL_STORE);
            $form.find('input[name="_method"]').val('POST');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-critere')).show();
    }

    $(document).on('click', '.js-nouveau-critere', () => ouvrir());
    $(document).on('click', '.js-edit-critere', function () {
        ouvrir($(this).data('id'), $(this).data('donnees'));
    });

    $('#form-critere').on('submit', function (e) {
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
            bootstrap.Modal.getInstance(document.getElementById('modal-critere')).hide();
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
@extends('layouts.app')

@section('title', 'Modifier ' . $mouvement->numero_mouvement)
@section('page_title', 'Modifier le mouvement')
@section('page_icon', 'fa-edit')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li><a href="{{ route('carriere.mouvements.index') }}">Actes de carrière</a></li>
    <li><a href="{{ route('carriere.mouvements.show', $mouvement) }}">{{ $mouvement->numero_mouvement }}</a></li>
    <li>Modifier</li>
@endsection

@section('contenu')
<div class="card">
    <div class="card-body">
        <form id="form-mouvement" method="POST" action="{{ route('carriere.mouvements.update', $mouvement) }}">
            @csrf
            @method('PUT')

            @include('carriere.mouvements._form', ['mouvement' => $mouvement])

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('carriere.mouvements.show', $mouvement) }}" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#form-mouvement').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
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
            window.showToast(r.message || 'Mouvement mis à jour.');
            setTimeout(() => { window.location.href = "{{ route('carriere.mouvements.show', $mouvement) }}"; }, 600);
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
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
@php
    /** @var \App\Domain\Pilotage\DTO\Indicateur $indicateur */
    $couleur = $indicateur->couleur ?? 'secondary';
    $estZero = $indicateur->estZero();
@endphp

@if($indicateur->lien)
    <a href="{{ $indicateur->lien }}" class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom text-decoration-none text-reset hover-bg-light transition">
@else
    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom">
@endif

    <div class="d-flex align-items-center">
        @if($indicateur->icone)
            <div class="me-3 text-{{ $couleur }}" style="width: 32px; text-align: center; font-size: 1.1rem;">
                <i class="fas {{ $indicateur->icone }}"></i>
            </div>
        @endif
        <div>
            <div class="small text-muted">{{ $indicateur->libelle }}</div>
            @if($indicateur->aide)
                <div class="small text-muted" style="font-size: 0.7rem;">{{ $indicateur->aide }}</div>
            @endif
        </div>
    </div>

    <div class="text-end">
        <span class="badge bg-{{ $couleur }} {{ $indicateur->suffixe ? 'fs-6' : 'fs-6' }}">
            {{ $indicateur->valeur }}{{ $indicateur->suffixe ? ' ' . $indicateur->suffixe : '' }}
        </span>
    </div>

@if($indicateur->lien)
    </a>
@else
    </div>
@endif
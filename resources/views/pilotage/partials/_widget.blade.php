@php
    /** @var \App\Domain\Pilotage\DTO\Widget $widget */
@endphp

<div class="card h-100 shadow-sm">
    <div class="card-header d-flex align-items-center" style="background: linear-gradient(90deg, var(--rh-primary-dark, #0F2E42), var(--rh-primary, #1B4965)); color: #fff;">
        @if($widget->icone)
            <i class="fas {{ $widget->icone }} me-2"></i>
        @endif
        <strong>{{ $widget->titre }}</strong>
    </div>
    <div class="card-body p-0">
        @forelse($widget->indicateurs as $indicateur)
            @include('pilotage.partials._indicateur', ['indicateur' => $indicateur])
        @empty
            <div class="text-center text-muted py-4 small">Aucun indicateur.</div>
        @endforelse
    </div>
</div>
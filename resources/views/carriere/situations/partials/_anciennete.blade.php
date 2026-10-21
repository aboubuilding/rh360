{{-- Ancienneté utile, emplois antérieurs (réembauche) et distinctions honorifiques. --}}
@php
    $calculAnciennete = app(\App\Domain\Carriere\Services\CalculateurAncienneteUtile::class);
    $dateCorrigee = $calculAnciennete->dateCorrigee($salarie, today());
    $emploisAnterieurs = \App\Domain\Carriere\Models\PeriodeEmploiAnterieure::where('salarie_id', $salarie->id)->orderByDesc('date_fin')->get();
    $distinctions = \App\Domain\Carriere\Models\DistinctionHonorifique::where('salarie_id', $salarie->id)->orderBy('palier_annees')->get();
@endphp

<div class="card mb-3">
    <div class="card-header"><strong>Ancienneté</strong></div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-5">Date d'embauche</dt>
            <dd class="col-sm-7">{{ $salarie->date_embauche?->format('d/m/Y') ?? '—' }}</dd>
            <dt class="col-sm-5">Ancienneté utile</dt>
            <dd class="col-sm-7">
                {{ $calculAnciennete->annees($salarie, today()) }} an(s)
                @if($dateCorrigee && $salarie->date_embauche && ! $dateCorrigee->eq($salarie->date_embauche))
                    <small class="text-muted">(décomptée depuis le {{ $dateCorrigee->format('d/m/Y') }})</small>
                @endif
            </dd>
        </dl>
    </div>

    <div class="card-header border-top"><strong>Emplois antérieurs dans l'entreprise</strong></div>
    <ul class="list-group list-group-flush">
        @forelse($emploisAnterieurs as $p)
            <li class="list-group-item">
                Du {{ $p->date_debut->format('d/m/Y') }} au {{ $p->date_fin->format('d/m/Y') }} — {{ $p->motif_depart->libelle() }}
                @if($p->motif_depart->cumulable())
                    <span class="badge bg-success ms-1">cumulé</span>
                @else
                    <span class="badge bg-secondary ms-1">non cumulé</span>
                @endif
            </li>
        @empty
            <li class="list-group-item text-muted">Aucun emploi antérieur enregistré.</li>
        @endforelse
    </ul>
    @can('permission', 'carriere.manage')
        <div class="card-body border-top">
            <form method="POST" action="{{ route('carriere.situations.emplois-anterieurs.store', $salarie) }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-sm-6"><x-field name="date_debut" label="Du" type="date" required /></div>
                <div class="col-sm-6"><x-field name="date_fin" label="Au" type="date" required /></div>
                <div class="col-sm-12">
                    <x-field name="motif_depart" label="Motif du départ" type="select" required
                             :options="[null => '—'] + \App\Domain\Carriere\Enums\MotifDepart::options()" />
                </div>
                <div class="col-sm-12"><x-field name="piece" label="Justificatif" type="file" /></div>
                <div class="col-sm-12"><button class="btn btn-outline-primary"><i class="fas fa-plus"></i> Ajouter l'emploi antérieur</button></div>
            </form>
        </div>
    @endcan

    @if($distinctions->isNotEmpty())
        <div class="card-header border-top"><strong>Distinctions honorifiques</strong></div>
        <ul class="list-group list-group-flush">
            @foreach($distinctions as $d)
                <li class="list-group-item d-flex justify-content-between flex-wrap gap-1">
                    <span>{{ $d->palier_annees }} ans — le {{ $d->date_anniversaire->format('d/m/Y') }}</span>
                    <span>{{ number_format($d->montant, 0, ',', ' ') }} FCFA
                        <span class="badge bg-{{ $d->statut === 'versee' ? 'success' : 'warning text-dark' }}">{{ $d->statut === 'versee' ? 'Versée' : 'Due' }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</div>

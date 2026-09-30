<li>
    <span class="noeud">
        <a href="{{ route('organisation.structures.show', $structure) }}">
            <strong>{{ $structure->nom }}</strong>
        </a>
        <span class="code">{{ $structure->code }}</span>
        @if($structure->typeStructure)
            <span class="badge bg-secondary ms-2">{{ $structure->typeStructure->nom }}</span>
        @endif
    </span>
    @if($structure->enfantsRecursifs->count() > 0)
        <ul>
            @foreach($structure->enfantsRecursifs as $enfant)
                @include('organisation.structures._noeud', ['structure' => $enfant])
            @endforeach
        </ul>
    @endif
</li>
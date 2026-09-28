<?php

namespace App\Domain\Pilotage\DTO;

use Illuminate\Support\Collection;

final class TableauDeBord
{
    /**
     * @param Collection<Widget> $widgets
     */
    public function __construct(
        public readonly Collection $widgets,
        public readonly array $contexte = [],
        public readonly ?string $derniereSynchro = null,
    ) {}

    public function widget(string $cle): ?Widget
    {
        return $this->widgets->firstWhere('cle', $cle);
    }

    public function estVide(): bool
    {
        return $this->widgets->isEmpty();
    }
}
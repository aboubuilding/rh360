<?php

namespace App\Domain\Pilotage\DTO;

use Illuminate\Support\Collection;

/**
 * Un widget du tableau de bord : un groupe d'indicateurs sous un titre commun.
 */
final class Widget
{
    /**
     * @param Collection<Indicateur> $indicateurs
     */
    public function __construct(
        public readonly string $cle,
        public readonly string $titre,
        public readonly Collection $indicateurs,
        public readonly ?string $icone = null,
        public readonly ?string $couleur = 'primary',
        public readonly ?string $permission = null,
        public readonly int $ordre = 100,
    ) {}

    public function aDesIndicateursActifs(): bool
    {
        return $this->indicateurs->contains(fn (Indicateur $i) => ! $i->estZero());
    }

    public function versArray(): array
    {
        return [
            'cle' => $this->cle,
            'titre' => $this->titre,
            'icone' => $this->icone,
            'couleur' => $this->couleur,
            'indicateurs' => $this->indicateurs->map(fn ($i) => $i->versArray())->all(),
        ];
    }
}
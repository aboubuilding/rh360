<?php

namespace App\Domain\Pilotage\DTO;

/**
 * Un indicateur du tableau de bord.
 *
 * Exemple : "Contrats à valider" → 12, couleur "warning", lien vers la liste filtrée.
 */
final class Indicateur
{
    public function __construct(
        public readonly string $cle,
        public readonly string $libelle,
        public readonly int|float|string $valeur,
        public readonly ?string $couleur = 'primary',
        public readonly ?string $icone = null,
        public readonly ?string $lien = null,
        public readonly ?string $suffixe = null,
        public readonly ?string $aide = null,
    ) {}

    /** Copie de l'indicateur pointant vers un autre écran. */
    public function avecLien(?string $lien): self
    {
        return new self($this->cle, $this->libelle, $this->valeur, $this->couleur, $this->icone, $lien, $this->suffixe, $this->aide);
    }

    public function estZero(): bool
    {
        return is_numeric($this->valeur) && (float) $this->valeur === 0.0;
    }

    public function versArray(): array
    {
        return [
            'cle' => $this->cle,
            'libelle' => $this->libelle,
            'valeur' => $this->valeur,
            'couleur' => $this->couleur,
            'icone' => $this->icone,
            'lien' => $this->lien,
            'suffixe' => $this->suffixe,
            'aide' => $this->aide,
            'est_zero' => $this->estZero(),
        ];
    }
}
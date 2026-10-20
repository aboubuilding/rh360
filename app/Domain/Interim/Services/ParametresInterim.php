<?php

namespace App\Domain\Interim\Services;

use App\Domain\Administration\Services\ServiceAudit;
use App\Domain\Interim\Models\ParametreInterim;

/**
 * Paramètres de la gestion des intérims d'une entreprise : la valeur saisie dans
 * l'écran de paramétrage, sinon la valeur par défaut de config/interim.php.
 */
class ParametresInterim
{
    /** @var array<int, array<string, mixed>> */
    private array $surcharges = [];

    private ?int $entrepriseForcee = null;

    public function __construct(private ServiceAudit $audit) {}

    // Contexte explicite pour les tâches planifiées et commandes (sans utilisateur connecté).
    public function pourEntreprise(?int $entrepriseId): self
    {
        $this->entrepriseForcee = $entrepriseId;

        return $this;
    }

    public function get(string $cle): mixed
    {
        $surcharges = $this->surcharges($this->entrepriseId());

        return array_key_exists($cle, $surcharges) ? $surcharges[$cle] : $this->defaut($cle);
    }

    public function defaut(string $cle): mixed
    {
        return config("interim.{$cle}");
    }

    public function estSurcharge(string $cle): bool
    {
        return array_key_exists($cle, $this->surcharges($this->entrepriseId()));
    }

    /**
     * Enregistre les valeurs saisies. Une valeur égale à la valeur par défaut n'est pas
     * conservée comme surcharge : l'entreprise suit alors la valeur des textes.
     *
     * @param  array<string, mixed>  $valeurs
     */
    public function enregistrer(array $valeurs): void
    {
        $entrepriseId = $this->entrepriseId();
        $avant = $this->surcharges($entrepriseId);

        $surcharges = [];
        foreach (CatalogueParametresInterim::definitions() as $cle => $definition) {
            if (! array_key_exists($cle, $valeurs)) {
                if (array_key_exists($cle, $avant)) {
                    $surcharges[$cle] = $avant[$cle];
                }

                continue;
            }

            $valeur = $this->normaliser($definition['type'], $valeurs[$cle]);
            if ($valeur !== $this->defaut($cle)) {
                $surcharges[$cle] = $valeur;
            }
        }

        $parametre = ParametreInterim::firstOrNew(['entreprise_id' => $entrepriseId]);
        $parametre->valeurs = $surcharges;
        $parametre->revision = ($parametre->revision ?? 0) + 1;
        $parametre->modifie_par = auth()->id();
        $parametre->save();

        unset($this->surcharges[$entrepriseId]);

        $this->audit->tracer('interim.parametres.modifies', $parametre, ['avant' => $avant, 'apres' => $surcharges]);
    }

    public function reinitialiser(): void
    {
        $this->enregistrer(array_map(fn ($cle) => $this->defaut($cle), array_combine(
            array_keys(CatalogueParametresInterim::definitions()),
            array_keys(CatalogueParametresInterim::definitions()),
        )));
    }

    private function normaliser(string $type, mixed $valeur): mixed
    {
        return match ($type) {
            'entier' => (int) $valeur,
            'entier_facultatif', 'referentiel' => ($valeur === null || $valeur === '') ? null : (int) $valeur,
            'booleen' => filter_var($valeur, FILTER_VALIDATE_BOOLEAN),
            'referentiels' => array_values(array_map('intval', array_filter((array) $valeur, fn ($v) => $v !== '' && $v !== null))),
            'types_conges' => array_values(array_map('strval', array_filter((array) $valeur, fn ($v) => $v !== '' && $v !== null))),
            default => (string) $valeur,
        };
    }

    /** @return array<string, mixed> */
    private function surcharges(?int $entrepriseId): array
    {
        if (! $entrepriseId) {
            return [];
        }

        return $this->surcharges[$entrepriseId] ??= ParametreInterim::where('entreprise_id', $entrepriseId)->value('valeurs') ?? [];
    }

    private function entrepriseId(): ?int
    {
        return $this->entrepriseForcee ?? auth()->user()?->entreprise_id;
    }
}

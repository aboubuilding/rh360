<?php

namespace App\Domain\Shared\Services;

use App\Domain\Administration\Services\ServiceAudit;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètres d'un module pour une entreprise : la valeur saisie dans l'écran de paramétrage,
 * sinon la valeur par défaut du fichier de configuration du module.
 */
abstract class ParametresEntreprise
{
    /** @var array<int, array<string, mixed>> */
    private array $surcharges = [];

    private ?int $entrepriseForcee = null;

    public function __construct(protected ServiceAudit $audit) {}

    // Préfixe du fichier de configuration des valeurs par défaut (config/{prefixe}.php).
    abstract protected function prefixeConfig(): string;

    /** @return array<string, array<string, mixed>> */
    abstract public function definitions(): array;

    /** @return class-string<Model> modèle (entreprise_id, valeurs, revision, modifie_par) */
    abstract protected function modele(): string;

    // Contexte explicite pour les tâches planifiées et commandes (sans utilisateur connecté).
    public function pourEntreprise(?int $entrepriseId): static
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
        return config($this->prefixeConfig().'.'.$cle);
    }

    public function estSurcharge(string $cle): bool
    {
        return array_key_exists($cle, $this->surcharges($this->entrepriseId()));
    }

    /**
     * Enregistre les valeurs saisies. Une valeur égale à la valeur par défaut n'est pas
     * conservée comme surcharge : l'entreprise suit alors la valeur par défaut.
     *
     * @param  array<string, mixed>  $valeurs
     */
    public function enregistrer(array $valeurs): void
    {
        $entrepriseId = $this->entrepriseId();
        $avant = $this->surcharges($entrepriseId);

        $surcharges = [];
        foreach ($this->definitions() as $cle => $definition) {
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

        $classe = $this->modele();
        $parametre = $classe::firstOrNew(['entreprise_id' => $entrepriseId]);
        $parametre->valeurs = $surcharges;
        $parametre->revision = ($parametre->revision ?? 0) + 1;
        $parametre->modifie_par = auth()->id();
        $parametre->save();

        unset($this->surcharges[$entrepriseId]);

        $this->audit->tracer($this->prefixeConfig().'.parametres.modifies', $parametre, ['avant' => $avant, 'apres' => $surcharges]);
    }

    public function reinitialiser(): void
    {
        $cles = array_keys($this->definitions());
        $this->enregistrer(array_combine($cles, array_map(fn ($cle) => $this->defaut($cle), $cles)));
    }

    private function normaliser(string $type, mixed $valeur): mixed
    {
        return match ($type) {
            'entier' => (int) $valeur,
            'entier_facultatif', 'referentiel' => ($valeur === null || $valeur === '') ? null : (int) $valeur,
            'booleen' => filter_var($valeur, FILTER_VALIDATE_BOOLEAN),
            'referentiels' => array_values(array_map('intval', array_filter((array) $valeur, fn ($v) => $v !== '' && $v !== null))),
            'types_conges' => array_values(array_map('strval', array_filter((array) $valeur, fn ($v) => $v !== '' && $v !== null))),
            'decimal' => (float) $valeur,
            default => (string) $valeur,
        };
    }

    /** @return array<string, mixed> */
    private function surcharges(?int $entrepriseId): array
    {
        if (! $entrepriseId) {
            return [];
        }

        $classe = $this->modele();

        return $this->surcharges[$entrepriseId] ??= $classe::where('entreprise_id', $entrepriseId)->value('valeurs') ?? [];
    }

    private function entrepriseId(): ?int
    {
        return $this->entrepriseForcee ?? auth()->user()?->entreprise_id;
    }
}

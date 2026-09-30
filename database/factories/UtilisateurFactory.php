<?php

namespace Database\Factories;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UtilisateurFactory extends Factory
{
    protected $model = Utilisateur::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'nom_complet' => $this->faker->name(),
            'identifiant' => 'user_'.$this->faker->unique()->numerify('######'),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Utilisateur::ROLE_RH,
            'actif' => true,
            'etat' => 1,
        ];
    }

    public function role(string $role): static
    {
        return $this->state(['role' => $role]);
    }

    public function inactif(): static
    {
        return $this->state(['actif' => false]);
    }
}

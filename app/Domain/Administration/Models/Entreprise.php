<?php

namespace App\Domain\Administration\Models;

use App\Domain\Shared\Enums\Etat;
use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entreprise extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'entreprises';

    protected $fillable = [
        'nom', 'sigle', 'forme_juridique', 'nif', 'numero_employeur_cnss',
        'secteur', 'adresse', 'ville', 'pays', 'telephone', 'email',
        'devise', 'date_bascule', 'chemin_logo',
        'direction_emettrice', 'service_emetteur',
        'texte_en_tete', 'texte_pied_page',
        'nom_signataire', 'fonction_signataire', 'lieu_signature',
        'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_bascule' => 'date',
            'actif' => 'boolean',
            'etat' => Etat::class,
        ];
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(Utilisateur::class);
    }
}
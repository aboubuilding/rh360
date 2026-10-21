<?php

namespace App\Domain\Conges\Models;

use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Événement ouvrant droit à une permission exceptionnelle, avec sa durée (CCIT art. 50).
 */
class EvenementPermission extends Model
{
    use AvecEtat, BelongsToEntreprise;

    protected $table = 'evenements_permission';

    // Liens de parenté proposés pour rattacher l'événement à un membre du foyer.
    public const LIENS = [
        'salarie' => 'Le salarié lui-même',
        'conjoint' => 'Conjoint',
        'ascendant' => 'Ascendant direct',
        'descendant' => 'Descendant direct',
        'frere_soeur' => 'Frère ou sœur',
        'beau_parent' => 'Beau-père ou belle-mère',
        'enfant_frere_soeur' => 'Enfant, frère ou sœur',
        'foyer' => 'Foyer du salarié',
    ];

    protected $fillable = [
        'entreprise_id', 'type_conge_id', 'code', 'libelle', 'lien_famille',
        'jours', 'delai_prise_mois', 'piece_attendue', 'ordre', 'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'jours' => 'decimal:2',
            'delai_prise_mois' => 'integer',
            'ordre' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function typeConge(): BelongsTo
    {
        return $this->belongsTo(TypeConge::class, 'type_conge_id');
    }
}

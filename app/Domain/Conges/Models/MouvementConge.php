<?php

namespace App\Domain\Conges\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mouvement du grand livre des congés payés, rattaché à un lot annuel (année d'acquisition).
 * Les jours sont signés : acquisitions, reprises, recrédits et arrondis en positif ; prises
 * et expirations en négatif ; ajustements dans les deux sens. Un mouvement n'est jamais
 * modifié : une erreur se corrige par un mouvement inverse.
 */
class MouvementConge extends Model
{
    use BelongsToEntreprise;

    protected $table = 'mouvements_conge';

    public const UPDATED_AT = null;

    public const ACQUISITION = 'acquisition';
    public const OUVERTURE = 'ouverture';
    public const PRISE = 'prise';
    public const RECREDIT = 'recredit';
    public const AJUSTEMENT = 'ajustement';
    public const ARRONDI = 'arrondi';
    public const EXPIRATION = 'expiration';

    public const LIBELLES = [
        self::ACQUISITION => 'Acquisition',
        self::OUVERTURE => 'Reprise du solde',
        self::PRISE => 'Prise',
        self::RECREDIT => 'Recrédit',
        self::AJUSTEMENT => 'Ajustement',
        self::ARRONDI => 'Arrondi',
        self::EXPIRATION => 'Expiration',
    ];

    protected $fillable = [
        'entreprise_id', 'salarie_id', 'type_conge_id', 'lot', 'nature', 'jours',
        'date_valeur', 'mois_reference', 'demande_id', 'commentaire', 'cree_par',
    ];

    protected function casts(): array
    {
        return [
            'lot' => 'integer',
            'jours' => 'decimal:2',
            'date_valeur' => 'date',
            'mois_reference' => 'date',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Un mouvement de congé ne se modifie pas : passez un mouvement inverse.'));
        static::deleting(fn () => throw new \LogicException('Un mouvement de congé ne se supprime pas : passez un mouvement inverse.'));
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(DemandeConge::class, 'demande_id');
    }
}

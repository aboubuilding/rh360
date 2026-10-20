<?php

namespace App\Domain\Interim\Models;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Interim\Enums\TypeDecisionInterim;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Décision RH historisée (qui, quand, pièce jointe) sur un intérim interne,
 * une mission d'intérim ou un prêt de main-d'œuvre. Ces lignes ne sont jamais modifiées.
 */
class DecisionInterim extends Model
{
    use BelongsToEntreprise;

    protected $table = 'decisions_interims';

    protected $fillable = [
        'entreprise_id', 'objet_type', 'objet_id', 'type_decision', 'donnees', 'commentaire',
        'chemin_piece', 'type_mime_piece', 'empreinte_sha256', 'decide_par', 'decide_le',
    ];

    protected function casts(): array
    {
        return [
            'type_decision' => TypeDecisionInterim::class,
            'donnees' => 'array',
            'decide_le' => 'datetime',
        ];
    }

    public function objet(): MorphTo
    {
        return $this->morphTo();
    }

    public function decidePar(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'decide_par');
    }
}

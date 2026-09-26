<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoriqueContrat extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'historique_contrats';

    protected $fillable = [
        'entreprise_id', 'contrat_id', 'action',
        'instantane', 'utilisateur_id',
    ];

    protected function casts(): array
    {
        return ['instantane' => 'array'];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'utilisateur_id');
    }
}
<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Contrats\Enums\BaseCalculEcheance;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlerteContrat extends Model
{
    use HasFactory, AvecEtat, BelongsToEntreprise;

    protected $table = 'alertes_contrats';

    protected $fillable = [
        'entreprise_id', 'contrat_id', 'cle', 'intitule',
        'date_echeance', 'base_calcul', 'en_cours',
        'cloture_le', 'cloture_par', 'note_cloture', 'piece_id',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
            'cloture_le' => 'datetime',
            'en_cours' => 'boolean',
            'base_calcul' => BaseCalculEcheance::class,
        ];
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class, 'contrat_id');
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(PieceContrat::class, 'piece_id');
    }

    public function cloturePar(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'cloture_par');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationContrat::class, 'alerte_id');
    }

    public function estEchue(): bool
    {
        return $this->en_cours && $this->date_echeance && $this->date_echeance->isPast();
    }

    public function joursRestants(): int
    {
        return $this->date_echeance
            ? max(0, now()->diffInDays($this->date_echeance, false))
            : 0;
    }
}
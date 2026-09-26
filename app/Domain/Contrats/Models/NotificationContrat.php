<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationContrat extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'notifications_contrats';

    protected $fillable = [
        'entreprise_id', 'alerte_id', 'utilisateur_id',
        'base_calcul', 'seuil', 'lu_le',
    ];

    protected function casts(): array
    {
        return [
            'seuil' => 'integer',
            'lu_le' => 'datetime',
        ];
    }

    public function alerte(): BelongsTo
    {
        return $this->belongsTo(AlerteContrat::class, 'alerte_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Administration\Models\Utilisateur::class, 'utilisateur_id');
    }

    public function estLu(): bool
    {
        return ! is_null($this->lu_le);
    }

    public function marquerLu(): void
    {
        if (! $this->lu_le) {
            $this->update(['lu_le' => now()]);
        }
    }

    public function scopeNonLues($q)
    {
        return $q->whereNull('lu_le');
    }
}
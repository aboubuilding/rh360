<?php

namespace App\Domain\Personnel\Models;

use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembreFoyer extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'membres_foyer';

    protected $fillable = [
        'salarie_id', 'lien_parente', 'nom', 'prenoms',
        'date_naissance', 'lieu_naissance',
        'est_enfant_declare', 'est_a_charge',
        'date_debut_charge', 'date_fin_charge',
        'chemin_photo', 'chemin_acte_naissance',
        'actif', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'date_debut_charge' => 'date',
            'date_fin_charge' => 'date',
            'est_enfant_declare' => 'boolean',
            'est_a_charge' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->nom . ' ' . $this->prenoms);
    }
}
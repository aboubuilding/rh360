<?php

namespace App\Domain\Administration\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalAudit extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'journal_audit';

    protected $fillable = [
        'entreprise_id', 'utilisateur_id', 'action', 'entite',
        'entite_id', 'details',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
}
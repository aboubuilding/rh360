<?php

namespace App\Domain\Administration\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionUtilisateur extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'permissions_utilisateurs';

    protected $fillable = [
        'entreprise_id', 'utilisateur_id', 'permission', 'autorise',
    ];

    protected function casts(): array
    {
        return ['autorise' => 'boolean'];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
}
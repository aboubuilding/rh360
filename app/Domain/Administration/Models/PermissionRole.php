<?php

namespace App\Domain\Administration\Models;

use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionRole extends Model
{
    use HasFactory, BelongsToEntreprise;

    protected $table = 'permissions_roles';

    protected $fillable = [
        'entreprise_id', 'role', 'permission', 'autorise',
    ];

    protected function casts(): array
    {
        return ['autorise' => 'boolean'];
    }
}
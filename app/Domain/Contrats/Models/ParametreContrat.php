<?php

namespace App\Domain\Contrats\Models;

use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametreContrat extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'parametres_contrats';
    protected $primaryKey = 'entreprise_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'entreprise_id', 'seuils', 'roles', 'revision', 'etat',
    ];

    protected function casts(): array
    {
        return [
            'seuils' => 'array',
            'roles' => 'array',
            'revision' => 'integer',
        ];
    }

    /**
     * Intercepte l'accès pour convertir les chaînes CSV en tableaux
     * (l'ancienne app stocke "30,15,7,0" — le CDC §7.5 indique que
     * c'est du texte, pas du JSON).
     */
    public function getSeuilsAttribute($value): array
    {
        if (is_array($value)) return $value;
        return $value ? array_map('intval', explode(',', $value)) : [];
    }

    public function setSeuilsAttribute($value): void
    {
        $this->attributes['seuils'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function getRolesAttribute($value): array
    {
        if (is_array($value)) return $value;
        return $value ? array_map('trim', explode(',', $value)) : [];
    }

    public function setRolesAttribute($value): void
    {
        $this->attributes['roles'] = is_array($value) ? implode(',', $value) : $value;
    }
}
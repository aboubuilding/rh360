<?php

namespace App\Domain\Administration\Models;

use App\Domain\Shared\Enums\Etat;
use App\Domain\Shared\Traits\AvecEtat;
use App\Domain\Shared\Traits\BelongsToEntreprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use HasFactory, Notifiable, AvecEtat, BelongsToEntreprise;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'entreprise_id', 'nom_complet', 'email', 'chemin_photo_profil',
        'identifiant', 'password', 'role', 'actif', 'etat',
        'derniere_connexion',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'actif' => 'boolean',
            'etat' => Etat::class,
            'derniere_connexion' => 'datetime',
        ];
    }

    // --- Rôles ---

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_DRH         = 'drh';
    public const ROLE_RH          = 'rh';
    public const ROLE_MANAGER     = 'manager';
    public const ROLE_DIRECTION   = 'direction';
    public const ROLE_AUDITEUR    = 'auditeur';

    public static function roles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super administrateur',
            self::ROLE_ADMIN       => 'Administrateur',
            self::ROLE_DRH         => 'DRH',
            self::ROLE_RH          => 'Responsable RH',
            self::ROLE_MANAGER     => 'Manager',
            self::ROLE_DIRECTION   => 'Direction générale',
            self::ROLE_AUDITEUR    => 'Auditeur',
        ];
    }

    public function libelleRole(): string
    {
        return self::roles()[$this->role] ?? $this->role;
    }

    public function estSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function peut(string $permission): bool
    {
        return app(\App\Domain\Administration\Services\ServicePermissions::class)
            ->utilisateurPeut($this, $permission);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function permissionsIndividuelles(): HasMany
    {
        return $this->hasMany(PermissionUtilisateur::class, 'utilisateur_id');
    }

    public function journalAudit(): HasMany
    {
        return $this->hasMany(JournalAudit::class, 'utilisateur_id');
    }
}
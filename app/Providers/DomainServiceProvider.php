<?php

namespace App\Providers;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Models\PermissionRole;
use App\Domain\Administration\Models\PermissionUtilisateur;
use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Administration\Observers\AuditObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Domain\Administration\Policies\EntreprisePolicy;
use App\Domain\Administration\Policies\JournalAuditPolicy;
use App\Domain\Administration\Policies\UtilisateurPolicy;
use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Classification\Models\ReferentielClassification;
use App\Domain\Classification\Models\RegleEvolution;
use App\Domain\Classification\Policies\PositionPolicy;
use App\Domain\Classification\Policies\ReferentielPolicy;
use App\Domain\Classification\Policies\RegleEvolutionPolicy;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Organisation\Models\TypeStructure;
use App\Domain\Organisation\Policies\PostePolicy;
use App\Domain\Organisation\Policies\StructurePolicy;
use App\Domain\Organisation\Policies\TypeStructurePolicy;

use App\Domain\Personnel\Models\DocumentSalarie;
use App\Domain\Personnel\Models\MembreFoyer;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Policies\DocumentSalariePolicy;
use App\Domain\Personnel\Policies\MembreFoyerPolicy;
use App\Domain\Personnel\Policies\SalariePolicy;

use App\Domain\Contrats\Models\AlerteContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\EvenementEssai;
use App\Domain\Contrats\Policies\AlerteContratPolicy;
use App\Domain\Contrats\Policies\ContratPolicy;
use App\Domain\Contrats\Policies\EvenementEssaiPolicy;


class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Domain\Administration\Services\ServicePermissions::class);
        $this->app->singleton(\App\Domain\Administration\Services\ServiceAudit::class);
        $this->app->singleton(\App\Domain\Administration\Services\VerificateurMotDePasseLegacy::class);
    }

    public function boot(): void
    {
        $observer = new AuditObserver(app(\App\Domain\Administration\Services\ServiceAudit::class));

        foreach ([Entreprise::class, Utilisateur::class, PermissionRole::class, PermissionUtilisateur::class] as $model) {
            $model::observe($observer);
        }

        Gate::define('permission', function (Utilisateur $user, string $permission) {
            return $user->peut($permission);
        });

       

// Dans boot()
Gate::policy(Entreprise::class, EntreprisePolicy::class);
Gate::policy(Utilisateur::class, UtilisateurPolicy::class);
Gate::policy(JournalAudit::class, JournalAuditPolicy::class);
Gate::policy(TypeStructure::class, TypeStructurePolicy::class);
Gate::policy(Structure::class, StructurePolicy::class);
Gate::policy(Poste::class, PostePolicy::class);
Gate::policy(ReferentielClassification::class, ReferentielPolicy::class);
Gate::policy(PositionClassification::class, PositionPolicy::class);
Gate::policy(RegleEvolution::class, RegleEvolutionPolicy::class);


Gate::policy(Salarie::class, SalariePolicy::class);
Gate::policy(MembreFoyer::class, MembreFoyerPolicy::class);
Gate::policy(DocumentSalarie::class, DocumentSalariePolicy::class);

Gate::policy(Contrat::class, ContratPolicy::class);
Gate::policy(EvenementEssai::class, EvenementEssaiPolicy::class);
Gate::policy(AlerteContrat::class, AlerteContratPolicy::class);
    }
}
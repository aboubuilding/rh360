<?php

namespace App\Providers;

use App\Domain\Administration\Models\Entreprise;
use App\Domain\Administration\Models\JournalAudit;
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

use App\Domain\Carriere\Models\InstantaneCarriere;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Domain\Carriere\Models\SituationCarriere;
use App\Domain\Carriere\Policies\InstantaneCarrierePolicy;
use App\Domain\Carriere\Policies\MouvementCarrierePolicy;
use App\Domain\Carriere\Policies\SituationCarrierePolicy;

use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\DemandeConge;
use App\Domain\Conges\Models\DossierMaternite;
use App\Domain\Conges\Models\SoldeConge;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Policies\AbsencePolicy;
use App\Domain\Conges\Policies\DemandeCongePolicy;
use App\Domain\Conges\Policies\DossierMaternitePolicy;
use App\Domain\Conges\Policies\SoldeCongePolicy;
use App\Domain\Conges\Policies\TypeCongePolicy;

use App\Domain\Paie\Models\BulletinPaie;
use App\Domain\Paie\Models\PeriodePaie;
use App\Domain\Paie\Models\RubriquePaie;
use App\Domain\Paie\Policies\BulletinPaiePolicy;
use App\Domain\Paie\Policies\PeriodePaiePolicy;
use App\Domain\Paie\Policies\RubriquePaiePolicy;

use App\Domain\Sst\Models\DotationEpi;
use App\Domain\Sst\Models\EvenementSecurite;
use App\Domain\Sst\Models\Habilitation;
use App\Domain\Sst\Models\Risque;
use App\Domain\Sst\Models\VisiteMedicale;
use App\Domain\Sst\Policies\DotationEpiPolicy;
use App\Domain\Sst\Policies\EvenementSecuritePolicy;
use App\Domain\Sst\Policies\HabilitationPolicy;
use App\Domain\Sst\Policies\RisquePolicy;
use App\Domain\Sst\Policies\VisiteMedicalePolicy;

use App\Domain\Formation\Models\BesoinFormation;
use App\Domain\Formation\Models\Formation;
use App\Domain\Formation\Models\ParticipantFormation;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Formation\Policies\BesoinFormationPolicy;
use App\Domain\Formation\Policies\FormationPolicy;
use App\Domain\Formation\Policies\ParticipantFormationPolicy;
use App\Domain\Formation\Policies\PlanFormationPolicy;
use App\Domain\Formation\Policies\SessionFormationPolicy;
use App\Domain\Performance\Models\CampagneEvaluation;
use App\Domain\Performance\Models\CritereEvaluation;
use App\Domain\Performance\Models\EntretienEvaluation;
use App\Domain\Performance\Models\ObjectifEvaluation;
use App\Domain\Performance\Policies\CampagneEvaluationPolicy;
use App\Domain\Performance\Policies\CritereEvaluationPolicy;
use App\Domain\Performance\Policies\EntretienEvaluationPolicy;
use App\Domain\Performance\Policies\ObjectifEvaluationPolicy;
use App\Domain\Recrutement\Models\BesoinRecrutement;
use App\Domain\Recrutement\Models\Candidat;
use App\Domain\Recrutement\Policies\BesoinRecrutementPolicy;
use App\Domain\Recrutement\Policies\CandidatPolicy;


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


Gate::policy(MouvementCarriere::class, MouvementCarrierePolicy::class);
Gate::policy(SituationCarriere::class, SituationCarrierePolicy::class);
Gate::policy(InstantaneCarriere::class, InstantaneCarrierePolicy::class);

// Policies Carrière
Gate::policy(\App\Domain\Carriere\Models\MouvementCarriere::class, \App\Domain\Carriere\Policies\MouvementCarrierePolicy::class);
Gate::policy(\App\Domain\Carriere\Models\SituationCarriere::class, \App\Domain\Carriere\Policies\SituationCarrierePolicy::class);
Gate::policy(\App\Domain\Carriere\Models\InstantaneCarriere::class, \App\Domain\Carriere\Policies\InstantaneCarrierePolicy::class);


Gate::policy(TypeConge::class, TypeCongePolicy::class);
Gate::policy(SoldeConge::class, SoldeCongePolicy::class);
Gate::policy(DemandeConge::class, DemandeCongePolicy::class);
Gate::policy(Absence::class, AbsencePolicy::class);
Gate::policy(DossierMaternite::class, DossierMaternitePolicy::class);

Gate::policy(PeriodePaie::class, PeriodePaiePolicy::class);
Gate::policy(BulletinPaie::class, BulletinPaiePolicy::class);
Gate::policy(RubriquePaie::class, RubriquePaiePolicy::class);
Gate::policy(\App\Domain\Paie\Models\ModelePaie::class, \App\Domain\Paie\Policies\ModelePaiePolicy::class);
Gate::policy(\App\Domain\Paie\Models\HeureSupplementaire::class, \App\Domain\Paie\Policies\HeureSupplementairePolicy::class);

Gate::policy(VisiteMedicale::class, VisiteMedicalePolicy::class);
Gate::policy(EvenementSecurite::class, EvenementSecuritePolicy::class);
Gate::policy(Risque::class, RisquePolicy::class);
Gate::policy(DotationEpi::class, DotationEpiPolicy::class);
Gate::policy(Habilitation::class, HabilitationPolicy::class);


// Formation
Gate::policy(Formation::class, FormationPolicy::class);
Gate::policy(BesoinFormation::class, BesoinFormationPolicy::class);
Gate::policy(PlanFormation::class, PlanFormationPolicy::class);
Gate::policy(SessionFormation::class, SessionFormationPolicy::class);
Gate::policy(ParticipantFormation::class, ParticipantFormationPolicy::class);

// Performance
Gate::policy(CampagneEvaluation::class, CampagneEvaluationPolicy::class);
Gate::policy(CritereEvaluation::class, CritereEvaluationPolicy::class);
Gate::policy(ObjectifEvaluation::class, ObjectifEvaluationPolicy::class);
Gate::policy(EntretienEvaluation::class, EntretienEvaluationPolicy::class);

// Recrutement
Gate::policy(BesoinRecrutement::class, BesoinRecrutementPolicy::class);
Gate::policy(Candidat::class, CandidatPolicy::class);


    }


}
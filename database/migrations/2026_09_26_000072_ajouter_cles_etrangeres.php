<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajout de toutes les clés étrangères, une fois l'ensemble des tables créé.
 * (Le schéma comporte des références croisées et auto-référentielles.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referentiels_classification', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'referentiels_classification_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('parametres_contrats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'parametres_contrats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('salaries', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'salaries_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('fusionne_dans_salarie_id', 'salaries_fusionne_dans_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('types_conges', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'types_conges_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('types_structures', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'types_structures_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('regles_cotisations', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'regles_cotisations_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('rubriques_paie', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'rubriques_paie_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'regles_anciennete_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('regles_irpp', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'regles_irpp_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('campagnes_evaluation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'campagnes_evaluation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('criteres_evaluation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'criteres_evaluation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('besoins_recrutement', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'besoins_recrutement_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('permissions_roles', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'permissions_roles_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('formations', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'formations_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('plan_formation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'plan_formation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'utilisateurs_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('absences', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'absences_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'absences_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('type_conge_id', 'absences_type_conge_id_fk')->references('id')->on('types_conges')->restrictOnDelete();
            $table->foreign('transmis_paie_par', 'absences_transmis_paie_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
            $table->foreign('cree_par', 'absences_cree_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('journal_audit', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'journal_audit_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('utilisateur_id', 'journal_audit_utilisateur_id_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('categories_classification', function (Blueprint $table) {
            $table->foreign('referentiel_id', 'categories_classification_referentiel_id_fk')->references('id')->on('referentiels_classification')->restrictOnDelete();
        });
        Schema::table('classes_classification', function (Blueprint $table) {
            $table->foreign('referentiel_id', 'classes_classification_referentiel_id_fk')->references('id')->on('referentiels_classification')->restrictOnDelete();
        });
        Schema::table('echelons_classification', function (Blueprint $table) {
            $table->foreign('referentiel_id', 'echelons_classification_referentiel_id_fk')->references('id')->on('referentiels_classification')->restrictOnDelete();
        });
        Schema::table('documents_salaries', function (Blueprint $table) {
            $table->foreign('salarie_id', 'documents_salaries_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('brouillons_salaries', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'brouillons_salaries_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('utilisateur_id', 'brouillons_salaries_utilisateur_id_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('elements_paie_salaries', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'elements_paie_salaries_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'elements_paie_salaries_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('rubrique_id', 'elements_paie_salaries_rubrique_id_fk')->references('id')->on('rubriques_paie')->restrictOnDelete();
        });
        Schema::table('regles_evolution', function (Blueprint $table) {
            $table->foreign('referentiel_id', 'regles_evolution_referentiel_id_fk')->references('id')->on('referentiels_classification')->restrictOnDelete();
        });
        Schema::table('visites_medicales', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'visites_medicales_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'visites_medicales_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('visite_origine_id', 'visites_medicales_visite_origine_id_fk')->references('id')->on('visites_medicales')->restrictOnDelete();
            $table->foreign('cree_par', 'visites_medicales_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('modifie_par', 'visites_medicales_modifie_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('membres_foyer', function (Blueprint $table) {
            $table->foreign('salarie_id', 'membres_foyer_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('soldes_conges', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'soldes_conges_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'soldes_conges_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('type_conge_id', 'soldes_conges_type_conge_id_fk')->references('id')->on('types_conges')->restrictOnDelete();
        });
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'demandes_conges_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'demandes_conges_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('type_conge_id', 'demandes_conges_type_conge_id_fk')->references('id')->on('types_conges')->restrictOnDelete();
            $table->foreign('cree_par', 'demandes_conges_cree_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
            $table->foreign('valide_par', 'demandes_conges_valide_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('dossiers_maternite', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'dossiers_maternite_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'dossiers_maternite_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('cree_par', 'dossiers_maternite_cree_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('structures', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'structures_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('type_structure_id', 'structures_type_structure_id_fk')->references('id')->on('types_structures')->restrictOnDelete();
            $table->foreign('parent_id', 'structures_parent_id_fk')->references('id')->on('structures')->restrictOnDelete();
        });
        Schema::table('periodes_paie', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'periodes_paie_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('valide_par', 'periodes_paie_valide_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('objectifs_evaluation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'objectifs_evaluation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('campagne_id', 'objectifs_evaluation_campagne_id_fk')->references('id')->on('campagnes_evaluation')->restrictOnDelete();
            $table->foreign('salarie_id', 'objectifs_evaluation_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('entretiens_evaluation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'entretiens_evaluation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('campagne_id', 'entretiens_evaluation_campagne_id_fk')->references('id')->on('campagnes_evaluation')->restrictOnDelete();
            $table->foreign('salarie_id', 'entretiens_evaluation_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('candidats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'candidats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('besoin_id', 'candidats_besoin_id_fk')->references('id')->on('besoins_recrutement')->restrictOnDelete();
        });
        Schema::table('evenements_securite', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'evenements_securite_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('cree_par', 'evenements_securite_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('modifie_par', 'evenements_securite_modifie_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('pieces_jointes_sst', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'pieces_jointes_sst_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('auteur_id', 'pieces_jointes_sst_auteur_id_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('historique_sst', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'historique_sst_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('auteur_id', 'historique_sst_auteur_id_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('besoins_formation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'besoins_formation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'besoins_formation_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('cree_par', 'besoins_formation_cree_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('sessions_formation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'sessions_formation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('plan_formation_id', 'sessions_formation_plan_formation_id_fk')->references('id')->on('plan_formation')->restrictOnDelete();
        });
        Schema::table('permissions_utilisateurs', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'permissions_utilisateurs_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('utilisateur_id', 'permissions_utilisateurs_utilisateur_id_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('positions_classification', function (Blueprint $table) {
            $table->foreign('referentiel_id', 'positions_classification_referentiel_id_fk')->references('id')->on('referentiels_classification')->restrictOnDelete();
            $table->foreign('categorie_id', 'positions_classification_categorie_id_fk')->references('id')->on('categories_classification')->restrictOnDelete();
            $table->foreign('classe_id', 'positions_classification_classe_id_fk')->references('id')->on('classes_classification')->restrictOnDelete();
            $table->foreign('echelon_id', 'positions_classification_echelon_id_fk')->references('id')->on('echelons_classification')->restrictOnDelete();
            $table->foreign('position_conformite_id', 'positions_classification_position_conformite_id_fk')->references('id')->on('positions_classification')->restrictOnDelete();
            $table->foreign('position_suivante_id', 'positions_classification_position_suivante_id_fk')->references('id')->on('positions_classification')->restrictOnDelete();
        });
        Schema::table('regles_contrats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'regles_contrats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('categorie_id', 'regles_contrats_categorie_id_fk')->references('id')->on('categories_classification')->restrictOnDelete();
            $table->foreign('cree_par', 'regles_contrats_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('saisies_paie', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'saisies_paie_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('periode_id', 'saisies_paie_periode_id_fk')->references('id')->on('periodes_paie')->restrictOnDelete();
            $table->foreign('salarie_id', 'saisies_paie_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('rubrique_id', 'saisies_paie_rubrique_id_fk')->references('id')->on('rubriques_paie')->restrictOnDelete();
        });
        Schema::table('heures_supplementaires', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'heures_supplementaires_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'heures_supplementaires_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('periode_paiement_id', 'heures_supplementaires_periode_paiement_id_fk')->references('id')->on('periodes_paie')->restrictOnDelete();
            $table->foreign('periode_origine_id', 'heures_supplementaires_periode_origine_id_fk')->references('id')->on('periodes_paie')->restrictOnDelete();
            $table->foreign('cree_par', 'heures_supplementaires_cree_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('modeles_paie', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'modeles_paie_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('categorie_id', 'modeles_paie_categorie_id_fk')->references('id')->on('categories_classification')->restrictOnDelete();
        });
        Schema::table('bulletins_paie', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'bulletins_paie_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('periode_id', 'bulletins_paie_periode_id_fk')->references('id')->on('periodes_paie')->restrictOnDelete();
            $table->foreign('salarie_id', 'bulletins_paie_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('postes', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'postes_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('structure_id', 'postes_structure_id_fk')->references('id')->on('structures')->restrictOnDelete();
        });
        Schema::table('actions_securite', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'actions_securite_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('evenement_id', 'actions_securite_evenement_id_fk')->references('id')->on('evenements_securite')->restrictOnDelete();
            $table->foreign('responsable_salarie_id', 'actions_securite_responsable_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('cree_par', 'actions_securite_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('modifie_par', 'actions_securite_modifie_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('participants_evenements', function (Blueprint $table) {
            $table->foreign('evenement_id', 'participants_evenements_evenement_id_fk')->references('id')->on('evenements_securite')->restrictOnDelete();
            $table->foreign('salarie_id', 'participants_evenements_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('participants_formation', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'participants_formation_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('session_formation_id', 'participants_formation_session_formation_id_fk')->references('id')->on('sessions_formation')->restrictOnDelete();
            $table->foreign('salarie_id', 'participants_formation_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
        });
        Schema::table('mouvements_carriere', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'mouvements_carriere_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'mouvements_carriere_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('structure_depart_id', 'mouvements_carriere_structure_depart_id_fk')->references('id')->on('structures')->restrictOnDelete();
            $table->foreign('structure_cible_id', 'mouvements_carriere_structure_cible_id_fk')->references('id')->on('structures')->restrictOnDelete();
            $table->foreign('poste_depart_id', 'mouvements_carriere_poste_depart_id_fk')->references('id')->on('postes')->restrictOnDelete();
            $table->foreign('poste_cible_id', 'mouvements_carriere_poste_cible_id_fk')->references('id')->on('postes')->restrictOnDelete();
            $table->foreign('position_classification_depart_id', 'mouvements_carriere_position_classification_depart_id_fk')->references('id')->on('positions_classification')->restrictOnDelete();
            $table->foreign('position_classification_cible_id', 'mouvements_carriere_position_classification_cible_id_fk')->references('id')->on('positions_classification')->restrictOnDelete();
            $table->foreign('cree_par', 'mouvements_carriere_cree_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
            $table->foreign('controle_par', 'mouvements_carriere_controle_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
            $table->foreign('valide_par', 'mouvements_carriere_valide_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('contrats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'contrats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'contrats_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('parent_id', 'contrats_parent_id_fk')->references('id')->on('contrats')->restrictOnDelete();
            $table->foreign('poste_id', 'contrats_poste_id_fk')->references('id')->on('postes')->restrictOnDelete();
            $table->foreign('position_classification_id', 'contrats_position_classification_id_fk')->references('id')->on('positions_classification')->restrictOnDelete();
            $table->foreign('cree_par', 'contrats_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('affectations', function (Blueprint $table) {
            $table->foreign('salarie_id', 'affectations_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('structure_id', 'affectations_structure_id_fk')->references('id')->on('structures')->restrictOnDelete();
            $table->foreign('poste_id', 'affectations_poste_id_fk')->references('id')->on('postes')->restrictOnDelete();
        });
        Schema::table('situations_carriere', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'situations_carriere_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'situations_carriere_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('position_classification_id', 'situations_carriere_position_classification_id_fk')->references('id')->on('positions_classification')->restrictOnDelete();
            $table->foreign('position_ouverture_id', 'situations_carriere_position_ouverture_id_fk')->references('id')->on('positions_classification')->restrictOnDelete();
            $table->foreign('enregistre_par', 'situations_carriere_enregistre_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('modeles_paie_rubriques', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'modeles_paie_rubriques_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('modele_id', 'modeles_paie_rubriques_modele_id_fk')->references('id')->on('modeles_paie')->restrictOnDelete();
            $table->foreign('rubrique_id', 'modeles_paie_rubriques_rubrique_id_fk')->references('id')->on('rubriques_paie')->restrictOnDelete();
        });
        Schema::table('lignes_bulletins', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'lignes_bulletins_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('bulletin_id', 'lignes_bulletins_bulletin_id_fk')->references('id')->on('bulletins_paie')->restrictOnDelete();
            $table->foreign('rubrique_id', 'lignes_bulletins_rubrique_id_fk')->references('id')->on('rubriques_paie')->restrictOnDelete();
        });
        Schema::table('risques', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'risques_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('poste_id', 'risques_poste_id_fk')->references('id')->on('postes')->restrictOnDelete();
            $table->foreign('responsable_salarie_id', 'risques_responsable_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('cree_par', 'risques_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('modifie_par', 'risques_modifie_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('instantanes_carriere', function (Blueprint $table) {
            $table->foreign('mouvement_id', 'instantanes_carriere_mouvement_id_fk')->references('id')->on('mouvements_carriere')->restrictOnDelete();
            $table->foreign('entreprise_id', 'instantanes_carriere_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
        });
        Schema::table('pieces_contrats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'pieces_contrats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('contrat_id', 'pieces_contrats_contrat_id_fk')->references('id')->on('contrats')->restrictOnDelete();
            $table->foreign('cree_par', 'pieces_contrats_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('historique_contrats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'historique_contrats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('contrat_id', 'historique_contrats_contrat_id_fk')->references('id')->on('contrats')->restrictOnDelete();
            $table->foreign('utilisateur_id', 'historique_contrats_utilisateur_id_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('evenements_essai', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'evenements_essai_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('contrat_id', 'evenements_essai_contrat_id_fk')->references('id')->on('contrats')->restrictOnDelete();
            $table->foreign('cree_par', 'evenements_essai_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('decide_par', 'evenements_essai_decide_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
        });
        Schema::table('rappels_avancement', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'rappels_avancement_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'rappels_avancement_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('mouvement_id', 'rappels_avancement_mouvement_id_fk')->references('id')->on('mouvements_carriere')->restrictOnDelete();
            $table->foreign('bulletin_source_id', 'rappels_avancement_bulletin_source_id_fk')->references('id')->on('bulletins_paie')->restrictOnDelete();
            $table->foreign('periode_generation_id', 'rappels_avancement_periode_generation_id_fk')->references('id')->on('periodes_paie')->restrictOnDelete();
        });
        Schema::table('dotations_epi', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'dotations_epi_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'dotations_epi_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('risque_id', 'dotations_epi_risque_id_fk')->references('id')->on('risques')->restrictOnDelete();
            $table->foreign('cree_par', 'dotations_epi_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('modifie_par', 'dotations_epi_modifie_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('actions_risques', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'actions_risques_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('risque_id', 'actions_risques_risque_id_fk')->references('id')->on('risques')->restrictOnDelete();
            $table->foreign('responsable_salarie_id', 'actions_risques_responsable_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('cree_par', 'actions_risques_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('modifie_par', 'actions_risques_modifie_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('evaluations_risques', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'evaluations_risques_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('risque_id', 'evaluations_risques_risque_id_fk')->references('id')->on('risques')->restrictOnDelete();
            $table->foreign('cree_par', 'evaluations_risques_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('habilitations', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'habilitations_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('salarie_id', 'habilitations_salarie_id_fk')->references('id')->on('salaries')->restrictOnDelete();
            $table->foreign('risque_id', 'habilitations_risque_id_fk')->references('id')->on('risques')->restrictOnDelete();
            $table->foreign('habilitation_origine_id', 'habilitations_habilitation_origine_id_fk')->references('id')->on('habilitations')->restrictOnDelete();
            $table->foreign('cree_par', 'habilitations_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
            $table->foreign('modifie_par', 'habilitations_modifie_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('alertes_contrats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'alertes_contrats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('contrat_id', 'alertes_contrats_contrat_id_fk')->references('id')->on('contrats')->restrictOnDelete();
            $table->foreign('cloture_par', 'alertes_contrats_cloture_par_fk')->references('id')->on('utilisateurs')->nullOnDelete();
            $table->foreign('piece_id', 'alertes_contrats_piece_id_fk')->references('id')->on('pieces_contrats')->restrictOnDelete();
        });
        Schema::table('operations_epi', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'operations_epi_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('dotation_id', 'operations_epi_dotation_id_fk')->references('id')->on('dotations_epi')->restrictOnDelete();
            $table->foreign('cree_par', 'operations_epi_cree_par_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
        Schema::table('notifications_contrats', function (Blueprint $table) {
            $table->foreign('entreprise_id', 'notifications_contrats_entreprise_id_fk')->references('id')->on('entreprises')->restrictOnDelete();
            $table->foreign('alerte_id', 'notifications_contrats_alerte_id_fk')->references('id')->on('alertes_contrats')->restrictOnDelete();
            $table->foreign('utilisateur_id', 'notifications_contrats_utilisateur_id_fk')->references('id')->on('utilisateurs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referentiels_classification', function (Blueprint $table) {
            $table->dropForeign('referentiels_classification_entreprise_id_fk');
        });
        Schema::table('parametres_contrats', function (Blueprint $table) {
            $table->dropForeign('parametres_contrats_entreprise_id_fk');
        });
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropForeign('salaries_entreprise_id_fk');
            $table->dropForeign('salaries_fusionne_dans_salarie_id_fk');
        });
        Schema::table('types_conges', function (Blueprint $table) {
            $table->dropForeign('types_conges_entreprise_id_fk');
        });
        Schema::table('types_structures', function (Blueprint $table) {
            $table->dropForeign('types_structures_entreprise_id_fk');
        });
        Schema::table('regles_cotisations', function (Blueprint $table) {
            $table->dropForeign('regles_cotisations_entreprise_id_fk');
        });
        Schema::table('rubriques_paie', function (Blueprint $table) {
            $table->dropForeign('rubriques_paie_entreprise_id_fk');
        });
        Schema::table('regles_anciennete', function (Blueprint $table) {
            $table->dropForeign('regles_anciennete_entreprise_id_fk');
        });
        Schema::table('regles_irpp', function (Blueprint $table) {
            $table->dropForeign('regles_irpp_entreprise_id_fk');
        });
        Schema::table('campagnes_evaluation', function (Blueprint $table) {
            $table->dropForeign('campagnes_evaluation_entreprise_id_fk');
        });
        Schema::table('criteres_evaluation', function (Blueprint $table) {
            $table->dropForeign('criteres_evaluation_entreprise_id_fk');
        });
        Schema::table('besoins_recrutement', function (Blueprint $table) {
            $table->dropForeign('besoins_recrutement_entreprise_id_fk');
        });
        Schema::table('permissions_roles', function (Blueprint $table) {
            $table->dropForeign('permissions_roles_entreprise_id_fk');
        });
        Schema::table('formations', function (Blueprint $table) {
            $table->dropForeign('formations_entreprise_id_fk');
        });
        Schema::table('plan_formation', function (Blueprint $table) {
            $table->dropForeign('plan_formation_entreprise_id_fk');
        });
        Schema::table('utilisateurs', function (Blueprint $table) {
            $table->dropForeign('utilisateurs_entreprise_id_fk');
        });
        Schema::table('absences', function (Blueprint $table) {
            $table->dropForeign('absences_entreprise_id_fk');
            $table->dropForeign('absences_salarie_id_fk');
            $table->dropForeign('absences_type_conge_id_fk');
            $table->dropForeign('absences_transmis_paie_par_fk');
            $table->dropForeign('absences_cree_par_fk');
        });
        Schema::table('journal_audit', function (Blueprint $table) {
            $table->dropForeign('journal_audit_entreprise_id_fk');
            $table->dropForeign('journal_audit_utilisateur_id_fk');
        });
        Schema::table('categories_classification', function (Blueprint $table) {
            $table->dropForeign('categories_classification_referentiel_id_fk');
        });
        Schema::table('classes_classification', function (Blueprint $table) {
            $table->dropForeign('classes_classification_referentiel_id_fk');
        });
        Schema::table('echelons_classification', function (Blueprint $table) {
            $table->dropForeign('echelons_classification_referentiel_id_fk');
        });
        Schema::table('documents_salaries', function (Blueprint $table) {
            $table->dropForeign('documents_salaries_salarie_id_fk');
        });
        Schema::table('brouillons_salaries', function (Blueprint $table) {
            $table->dropForeign('brouillons_salaries_entreprise_id_fk');
            $table->dropForeign('brouillons_salaries_utilisateur_id_fk');
        });
        Schema::table('elements_paie_salaries', function (Blueprint $table) {
            $table->dropForeign('elements_paie_salaries_entreprise_id_fk');
            $table->dropForeign('elements_paie_salaries_salarie_id_fk');
            $table->dropForeign('elements_paie_salaries_rubrique_id_fk');
        });
        Schema::table('regles_evolution', function (Blueprint $table) {
            $table->dropForeign('regles_evolution_referentiel_id_fk');
        });
        Schema::table('visites_medicales', function (Blueprint $table) {
            $table->dropForeign('visites_medicales_entreprise_id_fk');
            $table->dropForeign('visites_medicales_salarie_id_fk');
            $table->dropForeign('visites_medicales_visite_origine_id_fk');
            $table->dropForeign('visites_medicales_cree_par_fk');
            $table->dropForeign('visites_medicales_modifie_par_fk');
        });
        Schema::table('membres_foyer', function (Blueprint $table) {
            $table->dropForeign('membres_foyer_salarie_id_fk');
        });
        Schema::table('soldes_conges', function (Blueprint $table) {
            $table->dropForeign('soldes_conges_entreprise_id_fk');
            $table->dropForeign('soldes_conges_salarie_id_fk');
            $table->dropForeign('soldes_conges_type_conge_id_fk');
        });
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropForeign('demandes_conges_entreprise_id_fk');
            $table->dropForeign('demandes_conges_salarie_id_fk');
            $table->dropForeign('demandes_conges_type_conge_id_fk');
            $table->dropForeign('demandes_conges_cree_par_fk');
            $table->dropForeign('demandes_conges_valide_par_fk');
        });
        Schema::table('dossiers_maternite', function (Blueprint $table) {
            $table->dropForeign('dossiers_maternite_entreprise_id_fk');
            $table->dropForeign('dossiers_maternite_salarie_id_fk');
            $table->dropForeign('dossiers_maternite_cree_par_fk');
        });
        Schema::table('structures', function (Blueprint $table) {
            $table->dropForeign('structures_entreprise_id_fk');
            $table->dropForeign('structures_type_structure_id_fk');
            $table->dropForeign('structures_parent_id_fk');
        });
        Schema::table('periodes_paie', function (Blueprint $table) {
            $table->dropForeign('periodes_paie_entreprise_id_fk');
            $table->dropForeign('periodes_paie_valide_par_fk');
        });
        Schema::table('objectifs_evaluation', function (Blueprint $table) {
            $table->dropForeign('objectifs_evaluation_entreprise_id_fk');
            $table->dropForeign('objectifs_evaluation_campagne_id_fk');
            $table->dropForeign('objectifs_evaluation_salarie_id_fk');
        });
        Schema::table('entretiens_evaluation', function (Blueprint $table) {
            $table->dropForeign('entretiens_evaluation_entreprise_id_fk');
            $table->dropForeign('entretiens_evaluation_campagne_id_fk');
            $table->dropForeign('entretiens_evaluation_salarie_id_fk');
        });
        Schema::table('candidats', function (Blueprint $table) {
            $table->dropForeign('candidats_entreprise_id_fk');
            $table->dropForeign('candidats_besoin_id_fk');
        });
        Schema::table('evenements_securite', function (Blueprint $table) {
            $table->dropForeign('evenements_securite_entreprise_id_fk');
            $table->dropForeign('evenements_securite_cree_par_fk');
            $table->dropForeign('evenements_securite_modifie_par_fk');
        });
        Schema::table('pieces_jointes_sst', function (Blueprint $table) {
            $table->dropForeign('pieces_jointes_sst_entreprise_id_fk');
            $table->dropForeign('pieces_jointes_sst_auteur_id_fk');
        });
        Schema::table('historique_sst', function (Blueprint $table) {
            $table->dropForeign('historique_sst_entreprise_id_fk');
            $table->dropForeign('historique_sst_auteur_id_fk');
        });
        Schema::table('besoins_formation', function (Blueprint $table) {
            $table->dropForeign('besoins_formation_entreprise_id_fk');
            $table->dropForeign('besoins_formation_salarie_id_fk');
            $table->dropForeign('besoins_formation_cree_par_fk');
        });
        Schema::table('sessions_formation', function (Blueprint $table) {
            $table->dropForeign('sessions_formation_entreprise_id_fk');
            $table->dropForeign('sessions_formation_plan_formation_id_fk');
        });
        Schema::table('permissions_utilisateurs', function (Blueprint $table) {
            $table->dropForeign('permissions_utilisateurs_entreprise_id_fk');
            $table->dropForeign('permissions_utilisateurs_utilisateur_id_fk');
        });
        Schema::table('positions_classification', function (Blueprint $table) {
            $table->dropForeign('positions_classification_referentiel_id_fk');
            $table->dropForeign('positions_classification_categorie_id_fk');
            $table->dropForeign('positions_classification_classe_id_fk');
            $table->dropForeign('positions_classification_echelon_id_fk');
            $table->dropForeign('positions_classification_position_conformite_id_fk');
            $table->dropForeign('positions_classification_position_suivante_id_fk');
        });
        Schema::table('regles_contrats', function (Blueprint $table) {
            $table->dropForeign('regles_contrats_entreprise_id_fk');
            $table->dropForeign('regles_contrats_categorie_id_fk');
            $table->dropForeign('regles_contrats_cree_par_fk');
        });
        Schema::table('saisies_paie', function (Blueprint $table) {
            $table->dropForeign('saisies_paie_entreprise_id_fk');
            $table->dropForeign('saisies_paie_periode_id_fk');
            $table->dropForeign('saisies_paie_salarie_id_fk');
            $table->dropForeign('saisies_paie_rubrique_id_fk');
        });
        Schema::table('heures_supplementaires', function (Blueprint $table) {
            $table->dropForeign('heures_supplementaires_entreprise_id_fk');
            $table->dropForeign('heures_supplementaires_salarie_id_fk');
            $table->dropForeign('heures_supplementaires_periode_paiement_id_fk');
            $table->dropForeign('heures_supplementaires_periode_origine_id_fk');
            $table->dropForeign('heures_supplementaires_cree_par_fk');
        });
        Schema::table('modeles_paie', function (Blueprint $table) {
            $table->dropForeign('modeles_paie_entreprise_id_fk');
            $table->dropForeign('modeles_paie_categorie_id_fk');
        });
        Schema::table('bulletins_paie', function (Blueprint $table) {
            $table->dropForeign('bulletins_paie_entreprise_id_fk');
            $table->dropForeign('bulletins_paie_periode_id_fk');
            $table->dropForeign('bulletins_paie_salarie_id_fk');
        });
        Schema::table('postes', function (Blueprint $table) {
            $table->dropForeign('postes_entreprise_id_fk');
            $table->dropForeign('postes_structure_id_fk');
        });
        Schema::table('actions_securite', function (Blueprint $table) {
            $table->dropForeign('actions_securite_entreprise_id_fk');
            $table->dropForeign('actions_securite_evenement_id_fk');
            $table->dropForeign('actions_securite_responsable_salarie_id_fk');
            $table->dropForeign('actions_securite_cree_par_fk');
            $table->dropForeign('actions_securite_modifie_par_fk');
        });
        Schema::table('participants_evenements', function (Blueprint $table) {
            $table->dropForeign('participants_evenements_evenement_id_fk');
            $table->dropForeign('participants_evenements_salarie_id_fk');
        });
        Schema::table('participants_formation', function (Blueprint $table) {
            $table->dropForeign('participants_formation_entreprise_id_fk');
            $table->dropForeign('participants_formation_session_formation_id_fk');
            $table->dropForeign('participants_formation_salarie_id_fk');
        });
        Schema::table('mouvements_carriere', function (Blueprint $table) {
            $table->dropForeign('mouvements_carriere_entreprise_id_fk');
            $table->dropForeign('mouvements_carriere_salarie_id_fk');
            $table->dropForeign('mouvements_carriere_structure_depart_id_fk');
            $table->dropForeign('mouvements_carriere_structure_cible_id_fk');
            $table->dropForeign('mouvements_carriere_poste_depart_id_fk');
            $table->dropForeign('mouvements_carriere_poste_cible_id_fk');
            $table->dropForeign('mouvements_carriere_position_classification_depart_id_fk');
            $table->dropForeign('mouvements_carriere_position_classification_cible_id_fk');
            $table->dropForeign('mouvements_carriere_cree_par_fk');
            $table->dropForeign('mouvements_carriere_controle_par_fk');
            $table->dropForeign('mouvements_carriere_valide_par_fk');
        });
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropForeign('contrats_entreprise_id_fk');
            $table->dropForeign('contrats_salarie_id_fk');
            $table->dropForeign('contrats_parent_id_fk');
            $table->dropForeign('contrats_poste_id_fk');
            $table->dropForeign('contrats_position_classification_id_fk');
            $table->dropForeign('contrats_cree_par_fk');
        });
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropForeign('affectations_salarie_id_fk');
            $table->dropForeign('affectations_structure_id_fk');
            $table->dropForeign('affectations_poste_id_fk');
        });
        Schema::table('situations_carriere', function (Blueprint $table) {
            $table->dropForeign('situations_carriere_entreprise_id_fk');
            $table->dropForeign('situations_carriere_salarie_id_fk');
            $table->dropForeign('situations_carriere_position_classification_id_fk');
            $table->dropForeign('situations_carriere_position_ouverture_id_fk');
            $table->dropForeign('situations_carriere_enregistre_par_fk');
        });
        Schema::table('modeles_paie_rubriques', function (Blueprint $table) {
            $table->dropForeign('modeles_paie_rubriques_entreprise_id_fk');
            $table->dropForeign('modeles_paie_rubriques_modele_id_fk');
            $table->dropForeign('modeles_paie_rubriques_rubrique_id_fk');
        });
        Schema::table('lignes_bulletins', function (Blueprint $table) {
            $table->dropForeign('lignes_bulletins_entreprise_id_fk');
            $table->dropForeign('lignes_bulletins_bulletin_id_fk');
            $table->dropForeign('lignes_bulletins_rubrique_id_fk');
        });
        Schema::table('risques', function (Blueprint $table) {
            $table->dropForeign('risques_entreprise_id_fk');
            $table->dropForeign('risques_poste_id_fk');
            $table->dropForeign('risques_responsable_salarie_id_fk');
            $table->dropForeign('risques_cree_par_fk');
            $table->dropForeign('risques_modifie_par_fk');
        });
        Schema::table('instantanes_carriere', function (Blueprint $table) {
            $table->dropForeign('instantanes_carriere_mouvement_id_fk');
            $table->dropForeign('instantanes_carriere_entreprise_id_fk');
        });
        Schema::table('pieces_contrats', function (Blueprint $table) {
            $table->dropForeign('pieces_contrats_entreprise_id_fk');
            $table->dropForeign('pieces_contrats_contrat_id_fk');
            $table->dropForeign('pieces_contrats_cree_par_fk');
        });
        Schema::table('historique_contrats', function (Blueprint $table) {
            $table->dropForeign('historique_contrats_entreprise_id_fk');
            $table->dropForeign('historique_contrats_contrat_id_fk');
            $table->dropForeign('historique_contrats_utilisateur_id_fk');
        });
        Schema::table('evenements_essai', function (Blueprint $table) {
            $table->dropForeign('evenements_essai_entreprise_id_fk');
            $table->dropForeign('evenements_essai_contrat_id_fk');
            $table->dropForeign('evenements_essai_cree_par_fk');
            $table->dropForeign('evenements_essai_decide_par_fk');
        });
        Schema::table('rappels_avancement', function (Blueprint $table) {
            $table->dropForeign('rappels_avancement_entreprise_id_fk');
            $table->dropForeign('rappels_avancement_salarie_id_fk');
            $table->dropForeign('rappels_avancement_mouvement_id_fk');
            $table->dropForeign('rappels_avancement_bulletin_source_id_fk');
            $table->dropForeign('rappels_avancement_periode_generation_id_fk');
        });
        Schema::table('dotations_epi', function (Blueprint $table) {
            $table->dropForeign('dotations_epi_entreprise_id_fk');
            $table->dropForeign('dotations_epi_salarie_id_fk');
            $table->dropForeign('dotations_epi_risque_id_fk');
            $table->dropForeign('dotations_epi_cree_par_fk');
            $table->dropForeign('dotations_epi_modifie_par_fk');
        });
        Schema::table('actions_risques', function (Blueprint $table) {
            $table->dropForeign('actions_risques_entreprise_id_fk');
            $table->dropForeign('actions_risques_risque_id_fk');
            $table->dropForeign('actions_risques_responsable_salarie_id_fk');
            $table->dropForeign('actions_risques_cree_par_fk');
            $table->dropForeign('actions_risques_modifie_par_fk');
        });
        Schema::table('evaluations_risques', function (Blueprint $table) {
            $table->dropForeign('evaluations_risques_entreprise_id_fk');
            $table->dropForeign('evaluations_risques_risque_id_fk');
            $table->dropForeign('evaluations_risques_cree_par_fk');
        });
        Schema::table('habilitations', function (Blueprint $table) {
            $table->dropForeign('habilitations_entreprise_id_fk');
            $table->dropForeign('habilitations_salarie_id_fk');
            $table->dropForeign('habilitations_risque_id_fk');
            $table->dropForeign('habilitations_habilitation_origine_id_fk');
            $table->dropForeign('habilitations_cree_par_fk');
            $table->dropForeign('habilitations_modifie_par_fk');
        });
        Schema::table('alertes_contrats', function (Blueprint $table) {
            $table->dropForeign('alertes_contrats_entreprise_id_fk');
            $table->dropForeign('alertes_contrats_contrat_id_fk');
            $table->dropForeign('alertes_contrats_cloture_par_fk');
            $table->dropForeign('alertes_contrats_piece_id_fk');
        });
        Schema::table('operations_epi', function (Blueprint $table) {
            $table->dropForeign('operations_epi_entreprise_id_fk');
            $table->dropForeign('operations_epi_dotation_id_fk');
            $table->dropForeign('operations_epi_cree_par_fk');
        });
        Schema::table('notifications_contrats', function (Blueprint $table) {
            $table->dropForeign('notifications_contrats_entreprise_id_fk');
            $table->dropForeign('notifications_contrats_alerte_id_fk');
            $table->dropForeign('notifications_contrats_utilisateur_id_fk');
        });
    }
};

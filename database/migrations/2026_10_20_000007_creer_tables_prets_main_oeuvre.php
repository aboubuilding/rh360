<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prêt de main-d'œuvre à but non lucratif entre entreprises (Code du travail, art. 8 —
 * RG-PMO-01 à RG-PMO-05). Le salarié reste dans l'effectif et la paie de l'entreprise prêteuse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prets_main_oeuvre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->string('reference', 60);
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->foreignId('mouvement_carriere_id')->nullable()->constrained('mouvements_carriere')->restrictOnDelete();

            // Entreprise utilisatrice
            $table->string('utilisatrice_raison_sociale');
            $table->string('utilisatrice_adresse')->nullable();
            $table->string('utilisatrice_contact')->nullable();

            // RG-PMO-02 — contenu de la convention (durée, qualification, calcul de la refacturation)
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('qualification');
            $table->text('mode_calcul_refacturation');
            // RG-PMO-02 — contenu de l'avenant (travail, horaires, lieu, caractéristiques du poste)
            $table->text('travail_confie');
            $table->string('horaires');
            $table->string('lieu');
            $table->text('caracteristiques_poste');

            // RG-PMO-03 — accord ou refus du salarié
            $table->string('reponse_salarie', 20)->default('en_attente');
            $table->date('date_reponse_salarie')->nullable();

            // RG-PMO-05 — situation au départ, garantie au retour
            $table->foreignId('poste_origine_id')->nullable()->constrained('postes')->restrictOnDelete();
            $table->foreignId('affectation_origine_id')->nullable()->constrained('affectations')->restrictOnDelete();
            $table->foreignId('position_origine_id')->nullable()->constrained('positions_classification')->restrictOnDelete();
            $table->unsignedInteger('salaire_base_origine')->nullable()->comment('FCFA');
            $table->date('date_retour')->nullable();
            $table->foreignId('poste_retour_id')->nullable()->constrained('postes')->restrictOnDelete();

            $table->string('statut', 30)->default('projet')->index();
            $table->text('motif_annulation')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->unique(['entreprise_id', 'reference'], 'prets_main_oeuvre_reference_unique');
        });

        // RG-PMO-02 — accord écrit du salarié, convention de mise à disposition, avenant signé.
        Schema::create('pieces_prets_main_oeuvre', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('pret_main_oeuvre_id')->constrained('prets_main_oeuvre')->restrictOnDelete();
            $table->string('type_piece', 40);
            $table->date('date_signature');
            $table->string('chemin', 500);
            $table->string('type_mime', 160)->nullable();
            $table->string('empreinte_sha256', 128);
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();
        });

        // RG-PMO-01 — refacturation sans marge : salaires + charges sociales + frais professionnels remboursés.
        Schema::create('refacturations_prets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('pret_main_oeuvre_id')->constrained('prets_main_oeuvre')->restrictOnDelete();
            $table->date('periode_debut');
            $table->date('periode_fin');
            $table->unsignedInteger('salaires')->comment('FCFA');
            $table->unsignedInteger('charges_sociales')->comment('FCFA');
            $table->unsignedInteger('frais_professionnels')->default(0)->comment('FCFA');
            $table->unsignedInteger('montant_refacture')->comment('FCFA');
            $table->text('alerte_marge')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refacturations_prets');
        Schema::dropIfExists('pieces_prets_main_oeuvre');
        Schema::dropIfExists('prets_main_oeuvre');
    }
};

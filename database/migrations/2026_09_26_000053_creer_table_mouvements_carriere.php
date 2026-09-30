<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table mouvements_carriere (reprise de la table « career_movements » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_carriere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('numero_mouvement', 160);
            $table->foreignId('salarie_id');
            $table->string('type_mouvement', 120);
            $table->string('sous_type_mouvement', 160)->nullable();
            $table->string('type_source', 120)->nullable();
            $table->string('reference_source')->nullable();
            $table->text('motif')->nullable();
            $table->string('statut', 80)->default('draft');
            $table->date('date_proposition')->nullable();
            $table->date('date_eligibilite')->nullable();
            $table->date('date_decision')->nullable();
            $table->date('date_acte')->nullable();
            $table->date('date_effet')->nullable();
            $table->date('date_notification')->nullable();
            $table->date('date_controle')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_fin_reelle')->nullable();
            $table->text('motif_cloture')->nullable();
            $table->foreignId('structure_depart_id')->nullable();
            $table->foreignId('structure_cible_id')->nullable();
            $table->foreignId('poste_depart_id')->nullable();
            $table->foreignId('poste_cible_id')->nullable();
            $table->foreignId('position_classification_depart_id')->nullable();
            $table->foreignId('position_classification_cible_id')->nullable();
            $table->string('lieu_affectation_depart')->nullable();
            $table->string('lieu_affectation_cible')->nullable();
            $table->string('reference_acte')->nullable();
            $table->string('chemin_justificatif', 500)->nullable();
            $table->string('type_saisie_source', 80)->default('normal');
            $table->string('reference_lot_reprise', 240)->nullable();
            $table->foreignId('cree_par')->nullable();
            $table->foreignId('controle_par')->nullable();
            $table->foreignId('valide_par')->nullable();
            $table->timestamps();
            $table->unique(['entreprise_id', 'numero_mouvement'], 'mouvements_carriere_entreprise_id_numero_mouvement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_carriere');
    }
};

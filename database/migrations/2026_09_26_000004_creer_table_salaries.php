<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table salaries (reprise de la table « employees » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->string('numero_enregistrement', 100)->nullable();
            $table->string('matricule', 200);
            $table->string('nom');
            $table->string('prenoms');
            $table->string('sexe', 60)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite', 200)->nullable();
            $table->string('chemin_photo', 500)->nullable();
            $table->string('type_piece', 200)->nullable();
            $table->string('numero_piece')->nullable();
            $table->date('date_expiration_piece')->nullable();
            $table->string('telephone_principal', 160)->nullable();
            $table->string('telephone_secondaire', 160)->nullable();
            $table->string('email_personnel')->nullable();
            $table->string('email_professionnel')->nullable();
            $table->text('adresse')->nullable();
            $table->string('ville', 200)->nullable();
            $table->string('pays_residence', 200)->nullable();
            $table->decimal('gps_latitude', 10, 7)->nullable();
            $table->decimal('gps_longitude', 10, 7)->nullable();
            $table->string('situation_matrimoniale', 160)->nullable();
            $table->string('contact_urgence_nom')->nullable();
            $table->string('contact_urgence_lien', 200)->nullable();
            $table->string('contact_urgence_telephone', 160)->nullable();
            $table->string('numero_cnss', 240)->nullable();
            $table->date('date_immatriculation_cnss')->nullable();
            $table->string('numero_amu', 240)->nullable();
            $table->string('organisme_assurance')->nullable();
            $table->string('banque')->nullable();
            $table->string('compte_bancaire')->nullable();
            $table->string('mode_paiement', 200)->nullable();
            $table->date('date_embauche')->nullable();
            $table->string('type_contrat', 160)->nullable();
            $table->string('reference_contrat')->nullable();
            $table->date('date_contrat')->nullable();
            $table->date('date_fin_contrat')->nullable();
            $table->date('date_prise_service')->nullable();
            $table->date('date_fin_essai')->nullable();
            $table->string('origine_carriere', 80)->default('legacy');
            $table->string('lieu_affectation')->nullable();
            $table->string('statut_emploi', 160)->default('Actif');
            $table->string('statut_dossier', 60)->default('incomplete');
            $table->foreignId('fusionne_dans_salarie_id')->nullable();
            $table->text('motif_fusion')->nullable();
            $table->dateTime('fusionne_le')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['entreprise_id', 'numero_enregistrement'], 'salaries_entreprise_id_numero_enregistrement_unique');
            $table->unique(['entreprise_id', 'matricule'], 'salaries_entreprise_id_matricule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};

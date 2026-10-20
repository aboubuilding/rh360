<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Travail intérimaire par agence (Code du travail, art. 60 à 63, 356 — RG-TT-01 à RG-TT-10).
 * Personnel externe : aucune ligne dans salaries, contrats ni paie. L'ERP enregistre
 * seulement les agences, les missions et les factures de l'agence.
 * Ajoute aussi le type d'absence « Grève » (RG-TT-05) à chaque entreprise.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entreprises_interim', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->string('raison_sociale');
            // RG-TT-01 — agrément du ministre chargé du travail
            $table->string('numero_agrement', 120)->nullable();
            $table->date('date_agrement')->nullable();
            $table->date('date_fin_validite_agrement')->nullable();
            $table->string('statut_agrement', 30)->default('valide');
            $table->string('adresse')->nullable();
            $table->string('contact')->nullable();
            $table->string('telephone', 60)->nullable();
            $table->string('email')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->unique(['entreprise_id', 'numero_agrement'], 'entreprises_interim_agrement_unique');
        });

        Schema::create('missions_interim', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('entreprise_interim_id')->constrained('entreprises_interim')->restrictOnDelete();
            $table->string('reference', 60);

            // RG-TT-02 — intérimaire (personnel de l'agence, hors effectif)
            $table->string('interimaire_nom');
            $table->string('interimaire_prenoms');
            $table->string('interimaire_piece', 160)->nullable();
            $table->string('interimaire_telephone', 60)->nullable();

            $table->foreignId('poste_id')->constrained('postes')->restrictOnDelete();
            $table->string('qualification_convenue');
            $table->text('travail_determine');
            $table->string('type_motif', 40);
            $table->text('motif');
            $table->foreignId('titulaire_remplace_id')->nullable()->constrained('salaries')->restrictOnDelete();

            $table->date('date_debut');
            $table->date('date_fin');
            $table->date('date_fin_reelle')->nullable();
            $table->unsignedSmallInteger('nombre_renouvellements')->default(0);

            // RG-TT-09 — contrôle de prudence du salaire convenu avec l'agence
            $table->unsignedInteger('salaire_convenu')->nullable()->comment('FCFA');
            $table->unsignedInteger('salaire_minimum_reference')->nullable()->comment('FCFA');
            $table->text('alerte_salaire')->nullable();

            // RG-TT-07 — checklist SST à valider avant le premier jour
            $table->date('sst_accueil_le')->nullable();
            $table->date('sst_epi_remis_le')->nullable();
            $table->date('sst_consignes_le')->nullable();
            $table->foreignId('sst_valide_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->dateTime('sst_valide_le')->nullable();

            $table->string('statut', 30)->default('planifiee')->index();
            $table->text('motif_annulation')->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->unique(['entreprise_id', 'reference'], 'missions_interim_reference_unique');
            $table->index(['poste_id', 'date_debut', 'date_fin']);
        });

        // RG-TT-04 — l'ERP n'enregistre que les factures de l'agence, seul employeur.
        Schema::create('factures_interim', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('entreprise_interim_id')->constrained('entreprises_interim')->restrictOnDelete();
            $table->foreignId('mission_interim_id')->nullable()->constrained('missions_interim')->restrictOnDelete();
            $table->string('numero_facture', 120);
            $table->date('date_facture');
            $table->date('periode_debut')->nullable();
            $table->date('periode_fin')->nullable();
            $table->unsignedInteger('montant')->comment('FCFA');
            $table->string('chemin_piece', 500)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->unique(['entreprise_interim_id', 'numero_facture'], 'factures_interim_numero_unique');
        });

        // RG-TT-05 — type d'absence « Grève », ajouté à chaque entreprise s'il n'existe pas.
        // Traitement salarial et impacts : à vérifier par les RH dans l'écran des types de congés.
        foreach (DB::table('entreprises')->pluck('id') as $entrepriseId) {
            $existe = DB::table('types_conges')->where('entreprise_id', $entrepriseId)->where('code', 'GREVE')->exists();
            if (! $existe) {
                DB::table('types_conges')->insert([
                    'entreprise_id' => $entrepriseId,
                    'code' => 'GREVE',
                    'nom' => 'Grève',
                    'categorie' => 'absence',
                    'unite' => 'jour_calendaire',
                    'droit_annuel' => 0,
                    'remunere' => false,
                    'justificatif_requis' => false,
                    'traitement_salarial' => 'non_remunere',
                    'impact_conge_annuel' => 'aucune',
                    'impact_anciennete' => 'maintenue',
                    'autorisation_prealable_requise' => false,
                    'actif' => true,
                    'etat' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('factures_interim');
        Schema::dropIfExists('missions_interim');
        Schema::dropIfExists('entreprises_interim');
        // Le type d'absence « Grève » est conservé : des absences peuvent y être rattachées.
    }
};

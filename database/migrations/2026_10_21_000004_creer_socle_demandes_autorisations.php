<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes et autorisations — socle (colonnes additives, aucune donnée existante modifiée).
 * - types_conges : famille, absence de droit ou soumise à accord, délais de dépôt et
 *   d'information, point de départ du délai de justificatif, plafond (valeur, unité, période,
 *   base), ancienneté minimale, prise en continu et report du crédit ;
 * - evenements_permission : durées des permissions exceptionnelles par événement (CCIT art. 50) ;
 * - jours_feries : calendrier de l'entreprise (délais en jours ouvrés, décompte des congés) ;
 * - parametres_demandes : surcharges, par entreprise, de config/demandes.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('types_conges', function (Blueprint $table) {
            $table->string('famille', 30)->nullable()->after('categorie');
            $table->boolean('de_droit')->default(false)->after('famille');
            $table->unsignedSmallInteger('delai_depot')->nullable()->after('autorisation_prealable_requise');
            $table->string('delai_depot_unite', 20)->nullable()->after('delai_depot');
            $table->unsignedSmallInteger('delai_information_heures')->nullable()->after('delai_depot_unite');
            $table->string('delai_justification_depart', 20)->nullable()->after('delai_justification_jours');
            $table->decimal('plafond_valeur', 8, 2)->nullable()->after('portee_duree_max');
            $table->string('plafond_unite', 10)->nullable()->after('plafond_valeur');
            $table->string('plafond_periode', 20)->nullable()->after('plafond_unite');
            $table->string('plafond_base', 20)->nullable()->after('plafond_periode');
            $table->unsignedSmallInteger('anciennete_min_mois')->nullable()->after('plafond_base');
            $table->boolean('prise_continue_autorisee')->default(true)->after('anciennete_min_mois');
            $table->boolean('report_credit_autorise')->default(true)->after('prise_continue_autorisee');
        });

        Schema::create('evenements_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('type_conge_id')->constrained('types_conges')->restrictOnDelete();
            $table->string('code', 40);
            $table->string('libelle', 160);
            $table->string('lien_famille', 30)->nullable();
            $table->decimal('jours', 5, 2);
            $table->unsignedSmallInteger('delai_prise_mois')->nullable();
            $table->string('piece_attendue', 200)->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->tinyInteger('etat')->default(1);
            $table->timestamps();
            $table->unique(['entreprise_id', 'code']);
        });

        Schema::create('jours_feries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->date('date');
            $table->string('libelle', 160);
            $table->string('origine', 20)->default('saisi');
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
            $table->unique(['entreprise_id', 'date']);
        });

        Schema::create('parametres_demandes', function (Blueprint $table) {
            $table->foreignId('entreprise_id')->primary()->constrained('entreprises')->restrictOnDelete();
            $table->json('valeurs');
            $table->integer('revision')->default(1);
            $table->foreignId('modifie_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres_demandes');
        Schema::dropIfExists('jours_feries');
        Schema::dropIfExists('evenements_permission');

        Schema::table('types_conges', function (Blueprint $table) {
            $table->dropColumn([
                'famille', 'de_droit', 'delai_depot', 'delai_depot_unite', 'delai_information_heures',
                'delai_justification_depart', 'plafond_valeur', 'plafond_unite', 'plafond_periode',
                'plafond_base', 'anciennete_min_mois', 'prise_continue_autorisee', 'report_credit_autorise',
            ]);
        });
    }
};

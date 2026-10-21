<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Congés payés — grand livre et circuit du départ (colonnes et tables additives).
 * - mouvements_conge : acquisitions mensuelles, reprise des soldes existants, prises, recrédits,
 *   ajustements, arrondis et expirations, par lot annuel ; les soldes_conges en sont la projection ;
 * - rappels_conge : jours travaillés pendant un congé, qui le prolongent d'autant ;
 * - demandes_conges : anticipation accordée, initiative du départ, date de communication de
 *   l'ordre de départ, première date de départ fixée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_conge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->foreignId('type_conge_id')->constrained('types_conges')->restrictOnDelete();
            $table->unsignedSmallInteger('lot');
            $table->string('nature', 20);
            $table->decimal('jours', 7, 2);
            $table->date('date_valeur');
            // Mois acquis (acquisition) : une seule acquisition par salarié, type et mois.
            $table->date('mois_reference')->nullable();
            $table->foreignId('demande_id')->nullable()->constrained('demandes_conges')->nullOnDelete();
            $table->string('commentaire', 500)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['salarie_id', 'type_conge_id', 'lot']);
            $table->unique(['salarie_id', 'type_conge_id', 'nature', 'mois_reference'], 'mouvements_conge_acquisition_unique');
        });

        Schema::create('rappels_conge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('demande_id')->constrained('demandes_conges')->cascadeOnDelete();
            $table->date('du');
            $table->date('au');
            $table->decimal('jours', 5, 2);
            $table->text('motif');
            $table->date('reprise_avant');
            $table->date('reprise_apres');
            $table->foreignId('ordonne_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->boolean('anticipation')->default(false)->after('force_majeure');
            $table->string('initiative', 20)->default('salarie')->after('anticipation');
            $table->date('ordre_depart_le')->nullable()->after('initiative');
            $table->date('date_debut_fixee')->nullable()->after('date_debut');
        });
    }

    public function down(): void
    {
        Schema::table('demandes_conges', function (Blueprint $table) {
            $table->dropColumn(['anticipation', 'initiative', 'ordre_depart_le', 'date_debut_fixee']);
        });
        Schema::dropIfExists('rappels_conge');
        Schema::dropIfExists('mouvements_conge');
    }
};

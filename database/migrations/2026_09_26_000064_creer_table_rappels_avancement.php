<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table rappels_avancement (reprise de la table « payroll_advancement_arrears » de l'application Python EXPERT RH 360 v0.20.2).
 * Les clés étrangères sont ajoutées par la migration finale « ajouter_cles_etrangeres ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rappels_avancement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id');
            $table->foreignId('salarie_id');
            $table->foreignId('mouvement_id');
            $table->foreignId('bulletin_source_id');
            $table->foreignId('periode_generation_id');
            $table->decimal('montant_rappel_base', 15, 2)->default(0);
            $table->decimal('montant_rappel_anciennete', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['mouvement_id', 'bulletin_source_id'], 'rappels_avancement_mouvement_id_bulletin_source_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rappels_avancement');
    }
};

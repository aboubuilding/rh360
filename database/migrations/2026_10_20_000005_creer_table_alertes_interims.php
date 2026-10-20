<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertes des intérims (échéances, passage au 3e mois, délai dépassé, maladie du
 * titulaire à 6 mois…), générées chaque jour par la commande interims:balayer.
 * Une alerte est unique par objet et par clé ; elle est close quand elle ne s'applique plus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes_interims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->morphs('objet');
            $table->string('cle', 80);
            $table->string('niveau', 20)->default('warning');
            $table->string('intitule');
            $table->text('message');
            $table->date('date_echeance')->nullable();
            $table->boolean('en_cours')->default(true)->index();
            $table->dateTime('cloture_le')->nullable();
            $table->timestamps();

            $table->unique(['objet_type', 'objet_id', 'cle'], 'alertes_interims_objet_cle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes_interims');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - decisions_interims : historique des décisions RH (qui, quand, pièce jointe),
 *   commun aux intérims internes, missions d'intérim et prêts de main-d'œuvre.
 * - avantages_interims : avantages du poste occupé saisis à la main avec
 *   justificatif (RG-INT-07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions_interims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->morphs('objet');
            $table->string('type_decision', 40);
            $table->json('donnees')->nullable();
            $table->text('commentaire')->nullable();
            $table->string('chemin_piece', 500)->nullable();
            $table->string('type_mime_piece', 160)->nullable();
            $table->string('empreinte_sha256', 128)->nullable();
            $table->foreignId('decide_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->dateTime('decide_le');
            $table->timestamps();
        });

        Schema::create('avantages_interims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('interim_interne_id')->constrained('interims_internes')->restrictOnDelete();
            $table->foreignId('rubrique_id')->constrained('rubriques_paie')->restrictOnDelete();
            $table->unsignedInteger('montant_mensuel')->comment('FCFA');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('reference_accord', 240);
            $table->string('chemin_justificatif', 500);
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avantages_interims');
        Schema::dropIfExists('decisions_interims');
    }
};

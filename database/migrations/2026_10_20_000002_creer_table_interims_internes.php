<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Intérim interne d'un emploi supérieur (CCIT art. 13 — RG-INT-01 à RG-INT-10).
 * Le mouvement de carrière associé reste l'acte de l'historique de carrière.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interims_internes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained('entreprises')->restrictOnDelete();
            $table->foreignId('mouvement_carriere_id')->nullable()->unique()
                ->constrained('mouvements_carriere')->restrictOnDelete();

            // RG-INT-01
            $table->foreignId('salarie_id')->constrained('salaries')->restrictOnDelete();
            $table->foreignId('poste_occupe_id')->constrained('postes')->restrictOnDelete();
            $table->foreignId('categorie_occupee_id')->constrained('categories_classification')->restrictOnDelete();
            $table->foreignId('categorie_origine_id')->nullable()->constrained('categories_classification')->restrictOnDelete();
            $table->foreignId('titulaire_id')->nullable()->constrained('salaries')->restrictOnDelete();
            $table->foreignId('absence_titulaire_id')->nullable()->constrained('absences')->nullOnDelete();
            $table->string('motif', 60);
            $table->text('precision_motif')->nullable();

            $table->date('date_debut');
            $table->date('date_fin_prevue');
            // RG-INT-05 — date saisie de fin du congé, de consolidation ou de guérison
            $table->date('date_fin_motif')->nullable();
            // RG-INT-03 / RG-INT-05 — date limite calculée (plafond légal applicable)
            $table->date('date_limite');
            $table->date('date_fin_reelle')->nullable();

            // Instantané de la situation d'origine (RG-INT-02, RG-INT-08 retour)
            $table->unsignedInteger('salaire_base_origine')->nullable()->comment('FCFA');
            $table->foreignId('position_origine_id')->nullable()->constrained('positions_classification')->restrictOnDelete();
            $table->foreignId('affectation_origine_id')->nullable()->constrained('affectations')->restrictOnDelete();

            $table->string('statut', 30)->default('en_cours')->index();
            $table->string('cause_fin', 40)->nullable();
            $table->string('decision_fin', 30)->nullable();
            $table->date('date_decision')->nullable();
            $table->foreignId('decide_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->text('motif_cloture')->nullable();

            $table->string('reference_lot_reprise', 240)->nullable();
            $table->foreignId('cree_par')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->tinyInteger('etat')->default(1)->index()->comment('1=actif, 0=inactif, -1=supprime');
            $table->timestamps();

            $table->index(['entreprise_id', 'statut', 'date_limite']);
            $table->index(['poste_occupe_id', 'titulaire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interims_internes');
    }
};

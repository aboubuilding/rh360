<?php

namespace App\Http\Controllers\Carriere;

use App\Domain\Carriere\Enums\StatutMouvement;
use App\Domain\Carriere\Models\MouvementCarriere;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingCarriereController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('permission', 'carriere.view');

        $entrepriseId = auth()->user()->entreprise_id;

        $du = $request->get('du', now()->startOfYear()->format('Y-m-d'));
        $au = $request->get('au', now()->endOfYear()->format('Y-m-d'));
        $base = $request->get('base', 'date_effet');

        $mouvements = MouvementCarriere::query()
            ->whereBetween($base, [$du, $au])
            ->get();

        // Indicateurs
        $indicateurs = [
            'total_actes' => $mouvements->count(),
            'salaries_distincts' => $mouvements->pluck('salarie_id')->unique()->count(),
            'par_type' => $mouvements->groupBy(fn ($m) => $m->type_mouvement->libelle())
                ->map->count()
                ->sortDesc()
                ->all(),
            'par_statut' => $mouvements->groupBy(fn ($m) => $m->statut->libelle())
                ->map->count()
                ->all(),
        ];

        // Historique : 3 vues
        $vue = $request->get('vue', 'actes');
        $historique = match ($vue) {
            'actes' => MouvementCarriere::where('statut', StatutMouvement::EFFECTIF->value)
                ->orderByDesc('date_effet')
                ->limit(200)
                ->get(),
            'situations_initiales' => \App\Domain\Carriere\Models\SituationCarriere::query()
                ->where('type_source', 'reprise')
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get(),
            'entrees_fonction' => MouvementCarriere::where('type_mouvement', 'affectation')
                ->where('statut', StatutMouvement::EFFECTIF->value)
                ->orderByDesc('date_effet')
                ->limit(200)
                ->get(),
            default => collect(),
        };

        return view('carriere.reporting.index', [
            'indicateurs' => $indicateurs,
            'historique' => $historique,
            'vue' => $vue,
            'du' => $du,
            'au' => $au,
            'base' => $base,
        ]);
    }
}
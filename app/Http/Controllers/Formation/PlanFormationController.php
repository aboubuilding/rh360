<?php

namespace App\Http\Controllers\Formation;

use App\Domain\Formation\Actions\CreerPlanFormation;
use App\Domain\Formation\Models\PlanFormation;
use App\Domain\Formation\Requests\StorePlanFormationRequest;
use App\Domain\Formation\Services\CalculateurBudgetFormation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanFormationController extends Controller
{
    public function __construct(private CalculateurBudgetFormation $budget) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PlanFormation::class);

        $plans = PlanFormation::query()
            ->annee($request->annee ? (int) $request->annee : null)
            ->orderByDesc('annee')
            ->paginate(20)
            ->withQueryString();

        $plans->getCollection()->transform(function ($plan) {
            $plan->etat_budget = $this->budget->etat($plan);
            return $plan;
        });

        return view('formation.plans.index', compact('plans'));
    }

    public function create()
    {
        $this->authorize('create', PlanFormation::class);
        return view('formation.plans.create');
    }

    public function store(StorePlanFormationRequest $request, CreerPlanFormation $action)
    {
        $this->authorize('create', PlanFormation::class);

        $plan = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Plan de formation créé.',
                'redirect' => route('formation.plans.show', $plan),
            ]);
        }

        return redirect()->route('formation.plans.show', $plan)
            ->with('success', 'Plan créé.');
    }

    public function show(PlanFormation $plan)
    {
        $this->authorize('view', $plan);

        $plan->load(['sessions.participants']);
        $etatBudget = $this->budget->etat($plan);

        return view('formation.plans.show', compact('plan', 'etatBudget'));
    }

    public function destroy(PlanFormation $plan)
    {
        $this->authorize('delete', $plan);
        $plan->marquerSupprime();

        return redirect()->route('formation.plans.index')
            ->with('success', 'Plan supprimé.');
    }
}
<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Administration\Services\ServiceAudit;
use App\Domain\Contrats\Enums\MotifFinContrat;
use App\Domain\Contrats\Enums\TypeContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * RG-REM-04 — informations de fin d'un CDD qui conditionnent l'indemnité de fin de contrat
 * (motif de fin, refus d'un CDI au moins équivalent).
 */
class FinCddController extends Controller
{
    public function __invoke(Request $request, Contrat $contrat, ServiceAudit $audit)
    {
        abort_unless($request->user()->peut('contrats.manage') && $request->user()->entreprise_id === $contrat->entreprise_id, 403);
        abort_unless($contrat->type_contrat === TypeContrat::CDD->value && $contrat->estSigne(), 422, 'Réservé à un CDD signé.');

        $donnees = $request->validate([
            'motif_fin' => ['nullable', Rule::enum(MotifFinContrat::class)],
            'refus_cdi_equivalent_le' => ['nullable', 'date'],
        ], [
            'motif_fin.enum' => 'Le motif de fin sélectionné est invalide.',
            'refus_cdi_equivalent_le.date' => 'La date du refus doit être une date valide.',
        ]);

        $avant = $contrat->only(['motif_fin', 'refus_cdi_equivalent_le']);
        $contrat->update($donnees);
        $audit->tracer('contrat.fin_cdd', $contrat, ['avant' => $avant, 'apres' => $donnees]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Informations de fin du CDD enregistrées.']);
        }

        return back()->with('success', 'Informations de fin du CDD enregistrées.');
    }
}

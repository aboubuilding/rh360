<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Models\EvenementPermission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Durées des permissions exceptionnelles par événement.
 */
class EvenementPermissionController extends Controller
{
    public function update(Request $request, EvenementPermission $evenement)
    {
        $this->authorize('permission', 'conges.manage');

        $donnees = $request->validate([
            'jours' => ['required', 'numeric', 'min:0.5', 'max:60'],
            'delai_prise_mois' => ['nullable', 'integer', 'min:1', 'max:24'],
            'piece_attendue' => ['nullable', 'string', 'max:200'],
            'actif' => ['boolean'],
        ], [
            'jours.required' => 'La durée est obligatoire.',
            'jours.min' => 'La durée doit être d\'au moins une demi-journée.',
        ]);

        // Une durée inférieure à celle des textes n'est pas admise : seules les surcharges plus favorables le sont.
        $minimum = collect(app(\App\Domain\Conges\Services\InstallateurCatalogueDemandes::class)->evenements())
            ->firstWhere(0, $evenement->code)[3] ?? null;
        if ($minimum !== null && (float) $donnees['jours'] < $minimum) {
            return $this->repondre($request, "La durée ne peut pas être inférieure à {$minimum} jour(s) pour cet événement.", 422);
        }

        $evenement->update($donnees + ['actif' => $request->boolean('actif')]);

        return $this->repondre($request, 'Événement mis à jour.');
    }

    private function repondre(Request $request, string $message, int $statut = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $statut);
        }

        return redirect()->route('conges.types.index')->with($statut === 200 ? 'success' : 'error', $message);
    }
}

<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Contrats\Actions\AjouterPieceContrat;
use App\Domain\Contrats\Enums\ObjetPieceContrat;
use App\Domain\Contrats\Models\Contrat;
use App\Domain\Contrats\Models\PieceContrat;
use App\Domain\Contrats\Requests\StorePieceContratRequest;
use App\Domain\Contrats\Services\GestionnairePieces;
use App\Http\Controllers\Controller;

class PieceContratController extends Controller
{
    public function store(StorePieceContratRequest $request, Contrat $contrat, AjouterPieceContrat $action)
    {
        $this->authorize('gererPieces', $contrat);

        $action->executer(
            $contrat,
            $request->file('fichier'),
            ObjetPieceContrat::from($request->objet),
            $request->libelle,
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Pièce ajoutée.']);
        }

        return back()->with('success', 'Pièce ajoutée.');
    }

    public function voir(Contrat $contrat, PieceContrat $piece)
    {
        $this->authorize('view', $contrat);
        $this->verifierAppartenance($contrat, $piece);

        return app(GestionnairePieces::class)->telecharger($piece);
    }

    public function destroy(Contrat $contrat, PieceContrat $piece)
    {
        $this->authorize('gererPieces', $contrat);
        $this->verifierAppartenance($contrat, $piece);

        app(GestionnairePieces::class)->supprimer($piece);

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Pièce supprimée.']);
        }

        return back()->with('success', 'Pièce supprimée.');
    }

    private function verifierAppartenance(Contrat $contrat, PieceContrat $piece): void
    {
        if ($piece->contrat_id !== $contrat->id) {
            abort(404, 'Pièce introuvable pour ce contrat.');
        }
    }
}
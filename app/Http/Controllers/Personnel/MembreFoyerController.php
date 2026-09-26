<?php

namespace App\Http\Controllers\Personnel;

use App\Domain\Personnel\Models\MembreFoyer;
use App\Domain\Personnel\Models\Salarie;
use App\Domain\Personnel\Requests\StoreMembreFoyerRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class MembreFoyerController extends Controller
{
    /**
     * Ajoute un membre au foyer d'un salarié.
     */
    public function store(StoreMembreFoyerRequest $request, Salarie $salarie)
    {
        $this->authorize('update', $salarie);

        $donnees = $request->validated();
        $donnees['salarie_id'] = $salarie->id;
        $donnees['actif'] = true;
        $donnees['etat'] = 1;

        // Pièce jointe : photo
        if ($request->hasFile('photo')) {
            $donnees['chemin_photo'] = $request->file('photo')
                ->store('salaries/foyer/photos', 'local');
        }

        // Pièce jointe : acte de naissance
        if ($request->hasFile('acte_naissance')) {
            $donnees['chemin_acte_naissance'] = $request->file('acte_naissance')
                ->store('salaries/foyer/actes', 'local');
        }

        // Nettoyage des clés techniques non mappées en base
        unset($donnees['photo'], $donnees['acte_naissance']);

        MembreFoyer::create($donnees);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Membre du foyer ajouté.']);
        }

        return back()->with('success', 'Membre du foyer ajouté.');
    }

    /**
     * Met à jour un membre du foyer.
     */
    public function update(StoreMembreFoyerRequest $request, Salarie $salarie, MembreFoyer $membre)
    {
        $this->authorize('update', $salarie);
        $this->verifierAppartenance($salarie, $membre);

        $donnees = $request->validated();

        // Pièce jointe : photo (remplace et supprime l'ancienne)
        if ($request->hasFile('photo')) {
            if ($membre->chemin_photo) {
                Storage::disk('local')->delete($membre->chemin_photo);
            }
            $donnees['chemin_photo'] = $request->file('photo')
                ->store('salaries/foyer/photos', 'local');
        }

        // Pièce jointe : acte de naissance (remplace et supprime l'ancien)
        if ($request->hasFile('acte_naissance')) {
            if ($membre->chemin_acte_naissance) {
                Storage::disk('local')->delete($membre->chemin_acte_naissance);
            }
            $donnees['chemin_acte_naissance'] = $request->file('acte_naissance')
                ->store('salaries/foyer/actes', 'local');
        }

        unset($donnees['photo'], $donnees['acte_naissance']);

        $membre->update($donnees);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Membre du foyer mis à jour.']);
        }

        return back()->with('success', 'Membre mis à jour.');
    }

    /**
     * Archive un membre (devient inactif mais reste visible dans l'historique).
     */
    public function archiver(Salarie $salarie, MembreFoyer $membre)
    {
        $this->authorize('update', $salarie);
        $this->verifierAppartenance($salarie, $membre);

        $membre->marquerInactif();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Membre archivé.']);
        }

        return back()->with('success', 'Membre archivé.');
    }

    /**
     * Restaure un membre précédemment archivé.
     */
    public function restaurer(Salarie $salarie, MembreFoyer $membre)
    {
        $this->authorize('update', $salarie);
        $this->verifierAppartenance($salarie, $membre);

        $membre->restaurer();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Membre restauré.']);
        }

        return back()->with('success', 'Membre restauré.');
    }

    /**
     * Supprime (logiquement) un membre du foyer.
     */
    public function destroy(Salarie $salarie, MembreFoyer $membre)
    {
        $this->authorize('update', $salarie);
        $this->verifierAppartenance($salarie, $membre);

        $membre->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Membre supprimé.']);
        }

        return back()->with('success', 'Membre supprimé.');
    }

    /**
     * Vérifie que le membre appartient bien au salarié de l'URL.
     * En défense en profondeur, même si scopeBindings() est actif sur la route.
     */
    private function verifierAppartenance(Salarie $salarie, MembreFoyer $membre): void
    {
        if ($membre->salarie_id !== $salarie->id) {
            abort(404, 'Membre introuvable pour ce salarié.');
        }
    }
}
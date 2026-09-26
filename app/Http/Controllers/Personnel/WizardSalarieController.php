<?php

namespace App\Http\Controllers\Personnel;

use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Actions\CreerSalarie;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WizardSalarieController extends Controller
{
    private const SESSION_KEY = 'wizard_salarie';
    private const TOTAL_ETAPES = 5;

    public function demarrer()
    {
        $this->authorize('create', Salarie::class);

        session()->forget(self::SESSION_KEY);
        session([self::SESSION_KEY => ['etape_courante' => 1, 'donnees' => []]]);

        return redirect()->route('personnel.salaries.wizard.etape', 1);
    }

    public function etape(Request $request, int $numero)
    {
        $this->authorize('create', Salarie::class);

        if ($numero < 1 || $numero > self::TOTAL_ETAPES) {
            abort(404);
        }

        $etat = session(self::SESSION_KEY, ['etape_courante' => 1, 'donnees' => []]);

        // Autoriser la navigation vers une étape déjà atteinte uniquement
        if ($numero > $etat['etape_courante']) {
            return redirect()->route('personnel.salaries.wizard.etape', $etat['etape_courante']);
        }

        return view('personnel.salaries.create', [
            'numero' => $numero,
            'total' => self::TOTAL_ETAPES,
            'donnees' => $etat['donnees'],
            'structures' => Structure::orderBy('nom')->get(),
            'postes' => Poste::orderBy('intitule')->get(),
        ]);
    }

    public function enregistrerEtape(Request $request, int $numero)
    {
        $this->authorize('create', Salarie::class);

        if ($numero < 1 || $numero > self::TOTAL_ETAPES) {
            abort(404);
        }

        $donnees = $request->validate(
            $this->reglesEtape($numero),
            $this->messagesEtape($numero),
        );

        // Étape 1 : stocker la photo en zone temporaire
        if ($numero === 1 && $request->hasFile('photo')) {
            // Supprimer l'ancienne photo temp si on repasse sur l'étape 1
            $etat = session(self::SESSION_KEY, ['donnees' => []]);
            if (! empty($etat['donnees']['chemin_photo_temp'])) {
                Storage::disk('local')->delete($etat['donnees']['chemin_photo_temp']);
            }
            $donnees['chemin_photo_temp'] = $request->file('photo')
                ->store('salaries/photos_temp', 'local');
        }

        // Fusionner dans la session
        $etat = session(self::SESSION_KEY, ['etape_courante' => 1, 'donnees' => []]);
        $etat['donnees'] = array_merge($etat['donnees'], $donnees);
        $etat['etape_courante'] = min($numero + 1, self::TOTAL_ETAPES);
        session([self::SESSION_KEY => $etat]);

        // Destination : étape suivante ou récapitulatif
        $redirect = $numero === self::TOTAL_ETAPES
            ? route('personnel.salaries.wizard.recapitulatif')
            : route('personnel.salaries.wizard.etape', $numero + 1);

        // ⚠️ Réponse adaptée au type de requête
        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect]);
        }

        return redirect($redirect);
    }

    public function recapitulatif()
    {
        $this->authorize('create', Salarie::class);

        $etat = session(self::SESSION_KEY);
        if (! $etat || $etat['etape_courante'] < self::TOTAL_ETAPES) {
            return redirect()->route('personnel.salaries.wizard.etape', 1);
        }

        return view('personnel.salaries.recapitulatif', [
            'donnees' => $etat['donnees'],
        ]);
    }

    public function valider(Request $request, CreerSalarie $action)
    {
        $this->authorize('create', Salarie::class);

        $etat = session(self::SESSION_KEY);
        if (! $etat || empty($etat['donnees'])) {
            return redirect()->route('personnel.salaries.wizard.etape', 1)
                ->with('error', 'Aucune donnée à valider.');
        }

        $donnees = $etat['donnees'];

        // Déplacer la photo temp vers l'emplacement définitif
        if (! empty($donnees['chemin_photo_temp'])) {
            $cheminDefinitif = 'salaries/photos/' . basename($donnees['chemin_photo_temp']);
            Storage::disk('local')->move($donnees['chemin_photo_temp'], $cheminDefinitif);
            $donnees['chemin_photo'] = $cheminDefinitif;
            unset($donnees['chemin_photo_temp']);
        }

        $salarie = $action->executer($donnees);
        session()->forget(self::SESSION_KEY);

        return redirect()->route('personnel.salaries.show', $salarie)
            ->with('success', 'Salarié créé avec succès.');
    }

    public function abandonner()
    {
        // Nettoyer la photo temporaire si elle existe
        $etat = session(self::SESSION_KEY);
        if (! empty($etat['donnees']['chemin_photo_temp'])) {
            Storage::disk('local')->delete($etat['donnees']['chemin_photo_temp']);
        }

        session()->forget(self::SESSION_KEY);
        return redirect()->route('personnel.salaries.index')
            ->with('success', 'Création annulée.');
    }

    private function reglesEtape(int $numero): array
    {
        return match ($numero) {
            1 => [
                'nom' => ['required', 'string', 'max:255'],
                'prenoms' => ['required', 'string', 'max:255'],
                'sexe' => ['nullable', 'in:M,F'],
                'date_naissance' => ['nullable', 'date', 'before:today'],
                'lieu_naissance' => ['nullable', 'string', 'max:255'],
                'nationalite' => ['nullable', 'string', 'max:200'],
                'photo' => ['nullable', 'image', 'max:2048'],
                'type_piece' => ['nullable', 'string', 'max:200'],
                'numero_piece' => ['nullable', 'string', 'max:255'],
                'date_expiration_piece' => ['nullable', 'date'],
            ],
            2 => [
                'telephone_principal' => ['nullable', 'string', 'max:160'],
                'telephone_secondaire' => ['nullable', 'string', 'max:160'],
                'email_personnel' => ['nullable', 'email', 'max:255'],
                'email_professionnel' => ['nullable', 'email', 'max:255'],
                'adresse' => ['nullable', 'string'],
                'ville' => ['nullable', 'string', 'max:200'],
                'pays_residence' => ['nullable', 'string', 'max:200'],
                'gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ],
            3 => [
                'situation_matrimoniale' => ['nullable', 'string', 'max:160'],
                'contact_urgence_nom' => ['nullable', 'string', 'max:255'],
                'contact_urgence_lien' => ['nullable', 'string', 'max:200'],
                'contact_urgence_telephone' => ['nullable', 'string', 'max:160'],
            ],
            4 => [
                'numero_cnss' => ['nullable', 'string', 'max:240'],
                'date_immatriculation_cnss' => ['nullable', 'date'],
                'numero_amu' => ['nullable', 'string', 'max:240'],
                'organisme_assurance' => ['nullable', 'string', 'max:255'],
                'banque' => ['nullable', 'string', 'max:255'],
                'compte_bancaire' => ['nullable', 'string', 'max:255'],
                'mode_paiement' => ['nullable', 'string', 'max:200'],
            ],
            5 => [
                'date_embauche' => ['required', 'date'],
                'date_prise_service' => ['nullable', 'date', 'after_or_equal:date_embauche'],
                'structure_id' => ['nullable', 'exists:structures,id'],
                'poste_id' => ['nullable', 'exists:postes,id'],
                'type_contrat' => ['nullable', 'string', 'max:160'],
                'reference_contrat' => ['nullable', 'string', 'max:255'],
                'date_contrat' => ['nullable', 'date'],
                'date_fin_contrat' => ['nullable', 'date', 'after_or_equal:date_contrat'],
                'lieu_affectation' => ['nullable', 'string', 'max:255'],
                'statut_emploi' => ['nullable', 'string', 'max:255'],
            ],
            default => [],
        };
    }

    private function messagesEtape(int $numero): array
    {
        return match ($numero) {
            1 => [
                'nom.required' => 'Le nom est obligatoire.',
                'prenoms.required' => 'Le prénom est obligatoire.',
                'date_naissance.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
                'photo.image' => 'La photo doit être une image.',
                'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            ],
            5 => [
                'date_embauche.required' => 'La date d\'embauche est obligatoire.',
                'date_prise_service.after_or_equal' => 'La date de prise de service doit être postérieure ou égale à la date d\'embauche.',
                'date_fin_contrat.after_or_equal' => 'La date de fin de contrat doit être postérieure ou égale à la date du contrat.',
                'structure_id.exists' => 'La structure sélectionnée n\'existe pas.',
                'poste_id.exists' => 'Le poste sélectionné n\'existe pas.',
            ],
            default => [],
        };
    }
}
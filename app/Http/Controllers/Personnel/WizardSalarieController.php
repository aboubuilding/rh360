<?php

namespace App\Http\Controllers\Personnel;

use App\Domain\Classification\Models\PositionClassification;
use App\Domain\Organisation\Models\Poste;
use App\Domain\Organisation\Models\Structure;
use App\Domain\Personnel\Actions\CreerSalarie;
use App\Domain\Personnel\Models\BrouillonSalarie;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * Assistant de création en cinq étapes (CDC §4 2.2).
 * Le brouillon est sauvegardé en base (brouillons_salaries) à chaque étape :
 * l'utilisateur peut le reprendre plus tard, ou l'abandonner.
 */
class WizardSalarieController extends Controller
{
    private const TOTAL_ETAPES = 5;

    /** Étape atteinte une fois les cinq étapes enregistrées : récapitulatif. */
    private const ETAPE_RECAPITULATIF = self::TOTAL_ETAPES + 1;

    /**
     * Démarre un brouillon, ou reprend celui en cours de l'utilisateur.
     */
    public function demarrer(Request $request)
    {
        $this->authorize('create', Salarie::class);

        $brouillon = $this->brouillon($request);

        if ($brouillon) {
            return $this->redirigerVersEtape($brouillon)
                ->with('success', 'Brouillon de création repris là où vous l\'aviez laissé.');
        }

        BrouillonSalarie::create([
            'entreprise_id' => $request->user()->entreprise_id,
            'utilisateur_id' => $request->user()->id,
            'donnees' => [],
            'etape_courante' => 1,
            'etat' => 1,
        ]);

        return redirect()->route('personnel.salaries.wizard.etape', 1);
    }

    public function etape(Request $request, int $numero)
    {
        $this->authorize('create', Salarie::class);

        if ($numero < 1 || $numero > self::TOTAL_ETAPES) {
            abort(404);
        }

        $brouillon = $this->brouillon($request);
        if (! $brouillon) {
            return redirect()->route('personnel.salaries.wizard.demarrer');
        }

        // Navigation autorisée uniquement vers une étape déjà atteinte
        if ($numero > min($brouillon->etape_courante, self::TOTAL_ETAPES)) {
            return $this->redirigerVersEtape($brouillon);
        }

        return view('personnel.salaries.create', [
            'numero' => $numero,
            'total' => self::TOTAL_ETAPES,
            'donnees' => $brouillon->donnees ?? [],
            'structures' => Structure::orderBy('nom')->get(),
            'postes' => Poste::orderBy('intitule')->get(),
            'positions' => PositionClassification::with(['categorie', 'classe', 'echelon'])->orderBy('ordre')->get(),
        ]);
    }

    public function enregistrerEtape(Request $request, int $numero)
    {
        $this->authorize('create', Salarie::class);

        if ($numero < 1 || $numero > self::TOTAL_ETAPES) {
            abort(404);
        }

        $brouillon = $this->brouillon($request);
        if (! $brouillon) {
            return redirect()->route('personnel.salaries.wizard.demarrer');
        }

        $donnees = Arr::except(
            $request->validate($this->reglesEtape($numero), $this->messagesEtape($numero)),
            // Champs sensibles non autorisés ignorés (CDC §6)
            Salarie::champsSensiblesInterdits($request->user()),
        );
        unset($donnees['photo']);

        // Étape 1 : photo stockée en zone temporaire privée, rattachée au brouillon
        if ($numero === 1 && $request->hasFile('photo')) {
            if ($brouillon->chemin_photo) {
                Storage::disk('local')->delete($brouillon->chemin_photo);
            }
            $brouillon->chemin_photo = $request->file('photo')->store('salaries/photos_temp', 'local');
        }

        $brouillon->donnees = array_merge($brouillon->donnees ?? [], $donnees);
        $brouillon->etape_courante = max($brouillon->etape_courante, $numero + 1);
        $brouillon->save();

        $redirect = $numero === self::TOTAL_ETAPES
            ? route('personnel.salaries.wizard.recapitulatif')
            : route('personnel.salaries.wizard.etape', $numero + 1);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect]);
        }

        return redirect($redirect);
    }

    public function recapitulatif(Request $request)
    {
        $this->authorize('create', Salarie::class);

        $brouillon = $this->brouillon($request);
        if (! $brouillon || $brouillon->etape_courante < self::ETAPE_RECAPITULATIF) {
            return $brouillon
                ? $this->redirigerVersEtape($brouillon)
                : redirect()->route('personnel.salaries.wizard.demarrer');
        }

        $donnees = $brouillon->donnees ?? [];

        return view('personnel.salaries.recapitulatif', [
            'donnees' => $donnees,
            'structure' => ! empty($donnees['structure_id']) ? Structure::find($donnees['structure_id']) : null,
            'poste' => ! empty($donnees['poste_id']) ? Poste::find($donnees['poste_id']) : null,
            'position' => ! empty($donnees['position_classification_id'])
                ? PositionClassification::find($donnees['position_classification_id']) : null,
        ]);
    }

    public function valider(Request $request, CreerSalarie $action)
    {
        $this->authorize('create', Salarie::class);

        $brouillon = $this->brouillon($request);
        if (! $brouillon || $brouillon->etape_courante < self::ETAPE_RECAPITULATIF) {
            return redirect()->route('personnel.salaries.wizard.demarrer')
                ->with('error', 'Le brouillon est incomplet : terminez les cinq étapes avant de valider.');
        }

        $donnees = $brouillon->donnees ?? [];

        // Déplacer la photo temporaire vers l'emplacement définitif
        if ($brouillon->chemin_photo && Storage::disk('local')->exists($brouillon->chemin_photo)) {
            $cheminDefinitif = 'salaries/photos/' . basename($brouillon->chemin_photo);
            Storage::disk('local')->move($brouillon->chemin_photo, $cheminDefinitif);
            $donnees['chemin_photo'] = $cheminDefinitif;
        }

        $salarie = $action->executer($donnees);
        $brouillon->delete();

        return redirect()->route('personnel.salaries.show', $salarie)
            ->with('success', 'Salarié créé avec succès.');
    }

    public function abandonner(Request $request)
    {
        $this->authorize('create', Salarie::class);

        $brouillon = $this->brouillon($request);
        if ($brouillon) {
            if ($brouillon->chemin_photo) {
                Storage::disk('local')->delete($brouillon->chemin_photo);
            }
            $brouillon->delete();
        }

        return redirect()->route('personnel.salaries.index')
            ->with('success', 'Création annulée.');
    }

    /**
     * Brouillon en cours de l'utilisateur connecté (un seul à la fois).
     */
    private function brouillon(Request $request): ?BrouillonSalarie
    {
        return BrouillonSalarie::where('utilisateur_id', $request->user()->id)->latest('id')->first();
    }

    private function redirigerVersEtape(BrouillonSalarie $brouillon)
    {
        return $brouillon->etape_courante >= self::ETAPE_RECAPITULATIF
            ? redirect()->route('personnel.salaries.wizard.recapitulatif')
            : redirect()->route('personnel.salaries.wizard.etape', max(1, $brouillon->etape_courante));
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
                'position_classification_id' => ['nullable', 'exists:positions_classification,id'],
                'date_effet_echelon' => ['nullable', 'date'],
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
                'position_classification_id.exists' => 'La position de classification sélectionnée n\'existe pas.',
            ],
            default => [],
        };
    }
}

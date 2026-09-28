<?php

namespace App\Http\Controllers\Pilotage;

use App\Domain\Contrats\Models\NotificationContrat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationContratController extends Controller
{
    /**
     * Compteur de notifications non lues — utilisé par le badge de la cloche.
     */
    public function compteur(Request $request)
    {
        $count = NotificationContrat::where('utilisateur_id', $request->user()->id)
            ->whereNull('lu_le')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * 5 dernières notifications non lues — pour le dropdown de la cloche.
     */
    public function dernieres(Request $request)
    {
        $notifications = NotificationContrat::with(['alerte.contrat.salarie'])
            ->where('utilisateur_id', $request->user()->id)
            ->whereNull('lu_le')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'intitule' => $n->alerte?->intitule,
                'contrat_reference' => $n->alerte?->contrat?->reference,
                'salarie' => $n->alerte?->contrat?->salarie?->nom_complet,
                'date_echeance' => $n->alerte?->date_echeance?->format('d/m/Y'),
                'seuil' => $n->seuil,
                'created_at' => $n->created_at->diffForHumans(),
                'url' => $n->alerte?->contrat
                    ? route('contrats.contrats.show', $n->alerte->contrat)
                    : null,
            ]);

        return response()->json($notifications);
    }
}
<?php

namespace App\Http\Controllers\Paie;

use App\Domain\Paie\Models\BulletinPaie;
use App\Http\Controllers\Controller;

class BulletinPaieController extends Controller
{
    public function show(BulletinPaie $bulletin)
    {
        $this->authorize('view', $bulletin);

        $bulletin->load(['salarie', 'periode', 'lignes']);

        // Charger les infos de l'entreprise pour l'en-tête
        $entreprise = \App\Domain\Administration\Models\Entreprise::find($bulletin->entreprise_id);

        return view('paie.bulletins.show', compact('bulletin', 'entreprise'));
    }

    public function imprimer(BulletinPaie $bulletin)
    {
        $this->authorize('imprimer', $bulletin);

        $bulletin->load(['salarie', 'periode', 'lignes']);
        $entreprise = \App\Domain\Administration\Models\Entreprise::find($bulletin->entreprise_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('paie.bulletins.pdf', [
            'bulletin' => $bulletin,
            'entreprise' => $entreprise,
        ]);

        return $pdf->download("bulletin-{$bulletin->salarie->matricule}-{$bulletin->periode->code}.pdf");
    }
}
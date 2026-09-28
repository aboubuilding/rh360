<?php

namespace App\Http\Controllers\Sst;

use App\Domain\Sst\Services\GenerateurStatistiquesSst;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportingSstController extends Controller
{
    public function __construct(private GenerateurStatistiquesSst $stats) {}

    /**
     * Reporting agrégé — accessible aux profils Direction / Auditeur
     * SANS permission santé nominative.
     */
    public function index(Request $request)
    {
        $this->authorize('reporting', \App\Domain\Sst\Models\VisiteMedicale::class);

        $du = Carbon::parse($request->get('du', now()->startOfYear()->format('Y-m-d')));
        $au = Carbon::parse($request->get('au', now()->endOfYear()->format('Y-m-d')));

        $statistiques = $this->stats->globales(
            auth()->user()->entreprise_id,
            $du,
            $au,
        );

        return view('sst.reporting.index', [
            'stats' => $statistiques,
            'du' => $du->format('Y-m-d'),
            'au' => $au->format('Y-m-d'),
        ]);
    }

    public function exporter(Request $request)
    {
        $this->authorize('reporting', \App\Domain\Sst\Models\VisiteMedicale::class);

        $du = Carbon::parse($request->get('du', now()->startOfYear()->format('Y-m-d')));
        $au = Carbon::parse($request->get('au', now()->endOfYear()->format('Y-m-d')));

        $stats = $this->stats->globales(auth()->user()->entreprise_id, $du, $au);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($stats, $du, $au) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithHeadings,
                \Maatwebsite\Excel\Concerns\WithMultipleSheets
            {
                public function __construct(private array $stats, private Carbon $du, private Carbon $au) {}

                public function sheets(): array
                {
                    return [
                        new class($this->stats) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithTitle {
                            public function __construct(private array $stats) {}
                            public function title(): string { return 'Synthèse'; }
                            public function array(): array
                            {
                                return [
                                    ['Visites planifiées', $this->stats['visites_medicales']['total_planifiees'] ?? 0],
                                    ['Visites réalisées', $this->stats['visites_medicales']['realisees'] ?? 0],
                                    ['Taux de réalisation', ($this->stats['visites_medicales']['taux_realisation'] ?? 0) . ' %'],
                                    ['Événements sécurité', $this->stats['evenements_securite']['total'] ?? 0],
                                    ['Risques actifs', $this->stats['risques']['total_actifs'] ?? 0],
                                    ['EPI actives', $this->stats['epi']['total_actives'] ?? 0],
                                    ['Habilitations actives', $this->stats['habilitations']['total_actives'] ?? 0],
                                ];
                            }
                        },
                    ];
                }

                public function array(): array { return []; }
                public function headings(): array { return ['Indicateur', 'Valeur']; }
            },
            "reporting-sst-{$du->format('Y-m-d')}-{$au->format('Y-m-d')}.xlsx"
        );
    }
}
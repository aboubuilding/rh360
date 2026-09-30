<?php

namespace App\Domain\Pilotage\Services;

use App\Domain\Administration\Models\Utilisateur;
use App\Domain\Pilotage\DTO\TableauDeBord;
use App\Domain\Pilotage\DTO\Widget;
use Illuminate\Support\Collection;

class AgregateurTableauDeBord
{
    public function __construct(
        private CalculateurIndicateursPersonnel $personnel,
        private CalculateurIndicateursContrats $contrats,
        private CalculateurIndicateursCarriere $carriere,
        private CalculateurIndicateursConges $conges,
        private CalculateurIndicateursDocuments $documents,
        private CalculateurIndicateursSst $sst,
        private CalculateurIndicateursPaie $paie,
    ) {}

    /**
     * Construit le tableau de bord d'un utilisateur.
     * Ne renvoie que les widgets pour lesquels l'utilisateur a la permission.
     */
    public function pourUtilisateur(Utilisateur $utilisateur): TableauDeBord
    {
        $entrepriseId = $utilisateur->entreprise_id;
        $widgets = collect();

        // ============================================================
        // Personnel
        // ============================================================
        if ($utilisateur->peut('salaries.view')) {
            $widgets->push(new Widget(
                cle: 'personnel',
                titre: 'Dossiers RH',
                icone: 'fa-users',
                couleur: 'primary',
                ordre: 10,
                indicateurs: $this->personnel->calculer($entrepriseId),
            ));
        }

        // ============================================================
        // Contrats
        // ============================================================
        if ($utilisateur->peut('contrats.view')) {
            $widgets->push(new Widget(
                cle: 'contrats',
                titre: 'Contrats',
                icone: 'fa-file-contract',
                couleur: 'info',
                ordre: 20,
                indicateurs: $this->contrats->calculer($entrepriseId),
            ));
        }

        // ============================================================
        // Carrière
        // ============================================================
        if ($utilisateur->peut('carriere.view')) {
            $widgets->push(new Widget(
                cle: 'carriere',
                titre: 'Carrière & Mobilité',
                icone: 'fa-chart-line',
                couleur: 'primary',
                ordre: 30,
                indicateurs: $this->carriere->calculer($entrepriseId),
            ));
        }

        // ============================================================
        // Congés
        // ============================================================
        if ($utilisateur->peut('conges.view')) {
            $widgets->push(new Widget(
                cle: 'conges',
                titre: 'Congés & Absences',
                icone: 'fa-calendar-alt',
                couleur: 'warning',
                ordre: 40,
                indicateurs: $this->conges->calculer($entrepriseId),
            ));
        }

        // ============================================================
        // Documents
        // ============================================================
        if ($utilisateur->peut('salaries.view')) {
            $widgets->push(new Widget(
                cle: 'documents',
                titre: 'Documents',
                icone: 'fa-folder-open',
                couleur: 'secondary',
                ordre: 50,
                indicateurs: $this->documents->calculer($entrepriseId),
            ));
        }

        // ============================================================
        // SST
        // ============================================================
        // Indicateurs agrégés (comptages) : accessibles aussi via reports.sst (Direction, Auditeur)
        if ($utilisateur->peut('health.view') || $utilisateur->peut('safety.view') || $utilisateur->peut('reports.sst')) {
            $widgets->push(new Widget(
                cle: 'sst',
                titre: 'Santé & Sécurité',
                icone: 'fa-hard-hat',
                couleur: 'danger',
                ordre: 60,
                indicateurs: $this->liensSstAutorises($utilisateur, $this->sst->calculer($entrepriseId)),
            ));
        }

        // ============================================================
        // Paie
        // ============================================================
        if ($utilisateur->peut('paie.view')) {
            $widgets->push(new Widget(
                cle: 'paie',
                titre: 'Paie',
                icone: 'fa-money-check-alt',
                couleur: 'success',
                ordre: 70,
                indicateurs: $this->paie->calculer($entrepriseId),
            ));
        }

        // Trier par ordre
        $widgets = $widgets->sortBy('ordre')->values();

        return new TableauDeBord(
            widgets: $widgets,
            contexte: [
                'utilisateur' => $utilisateur->nom_complet,
                'role' => $utilisateur->libelleRole(),
                'entreprise' => $utilisateur->entreprise?->nom,
                'date' => now()->translatedFormat('l d F Y'),
            ],
            derniereSynchro: now()->format('d/m/Y H:i:s'),
        );
    }

    /**
     * Sans accès aux registres nominatifs SST (Direction, Auditeur), les indicateurs
     * mènent au reporting agrégé plutôt qu'à une liste nominative refusée (CDC §4 7.1, §6).
     */
    private function liensSstAutorises(Utilisateur $utilisateur, Collection $indicateurs): Collection
    {
        $registres = [
            '/sst/visites' => 'health.view',
            '/sst/evenements' => 'safety.view',
            '/sst/risques' => 'risks.view',
            '/sst/epi' => 'ppe.view',
            '/sst/habilitations' => 'habilitations.view',
        ];

        return $indicateurs->map(function ($indicateur) use ($utilisateur, $registres) {
            $chemin = $indicateur->lien ? parse_url($indicateur->lien, PHP_URL_PATH) : null;

            foreach ($registres as $prefixe => $permission) {
                if ($chemin && str_starts_with($chemin, $prefixe) && ! $utilisateur->peut($permission)) {
                    return $indicateur->avecLien(route('sst.reporting.index'));
                }
            }

            return $indicateur;
        });
    }
}
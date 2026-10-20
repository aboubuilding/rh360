<?php

namespace App\Domain\Interim\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Rapport d'une reprise ou d'un contrôle : une ligne par enregistrement, exportable en CSV.
 */
class RapportReprise
{
    /** @var list<array<string, mixed>> */
    public array $lignes = [];

    public function __construct(public readonly bool $simulation, public readonly string $lot) {}

    public function ligne(string $source, int $id, ?string $reference, ?string $salarie, string $ancien, string $nouvel, ?string $anomalie = null): void
    {
        $this->lignes[] = [
            'source' => $source,
            'id' => $id,
            'reference' => $reference,
            'salarie' => $salarie,
            'ancien_etat' => $ancien,
            'nouvel_etat' => $nouvel,
            'anomalie' => $anomalie,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function anomalies(): array
    {
        return array_values(array_filter($this->lignes, fn ($l) => $l['anomalie'] !== null));
    }

    /**
     * Écrit le rapport en CSV (séparateur « ; », UTF-8 avec BOM pour Excel) sur le disque local.
     *
     * @param  list<array<string, mixed>>  $lignes
     * @param  list<string>  $entetes
     */
    public static function ecrireCsv(string $chemin, array $entetes, array $lignes): string
    {
        $flux = fopen('php://temp', 'r+');
        fwrite($flux, "\xEF\xBB\xBF");
        fputcsv($flux, $entetes, ';');
        foreach ($lignes as $ligne) {
            fputcsv($flux, array_values($ligne), ';');
        }
        rewind($flux);
        Storage::disk('local')->put($chemin, stream_get_contents($flux));
        fclose($flux);

        return Storage::disk('local')->path($chemin);
    }

    public function exporter(): string
    {
        $suffixe = $this->simulation ? '-simulation' : '';

        return self::ecrireCsv(
            "interims/rapports/{$this->lot}{$suffixe}.csv",
            ['source', 'id', 'reference', 'salarie', 'ancien_etat', 'nouvel_etat', 'anomalie'],
            $this->lignes,
        );
    }
}

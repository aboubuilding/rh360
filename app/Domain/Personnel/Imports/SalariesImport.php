<?php

namespace App\Domain\Personnel\Imports;

use App\Domain\Personnel\Actions\CreerSalarie;
use App\Domain\Personnel\Models\Salarie;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class SalariesImport implements ToModel, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public array $erreurs = [];
    public int $importes = 0;

    public function __construct(private CreerSalarie $action) {}

    public function model(array $row)
    {
        try {
            // Vérifier si le matricule existe déjà
            if (! empty($row['matricule'])) {
                $existe = Salarie::withoutGlobalScopes()
                    ->where('entreprise_id', auth()->user()->entreprise_id)
                    ->where('matricule', $row['matricule'])
                    ->exists();
                if ($existe) {
                    $this->erreurs[] = "Matricule {$row['matricule']} déjà existant.";
                    return null;
                }
            }

            $this->action->executer($row);
            $this->importes++;
            return null;
        } catch (\Throwable $e) {
            $this->erreurs[] = "Ligne ignorée : " . $e->getMessage();
            return null;
        }
    }
}
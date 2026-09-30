<?php

namespace App\Domain\Organisation\Actions;

use App\Domain\Organisation\Models\Structure;
use Illuminate\Support\Facades\DB;

class FusionnerStructures
{
    /**
     * Fusionne $source dans $cible : les enfants et postes de $source
     * sont rattachés à $cible, puis $source est marquée supprimée.
     */
    public function executer(Structure $source, Structure $cible): void
    {
        DB::transaction(function () use ($source, $cible) {
            Structure::where('parent_id', $source->id)
                ->update(['parent_id' => $cible->id]);

            $source->postes()->update(['structure_id' => $cible->id]);

            $source->marquerSupprime();
        });
    }
}
<?php

namespace App\Domain\Carriere\Requests;

class UpdateMouvementRequest extends StoreMouvementRequest
{
    // Hérite des règles et messages — la restriction de statut est
    // vérifiée par la policy et l'Action ModifierMouvement.
}
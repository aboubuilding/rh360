<?php

namespace App\Domain\Interim\Services;

/**
 * L'indemnité d'intérim ne peut pas être calculée (barème absent ou ambigu).
 */
class IndemniteIncalculable extends \DomainException {}
